<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Yetki kontrolü (sadece Admin erişebilir)
if (!checkPermission(['Admin'])) {
    setMessage('error', 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
    header("Location: dashboard.php");
    exit;
}

// Kullanıcı ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz kullanıcı ID.');
    header("Location: ayarlar.php");
    exit;
}

// Kullanıcı bilgilerini al
$userQuery = "SELECT * FROM kullanicilar WHERE kullanici_id = $id";
$userResult = $conn->query($userQuery);

if ($userResult->num_rows === 0) {
    setMessage('error', 'Kullanıcı bulunamadı.');
    header("Location: ayarlar.php");
    exit;
}

$user = $userResult->fetch_assoc();

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $ad = post('ad');
    $soyad = post('soyad');
    $email = post('email');
    $rol = post('rol');
    $durum = post('durum', 'Aktif');
    $change_password = isset($_POST['change_password']) ? true : false;
    $sifre = post('sifre');
    $sifre_tekrar = post('sifre_tekrar');

    // Doğrulama
    if (empty($ad) || empty($soyad) || empty($email) || empty($rol)) {
        $error = "Lütfen zorunlu alanları doldurun.";
    } else {
        // E-posta kontrolü
        $checkQuery = "SELECT kullanici_id FROM kullanicilar WHERE email = ? AND kullanici_id != ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("si", $email, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "Bu e-posta adresi zaten kullanılıyor.";
        } else {
            // Şifre değiştirilecek mi?
            if ($change_password) {
                // Şifre doğrulama
                if (empty($sifre) || empty($sifre_tekrar)) {
                    $error = "Lütfen şifre alanlarını doldurun.";
                } elseif ($sifre !== $sifre_tekrar) {
                    $error = "Şifreler eşleşmiyor.";
                } elseif (strlen($sifre) < 6) {
                    $error = "Şifre en az 6 karakter olmalıdır.";
                }
            }

            if (empty($error)) {
                // Şifre değiştirilecek mi?
                if ($change_password) {
                    // Şifreyi hashle
                    $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);

                    // Kullanıcı güncelleme işlemi (şifre ile)
                    $stmt = $conn->prepare("UPDATE kullanicilar SET ad = ?, soyad = ?, email = ?, rol = ?, durum = ?, sifre = ? WHERE kullanici_id = ?");
                    $stmt->bind_param("ssssssi", $ad, $soyad, $email, $rol, $durum, $hashed_password, $id);
                } else {
                    // Kullanıcı güncelleme işlemi (şifre olmadan)
                    $stmt = $conn->prepare("UPDATE kullanicilar SET ad = ?, soyad = ?, email = ?, rol = ?, durum = ? WHERE kullanici_id = ?");
                    $stmt->bind_param("sssssi", $ad, $soyad, $email, $rol, $durum, $id);
                }

                if ($stmt->execute()) {
                    setMessage('success', 'Kullanıcı başarıyla güncellendi.');

                    // Admin kendisini güncellediyse session bilgilerini güncelle
                    if ($id === (int)$_SESSION['user_id']) {
                        $_SESSION['full_name'] = $ad . ' ' . $soyad;
                    }

                    // Kullanıcı bilgilerini yenile
                    $userResult = $conn->query($userQuery);
                    $user = $userResult->fetch_assoc();

                    $success = "Kullanıcı bilgileri güncellendi.";
                } else {
                    $error = "Kullanıcı güncellenirken bir hata oluştu: " . $stmt->error;
                }

                $stmt->close();
            }
        }

        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Düzenle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Kullanıcı Düzenle</h2>
                    <a href="ayarlar.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Kullanıcı Listesine Dön
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                    <?php echo errorMessage($error); ?>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <?php echo successMessage($success); ?>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-edit me-1"></i> Kullanıcı Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" id="kullanici_adi" value="<?php echo $user['kullanici_adi']; ?>" disabled>
                                        <div class="form-text">Kullanıcı adı değiştirilemez.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="ad" class="form-label">Ad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ad" name="ad" value="<?php echo $user['ad']; ?>" required>
                                        <div class="invalid-feedback">Ad zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="soyad" class="form-label">Soyad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="soyad" name="soyad" value="<?php echo $user['soyad']; ?>" required>
                                        <div class="invalid-feedback">Soyad zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                        <select class="form-select" id="rol" name="rol" required <?php echo ($id == 1) ? 'disabled' : ''; ?>>
                                            <option value="Admin" <?php echo ($user['rol'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="Yönetici" <?php echo ($user['rol'] == 'Yönetici') ? 'selected' : ''; ?>>Yönetici</option>
                                            <option value="Öğretmen" <?php echo ($user['rol'] == 'Öğretmen') ? 'selected' : ''; ?>>Öğretmen</option>
                                        </select>
                                        <?php if ($id == 1): ?>
                                            <input type="hidden" name="rol" value="Admin">
                                            <div class="form-text">Ana admin hesabının rolü değiştirilemez.</div>
                                        <?php endif; ?>
                                        <div class="invalid-feedback">Rol seçimi zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="durum" class="form-label">Durum</label>
                                        <select class="form-select" id="durum" name="durum" <?php echo ($id == 1 || $id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                            <option value="Aktif" <?php echo ($user['durum'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="Pasif" <?php echo ($user['durum'] == 'Pasif') ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                        <?php if ($id == 1 || $id == $_SESSION['user_id']): ?>
                                            <input type="hidden" name="durum" value="Aktif">
                                            <div class="form-text"><?php echo ($id == 1) ? 'Ana admin hesabı' : 'Kendi hesabınız'; ?> pasif yapılamaz.</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="change_password" name="change_password">
                                        <label class="form-check-label" for="change_password">Şifreyi değiştir</label>
                                    </div>

                                    <div id="password_fields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="sifre" class="form-label">Yeni Şifre</label>
                                            <input type="password" class="form-control" id="sifre" name="sifre" minlength="6">
                                            <div class="invalid-feedback">Şifre en az 6 karakter olmalıdır.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="sifre_tekrar" class="form-label">Yeni Şifre (Tekrar)</label>
                                            <input type="password" class="form-control" id="sifre_tekrar" name="sifre_tekrar">
                                            <div class="invalid-feedback">Şifreler eşleşmiyor.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Değişiklikleri Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script>
        // Şifre değiştirme checkbox'ı
        document.getElementById('change_password').addEventListener('change', function() {
            const passwordFields = document.getElementById('password_fields');
            const sifreInput = document.getElementById('sifre');
            const sifreTekrarInput = document.getElementById('sifre_tekrar');

            if (this.checked) {
                passwordFields.style.display = 'block';
                sifreInput.setAttribute('required', '');
                sifreTekrarInput.setAttribute('required', '');
            } else {
                passwordFields.style.display = 'none';
                sifreInput.removeAttribute('required');
                sifreTekrarInput.removeAttribute('required');
            }
        });
    </script>
</body>

</html>