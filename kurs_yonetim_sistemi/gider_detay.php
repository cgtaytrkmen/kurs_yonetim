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

// Gider ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz gider ID.');
    header("Location: giderler.php");
    exit;
}

// Gider bilgilerini al
$giderQuery = "SELECT * FROM giderler WHERE gider_id = $id";
$giderResult = $conn->query($giderQuery);

if ($giderResult->num_rows === 0) {
    setMessage('error', 'Gider bulunamadı.');
    header("Location: giderler.php");
    exit;
}

$gider = $giderResult->fetch_assoc();

// Gider silme işlemi
if (isset($_GET['delete'])) {
    $deleteQuery = "DELETE FROM giderler WHERE gider_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Gider kaydı başarıyla silindi.');
        header("Location: giderler.php");
        exit;
    } else {
        setMessage('error', 'Gider kaydı silinirken bir hata oluştu: ' . $conn->error);
    }
}

// Benzer türdeki diğer giderler
$similarQuery = "SELECT * FROM giderler WHERE gider_turu = '{$gider['gider_turu']}' AND gider_id != $id ORDER BY gider_tarihi DESC LIMIT 5";
$similarResult = $conn->query($similarQuery);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gider Detayı - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Gider Detayı</h2>
                    <div>
                        <a href="gider_duzenle.php?id=<?php echo $id; ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i> Düzenle
                        </a>
                        <a href="#" class="btn btn-danger me-2" onclick="return confirm('Bu gider kaydını silmek istediğinize emin misiniz?') ? window.location.href='gider_detay.php?id=<?php echo $id; ?>&delete=1' : false">
                            <i class="fas fa-trash me-1"></i> Sil
                        </a>
                        <a href="giderler.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Listeye Dön
                        </a>
                    </div>
                </div>

                <?php echo showMessage(); ?>

                <div class="row">
                    <!-- Gider Bilgileri -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-danger text-white">
                                <i class="fas fa-receipt me-1"></i> Gider Bilgileri
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="card-title"><?php echo $gider['gider_turu']; ?></h5>
                                        <p class="text-muted mb-0">Gider ID: #<?php echo $gider['gider_id']; ?></p>
                                        <p class="text-muted">Tarih: <?php echo formatDateTime($gider['gider_tarihi']); ?></p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <h3 class="text-danger mb-3"><?php echo formatMoney($gider['miktar']); ?></h3>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <?php if (!empty($gider['aciklama'])): ?>
                                    <div class="mb-3">
                                        <h6>Açıklama</h6>
                                        <p><?php echo nl2br(htmlspecialchars($gider['aciklama'])); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-light">
                                        <i class="fas fa-info-circle me-1"></i> Bu gider kaydı için açıklama girilmemiş.
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button onclick="window.print()" class="btn btn-outline-primary">
                                        <i class="fas fa-print me-1"></i> Yazdır
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yan Panel -->
                    <div class="col-md-4">
                        <!-- Benzer Giderler -->
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <i class="fas fa-history me-1"></i> Benzer Giderler
                            </div>
                            <div class="card-body">
                                <?php if ($similarResult->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($similar = $similarResult->fetch_assoc()): ?>
                                            <a href="gider_detay.php?id=<?php echo $similar['gider_id']; ?>" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo formatMoney($similar['miktar']); ?></h6>
                                                    <small><?php echo formatDate($similar['gider_tarihi']); ?></small>
                                                </div>
                                                <?php if (!empty($similar['aciklama'])): ?>
                                                    <small class="text-muted"><?php echo mb_substr($similar['aciklama'], 0, 50); ?><?php echo (mb_strlen($similar['aciklama']) > 50) ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </a>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Bu türde başka gider kaydı bulunmamaktadır.
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2 mt-3">
                                    <a href="giderler.php?gider_turu=<?php echo urlencode($gider['gider_turu']); ?>" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-search me-1"></i> Tüm "<?php echo $gider['gider_turu']; ?>" Giderleri
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Hızlı İşlemler -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-bolt me-1"></i> Hızlı İşlemler
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="gider_ekle.php" class="btn btn-outline-primary mb-2">
                                        <i class="fas fa-plus-circle me-1"></i> Yeni Gider Ekle
                                    </a>
                                    <a href="raporlar.php?type=mali" class="btn btn-outline-success mb-2">
                                        <i class="fas fa-chart-bar me-1"></i> Mali Raporu Görüntüle
                                    </a>
                                </div>
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