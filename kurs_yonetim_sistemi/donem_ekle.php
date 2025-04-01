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
    $donem_adi = post('donem_adi');
    $baslangic_tarihi = post('baslangic_tarihi');
    $bitis_tarihi = post('bitis_tarihi');
    $durum = post('durum', 'Aktif');

    // Doğrulama
    if (empty($donem_adi) || empty($baslangic_tarihi) || empty($bitis_tarihi)) {
        $error = "Lütfen tüm alanları doldurun.";
    } elseif (strtotime($bitis_tarihi) < strtotime($baslangic_tarihi)) {
        $error = "Bitiş tarihi, başlangıç tarihinden önce olamaz.";
    } else {
        // Dönem ekleme işlemi
        $stmt = $conn->prepare("INSERT INTO donemler (donem_adi, baslangic_tarihi, bitis_tarihi, durum) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $donem_adi, $baslangic_tarihi, $bitis_tarihi, $durum);

        if ($stmt->execute()) {
            $donem_id = $conn->insert_id;
            setMessage('success', 'Dönem başarıyla eklendi.');
            header("Location: donemler.php");
            exit;
        } else {
            $error = "Dönem eklenirken bir hata oluştu: " . $stmt->error;
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
    <title>Yeni Dönem Ekle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yeni Dönem Ekle</h2>
                    <a href="donemler.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Dönemlere Dön
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
                        <i class="fas fa-calendar-plus me-1"></i> Dönem Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="donem_adi" class="form-label">Dönem Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="donem_adi" name="donem_adi" required>
                                <div class="invalid-feedback">Dönem adı gereklidir.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="baslangic_tarihi" class="form-label">Başlangıç Tarihi <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="baslangic_tarihi" name="baslangic_tarihi" required>
                                    <div class="invalid-feedback">Başlangıç tarihi gereklidir.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="bitis_tarihi" class="form-label">Bitiş Tarihi <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="bitis_tarihi" name="bitis_tarihi" required>
                                    <div class="invalid-feedback">Bitiş tarihi gereklidir.</div>
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