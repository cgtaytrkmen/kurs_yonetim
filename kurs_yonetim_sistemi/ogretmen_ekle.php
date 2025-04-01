<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Yetki kontrolü
if (!checkPermission(['Admin', 'Yönetici'])) {
    setMessage('error', 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $ad = post('ad');
    $soyad = post('soyad');
    $telefon = post('telefon');
    $email = post('email');
    $uzmanlik_alani = post('uzmanlik_alani');
    $baslangic_tarihi = post('baslangic_tarihi');
    $durum = post('durum', 'Aktif');

    // Doğrulama
    if (empty($ad) || empty($soyad)) {
        $error = "Ad ve soyad alanları zorunludur.";
    } else {
        // Öğretmen ekleme işlemi
        $stmt = $conn->prepare("INSERT INTO ogretmenler (ad, soyad, telefon, email, uzmanlik_alani, baslangic_tarihi, durum) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $ad, $soyad, $telefon, $email, $uzmanlik_alani, $baslangic_tarihi, $durum);

        if ($stmt->execute()) {
            setMessage('success', 'Öğretmen başarıyla eklendi.');
            header("Location: ogretmenler.php");
            exit;
        } else {
            $error = "Öğretmen eklenirken bir hata oluştu: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Öğretmen Ekle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yeni Öğretmen Ekle</h2>
                    <a href="ogretmenler.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Öğretmenlere Dön
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
                        <i class="fas fa-chalkboard-teacher me-1"></i> Öğretmen Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ad" class="form-label">Ad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ad" name="ad" required>
                                        <div class="invalid-feedback">Ad alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="soyad" class="form-label">Soyad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="soyad" name="soyad" required>
                                        <div class="invalid-feedback">Soyad alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="telefon" class="form-label">Telefon</label>
                                        <input type="tel" class="form-control" id="telefon" name="telefon">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="uzmanlik_alani" class="form-label">Uzmanlık Alanı</label>
                                        <input type="text" class="form-control" id="uzmanlik_alani" name="uzmanlik_alani">
                                    </div>

                                    <div class="mb-3">
                                        <label for="baslangic_tarihi" class="form-label">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" id="baslangic_tarihi" name="baslangic_tarihi">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="durum" class="form-label">Durum</label>
                                <select class="form-select" id="durum" name="durum">
                                    <option value="Aktif" selected>Aktif</option>
                                    <option value="Pasif">Pasif</option>
                                </select>
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