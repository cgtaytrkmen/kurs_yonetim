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
    $sinif_adi = post('sinif_adi');
    $kapasite = post('kapasite') ?: null;
    $lokasyon = post('lokasyon');
    $aciklama = post('aciklama');

    // Doğrulama
    if (empty($sinif_adi)) {
        $error = "Sınıf adı zorunludur.";
    } else {
        // Sınıf ekleme işlemi
        $stmt = $conn->prepare("INSERT INTO siniflar (sinif_adi, kapasite, lokasyon, aciklama) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $sinif_adi, $kapasite, $lokasyon, $aciklama);

        if ($stmt->execute()) {
            setMessage('success', 'Sınıf başarıyla eklendi.');
            header("Location: siniflar.php");
            exit;
        } else {
            $error = "Sınıf eklenirken bir hata oluştu: " . $stmt->error;
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
    <title>Yeni Sınıf Ekle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yeni Sınıf Ekle</h2>
                    <a href="siniflar.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Sınıflara Dön
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
                        <i class="fas fa-door-open me-1"></i> Sınıf Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="sinif_adi" class="form-label">Sınıf Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sinif_adi" name="sinif_adi" required>
                                <div class="invalid-feedback">Sınıf adı zorunludur.</div>
                            </div>

                            <div class="mb-3">
                                <label for="kapasite" class="form-label">Kapasite</label>
                                <input type="number" class="form-control" id="kapasite" name="kapasite" min="1">
                                <div class="form-text">Boş bırakılırsa sınırsız kabul edilecektir.</div>
                            </div>

                            <div class="mb-3">
                                <label for="lokasyon" class="form-label">Lokasyon</label>
                                <input type="text" class="form-control" id="lokasyon" name="lokasyon">
                            </div>

                            <div class="mb-3">
                                <label for="aciklama" class="form-label">Açıklama</label>
                                <textarea class="form-control" id="aciklama" name="aciklama" rows="3"></textarea>
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