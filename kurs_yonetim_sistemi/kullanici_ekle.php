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

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $kullanici_adi = post('kullanici_adi');
    $sifre = post('sifre');
    $sifre_tekrar = post('sifre_tekrar');
    $ad = post('ad');
    $soyad = post('soyad');
    $email = post('email');
    $rol = post('rol');
    $durum = post('durum', 'Aktif');

    // Doğrulama
    if (empty($kullanici_adi) || empty($sifre) || empty($ad) || empty($soyad) || empty($email) || empty($rol)) {
        $error = "Lütfen zorunlu alanları doldurun.";
    } elseif ($sifre !== $sifre_tekrar) {
        $error = "Şifreler eşleşmiyor.";
    } elseif (strlen($sifre) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır.";
    } else {
        // Kullanıcı adı ve e-posta kontrolü
        $checkQuery = "SELECT kullanici_id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("ss", $kullanici_adi, $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.";
        } else {
            // Şifreyi hashle
            $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);

            // Kullanıcı ekleme işlemi
            $stmt = $conn->prepare("INSERT INTO kullanicilar (kullanici_adi, sifre, ad, soyad, email, rol, durum) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $kullanici_adi, $hashed_password, $ad, $soyad, $email, $rol, $durum);

            if ($stmt->execute()) {
                setMessage('success', 'Kullanıcı başarıyla eklendi.');
                header("Location: ayarlar.php");
                exit;
            } else {
                $error = "Kullanıcı eklenirken bir hata oluştu: " . $stmt->error;
            }

            $stmt->close();
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
    <title>Yeni Kullanıcı Ekle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yeni Kullanıcı Ekle</h2>
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
                        <i class="fas fa-user-plus me-1"></i> Kullanıcı Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="kullanici_adi" class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" required>
                                        <div class="invalid-feedback">Kullanıcı adı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sifre" class="form-label">Şifre <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="sifre" name="sifre" minlength="6" required>
                                        <div class="invalid-feedback">Şifre en az 6 karakter olmalıdır.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sifre_tekrar" class="form-label">Şifre (Tekrar) <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="sifre_tekrar" name="sifre_tekrar" required>
                                        <div class="invalid-feedback">Şifreler eşleşmiyor.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ad" class="form-label">Ad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ad" name="ad" required>
                                        <div class="invalid-feedback">Ad zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="soyad" class="form-label">Soyad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="soyad" name="soyad" required>
                                        <div class="invalid-feedback">Soyad zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                        <select class="form-select" id="rol" name="rol" required>
                                            <option value="">Seçiniz</option>
                                            <option value="Admin">Admin</option>
                                            <option value="Yönetici">Yönetici</option>
                                            <option value="Öğretmen">Öğretmen</option>
                                        </select>
                                        <div class="invalid-feedback">Rol seçimi zorunludur.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="durum" class="form-label">Durum</label>
                                        <select class="form-select" id="durum" name="durum">
                                            <option value="Aktif" selected>Aktif</option>
                                            <option value="Pasif">Pasif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-2">
                                    <i class="fas fa-eraser me-1"></i> Temizle
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Kaydet
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
</body>

</html>