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

// Dönem ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz dönem ID.');
    header("Location: donemler.php");
    exit;
}

// Dönem bilgilerini al
$donemQuery = "SELECT * FROM donemler WHERE donem_id = $id";
$donemResult = $conn->query($donemQuery);

if ($donemResult->num_rows === 0) {
    setMessage('error', 'Dönem bulunamadı.');
    header("Location: donemler.php");
    exit;
}

$donem = $donemResult->fetch_assoc();

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
        // Dönem güncelleme işlemi
        $stmt = $conn->prepare("UPDATE donemler SET donem_adi = ?, baslangic_tarihi = ?, bitis_tarihi = ?, durum = ? WHERE donem_id = ?");
        $stmt->bind_param("ssssi", $donem_adi, $baslangic_tarihi, $bitis_tarihi, $durum, $id);

        if ($stmt->execute()) {
            $success = "Dönem bilgileri başarıyla güncellendi.";
            // Dönem bilgilerini yenile
            $donemResult = $conn->query($donemQuery);
            $donem = $donemResult->fetch_assoc();
        } else {
            $error = "Dönem güncellenirken bir hata oluştu: " . $stmt->error;
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
    <title>Dönem Düzenle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Dönem Düzenle</h2>
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
                        <i class="fas fa-calendar-alt me-1"></i> Dönem Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="donem_adi" class="form-label">Dönem Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="donem_adi" name="donem_adi" value="<?php echo $donem['donem_adi']; ?>" required>
                                <div class="invalid-feedback">Dönem adı gereklidir.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="baslangic_tarihi" class="form-label">Başlangıç Tarihi <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="baslangic_tarihi" name="baslangic_tarihi" value="<?php echo $donem['baslangic_tarihi']; ?>" required>
                                    <div class="invalid-feedback">Başlangıç tarihi gereklidir.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="bitis_tarihi" class="form-label">Bitiş Tarihi <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="bitis_tarihi" name="bitis_tarihi" value="<?php echo $donem['bitis_tarihi']; ?>" required>
                                    <div class="invalid-feedback">Bitiş tarihi gereklidir.</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="durum" class="form-label">Durum</label>
                                <select class="form-select" id="durum" name="durum">
                                    <option value="Aktif" <?php echo ($donem['durum'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="Pasif" <?php echo ($donem['durum'] == 'Pasif') ? 'selected' : ''; ?>>Pasif</option>
                                </select>
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
</body>

</html>