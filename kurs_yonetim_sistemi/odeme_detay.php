<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Ödeme ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz ödeme ID.');
    header("Location: odemeler.php");
    exit;
}

// Ödeme bilgilerini al
$odemeQuery = "SELECT p.*, o.ad, o.soyad, o.ogrenci_id, d.donem_adi
              FROM odemeler p
              JOIN ogrenciler o ON p.ogrenci_id = o.ogrenci_id
              LEFT JOIN donemler d ON p.donem_id = d.donem_id
              WHERE p.odeme_id = $id";
$odemeResult = $conn->query($odemeQuery);

if ($odemeResult->num_rows === 0) {
    setMessage('error', 'Ödeme kaydı bulunamadı.');
    header("Location: odemeler.php");
    exit;
}

$odeme = $odemeResult->fetch_assoc();

// İlgili öğrencinin diğer ödemeleri
$digerOdemelerQuery = "SELECT p.*, d.donem_adi
                      FROM odemeler p
                      LEFT JOIN donemler d ON p.donem_id = d.donem_id
                      WHERE p.ogrenci_id = {$odeme['ogrenci_id']} AND p.odeme_id != $id
                      ORDER BY p.odeme_tarihi DESC
                      LIMIT 5";
$digerOdemelerResult = $conn->query($digerOdemelerQuery);

// Ödeme silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $deleteQuery = "DELETE FROM odemeler WHERE odeme_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Ödeme kaydı başarıyla silindi.');
        header("Location: odemeler.php");
        exit;
    } else {
        setMessage('error', 'Ödeme kaydı silinirken bir hata oluştu: ' . $conn->error);
    }
}

// Öğrencinin toplam ödemeleri
$toplamOdemeQuery = "SELECT SUM(miktar) as toplam FROM odemeler WHERE ogrenci_id = {$odeme['ogrenci_id']}";
$toplamOdemeResult = $conn->query($toplamOdemeQuery);
$toplamOdeme = $toplamOdemeResult->fetch_assoc()['toplam'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Detayı - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Ödeme Detayı</h2>
                    <div>
                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                            <a href="odeme_duzenle.php?id=<?php echo $id; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Düzenle
                            </a>
                            <a href="#" class="btn btn-danger ms-2" onclick="return confirm('Bu ödemeyi silmek istediğinize emin misiniz?') ? window.location.href='odeme_detay.php?id=<?php echo $id; ?>&delete=1' : false">
                                <i class="fas fa-trash me-1"></i> Sil
                            </a>
                        <?php endif; ?>
                        <a href="odemeler.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-arrow-left me-1"></i> Listeye Dön
                        </a>
                    </div>
                </div>

                <?php echo showMessage(); ?>

                <div class="row">
                    <!-- Ödeme Bilgileri -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-receipt me-1"></i> Ödeme Bilgileri
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="card-title">Ödeme Makbuzu</h5>
                                        <p class="text-muted mb-0">Ödeme No: #<?php echo $odeme['odeme_id']; ?></p>
                                        <p class="text-muted">Tarih: <?php echo formatDateTime($odeme['odeme_tarihi']); ?></p>

                                        <h6 class="mt-4">Öğrenci Bilgileri</h6>
                                        <p>
                                            <a href="ogrenci_detay.php?id=<?php echo $odeme['ogrenci_id']; ?>">
                                                <?php echo $odeme['ad'] . ' ' . $odeme['soyad']; ?>
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <h3 class="text-success mb-3"><?php echo formatMoney($odeme['miktar']); ?></h3>
                                        <p class="mb-1"><strong>Ödeme Türü:</strong> <?php echo $odeme['odeme_turu']; ?></p>
                                        <?php if (!empty($odeme['donem_adi'])): ?>
                                            <p class="mb-1"><strong>Dönem:</strong> <?php echo $odeme['donem_adi']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <?php if (!empty($odeme['aciklama'])): ?>
                                    <div class="mb-3">
                                        <h6>Açıklama</h6>
                                        <p><?php echo nl2br(htmlspecialchars($odeme['aciklama'])); ?></p>
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
                        <!-- Özet Bilgiler -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-user-graduate me-1"></i> Öğrenci Ödeme Özeti
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $odeme['ad'] . ' ' . $odeme['soyad']; ?></h5>
                                <ul class="list-group list-group-flush mt-3">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Toplam Ödemeler:</span>
                                        <span class="fw-bold"><?php echo formatMoney($toplamOdeme); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Son Ödeme:</span>
                                        <span class="text-muted"><?php echo formatMoney($odeme['miktar']); ?></span>
                                    </li>
                                </ul>
                                <div class="d-grid gap-2 mt-3">
                                    <a href="odeme_ekle.php?ogrenci_id=<?php echo $odeme['ogrenci_id']; ?>" class="btn btn-success">
                                        <i class="fas fa-plus-circle me-1"></i> Yeni Ödeme Ekle
                                    </a>
                                    <a href="ogrenci_detay.php?id=<?php echo $odeme['ogrenci_id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-user me-1"></i> Öğrenci Detayları
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Önceki Ödemeler -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-history me-1"></i> Diğer Ödemeler
                            </div>
                            <div class="card-body">
                                <?php if ($digerOdemelerResult->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($digerOdeme = $digerOdemelerResult->fetch_assoc()): ?>
                                            <a href="odeme_detay.php?id=<?php echo $digerOdeme['odeme_id']; ?>" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo formatMoney($digerOdeme['miktar']); ?></h6>
                                                    <small><?php echo formatDate($digerOdeme['odeme_tarihi']); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo $digerOdeme['odeme_turu']; ?></p>
                                                <?php if (!empty($digerOdeme['donem_adi'])): ?>
                                                    <small class="text-muted"><?php echo $digerOdeme['donem_adi']; ?></small>
                                                <?php endif; ?>
                                            </a>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Bu öğrenciye ait başka ödeme kaydı bulunmamaktadır.
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2 mt-3">
                                    <a href="odemeler.php?search=<?php echo urlencode($odeme['ad'] . ' ' . $odeme['soyad']); ?>" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-search me-1"></i> Tüm Ödemeleri Görüntüle
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