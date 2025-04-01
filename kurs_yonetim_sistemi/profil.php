<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini al
$userQuery = "SELECT * FROM kullanicilar WHERE kullanici_id = $user_id";
$userResult = $conn->query($userQuery);

if ($userResult->num_rows === 0) {
    setMessage('error', 'Kullanıcı bulunamadı.');
    header("Location: dashboard.php");
    exit;
}

$user = $userResult->fetch_assoc();

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = post('action');

    if ($action === 'profile_update') {
        // Profil bilgilerini güncelle
        $ad = post('ad');
        $soyad = post('soyad');
        $email = post('email');

        // Doğrulama
        if (empty($ad) || empty($soyad) || empty($email)) {
            $error = "Lütfen tüm alanları doldurun.";
        } else {
            // Email formatını doğrula
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Geçersiz e-posta adresi.";
            } else {
                // Email kullanımda mı kontrol et
                $checkEmailQuery = "SELECT kullanici_id FROM kullanicilar WHERE email = '$email' AND kullanici_id != $user_id";
                $checkEmailResult = $conn->query($checkEmailQuery);

                if ($checkEmailResult->num_rows > 0) {
                    $error = "Bu e-posta adresi zaten kullanımda.";
                } else {
                    // Profil güncelleme
                    $updateQuery = "UPDATE kullanicilar SET ad = ?, soyad = ?, email = ? WHERE kullanici_id = ?";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("sssi", $ad, $soyad, $email, $user_id);

                    if ($stmt->execute()) {
                        $success = "Profil bilgileriniz başarıyla güncellendi.";

                        // Session bilgilerini güncelle
                        $_SESSION['full_name'] = $ad . ' ' . $soyad;

                        // Kullanıcı bilgilerini yenile
                        $userResult = $conn->query($userQuery);
                        $user = $userResult->fetch_assoc();
                    } else {
                        $error = "Profil güncellenirken bir hata oluştu: " . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'password_update') {
        // Şifre değiştir
        $current_password = post('current_password');
        $new_password = post('new_password');
        $confirm_password = post('confirm_password');

        // Doğrulama
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "Lütfen tüm şifre alanlarını doldurun.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Yeni şifre ve şifre tekrarı eşleşmiyor.";
        } elseif (strlen($new_password) < 6) {
            $error = "Yeni şifre en az 6 karakter olmalıdır.";
        } else {
            // Mevcut şifreyi doğrula
            if (password_verify($current_password, $user['sifre'])) {
                // Yeni şifreyi hashle
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Şifre güncelleme
                $updateQuery = "UPDATE kullanicilar SET sifre = ? WHERE kullanici_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("si", $hashed_password, $user_id);

                if ($stmt->execute()) {
                    $success = "Şifreniz başarıyla güncellendi.";
                } else {
                    $error = "Şifre güncellenirken bir hata oluştu: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $error = "Mevcut şifre yanlış.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Kurs Yönetim Sistemi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Navigation -->
            <?php include 'includes/topnav.php'; ?>

            <!-- Main Content -->
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mt-4">Profil</h2>
                </div>

                <?php if (!empty($error)): ?>
                    <?php echo errorMessage($error); ?>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <?php echo successMessage($success); ?>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <!-- Profil Kartı -->
                        <div class="card mb-4">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-user-circle fa-7x text-primary"></i>
                                </div>
                                <h4 class="card-title"><?php echo $user['ad'] . ' ' . $user['soyad']; ?></h4>
                                <p class="text-muted"><?php echo ucfirst($user['rol']); ?></p>
                                <hr>
                                <div class="text-start">
                                    <p><i class="fas fa-user me-2"></i> Kullanıcı Adı: <?php echo $user['kullanici_adi']; ?></p>
                                    <p><i class="fas fa-envelope me-2"></i> E-posta: <?php echo $user['email']; ?></p>
                                    <p><i class="fas fa-clock me-2"></i> Son Giriş:
                                        <?php echo $user['son_giris'] ? formatDateTime($user['son_giris']) : 'Belirtilmemiş'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- Profil Düzenleme -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-user-edit me-1"></i> Profil Bilgilerini Düzenle
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                                    <input type="hidden" name="action" value="profile_update">

                                    <div class="mb-3">
                                        <label for="ad" class="form-label">Ad</label>
                                        <input type="text" class="form-control" id="ad" name="ad" value="<?php echo $user['ad']; ?>" required>
                                        <div class="invalid-feedback">Ad alanı gereklidir.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="soyad" class="form-label">Soyad</label>
                                        <input type="text" class="form-control" id="soyad" name="soyad" value="<?php echo $user['soyad']; ?>" required>
                                        <div class="invalid-feedback">Soyad alanı gereklidir.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" id="kullanici_adi" value="<?php echo $user['kullanici_adi']; ?>" disabled>
                                        <div class="form-text">Kullanıcı adı değiştirilemez.</div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Değişiklikleri Kaydet
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Şifre Değiştirme -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-key me-1"></i> Şifre Değiştir
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                                    <input type="hidden" name="action" value="password_update">

                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mevcut Şifre</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <div class="invalid-feedback">Mevcut şifrenizi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Yeni Şifre</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                                        <div class="invalid-feedback">Yeni şifre en az 6 karakter olmalıdır.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="invalid-feedback">Şifre tekrarını giriniz.</div>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-key me-1"></i> Şifreyi Güncelle
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>