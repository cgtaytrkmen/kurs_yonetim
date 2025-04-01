<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Yoklama ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz yoklama ID.');
    header("Location: yoklama_listesi.php");
    exit;
}

// Yoklama bilgilerini al
$yoklamaQuery = "SELECT y.*, dp.program_id, d.ders_adi, s.sinif_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dp.gun
                FROM yoklamalar y
                JOIN ders_programi dp ON y.program_id = dp.program_id
                JOIN dersler d ON dp.ders_id = d.ders_id
                JOIN siniflar s ON dp.sinif_id = s.sinif_id
                JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                WHERE y.yoklama_id = $id";
$yoklamaResult = $conn->query($yoklamaQuery);

if ($yoklamaResult->num_rows === 0) {
    setMessage('error', 'Yoklama kaydı bulunamadı.');
    header("Location: yoklama_listesi.php");
    exit;
}

$yoklama = $yoklamaResult->fetch_assoc();

// Yoklama detaylarını al
$detayQuery = "SELECT yd.*, CONCAT(o.ad, ' ', o.soyad) as ogrenci_adi, o.ogrenci_id
              FROM yoklama_detay yd
              JOIN ogrenciler o ON yd.ogrenci_id = o.ogrenci_id
              WHERE yd.yoklama_id = $id
              ORDER BY o.ad, o.soyad";
$detayResult = $conn->query($detayQuery);

// Yoklama istatistikleri
$statsQuery = "SELECT 
                SUM(CASE WHEN durum = 'Var' THEN 1 ELSE 0 END) as var_sayisi,
                SUM(CASE WHEN durum = 'Yok' THEN 1 ELSE 0 END) as yok_sayisi,
                SUM(CASE WHEN durum = 'İzinli' THEN 1 ELSE 0 END) as izinli_sayisi,
                SUM(CASE WHEN durum = 'Geç' THEN 1 ELSE 0 END) as gec_sayisi,
                COUNT(*) as toplam
              FROM yoklama_detay
              WHERE yoklama_id = $id";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Yoklama silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $deleteQuery = "DELETE FROM yoklamalar WHERE yoklama_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Yoklama kaydı başarıyla silindi.');
        header("Location: yoklama_listesi.php");
        exit;
    } else {
        setMessage('error', 'Yoklama kaydı silinirken bir hata oluştu: ' . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoklama Detayı - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yoklama Detayı</h2>
                    <div>
                        <a href="yoklama.php?program_id=<?php echo $yoklama['program_id']; ?>&date=<?php echo $yoklama['ders_tarihi']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Düzenle
                        </a>
                        <a href="yoklama_listesi.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-arrow-left me-1"></i> Listeye Dön
                        </a>
                    </div>
                </div>

                <?php echo showMessage(); ?>

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-clipboard-check me-1"></i> Yoklama Bilgileri
                    </div>
                    <div class="card-body">
                        <!-- Ders Bilgileri -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <strong>Tarih:</strong> <?php echo formatDate($yoklama['ders_tarihi']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Gün:</strong> <?php echo $yoklama['gun']; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Ders:</strong> <?php echo $yoklama['ders_adi']; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Sınıf:</strong> <?php echo $yoklama['sinif_adi']; ?>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <strong>Öğretmen:</strong> <?php echo $yoklama['ogretmen_adi']; ?>
                            </div>
                            <div class="col-md-6 text-end">
                                <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                                    <a href="#" class="btn btn-sm btn-danger" onclick="return confirm('Bu yoklama kaydını silmek istediğinize emin misiniz?') ? window.location.href='yoklama_detay.php?id=<?php echo $id; ?>&delete=1' : false">
                                        <i class="fas fa-trash me-1"></i> Yoklamayı Sil
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Yoklama İstatistikleri -->
                        <div class="row mb-4">
                            <div class="col-md-3 col-6 text-center mb-3">
                                <div class="p-3 rounded bg-success text-white">
                                    <h3><?php echo $stats['var_sayisi']; ?></h3>
                                    <p class="mb-0">Var</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 text-center mb-3">
                                <div class="p-3 rounded bg-danger text-white">
                                    <h3><?php echo $stats['yok_sayisi']; ?></h3>
                                    <p class="mb-0">Yok</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 text-center mb-3">
                                <div class="p-3 rounded bg-warning text-white">
                                    <h3><?php echo $stats['izinli_sayisi']; ?></h3>
                                    <p class="mb-0">İzinli</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 text-center mb-3">
                                <div class="p-3 rounded bg-info text-white">
                                    <h3><?php echo $stats['gec_sayisi']; ?></h3>
                                    <p class="mb-0">Geç</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="progress" style="height: 25px;">
                                <?php
                                $total = $stats['toplam'];
                                $varYuzde = $total > 0 ? ($stats['var_sayisi'] / $total * 100) : 0;
                                $gecYuzde = $total > 0 ? ($stats['gec_sayisi'] / $total * 100) : 0;
                                $izinliYuzde = $total > 0 ? ($stats['izinli_sayisi'] / $total * 100) : 0;
                                $yokYuzde = $total > 0 ? ($stats['yok_sayisi'] / $total * 100) : 0;
                                ?>
                                <div class="progress-bar bg-success" style="width: <?php echo $varYuzde; ?>%" title="Var: <?php echo round($varYuzde, 1); ?>%">
                                    <?php echo round($varYuzde, 1); ?>%
                                </div>
                                <div class="progress-bar bg-info" style="width: <?php echo $gecYuzde; ?>%" title="Geç: <?php echo round($gecYuzde, 1); ?>%">
                                    <?php echo round($gecYuzde, 1); ?>%
                                </div>
                                <div class="progress-bar bg-warning" style="width: <?php echo $izinliYuzde; ?>%" title="İzinli: <?php echo round($izinliYuzde, 1); ?>%">
                                    <?php echo round($izinliYuzde, 1); ?>%
                                </div>
                                <div class="progress-bar bg-danger" style="width: <?php echo $yokYuzde; ?>%" title="Yok: <?php echo round($yokYuzde, 1); ?>%">
                                    <?php echo round($yokYuzde, 1); ?>%
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small>Toplam <?php echo $stats['toplam']; ?> öğrenci</small>
                                <small>Devam Oranı: %<?php echo round($varYuzde + $gecYuzde, 1); ?></small>
                            </div>
                        </div>

                        <!-- Yoklama Detayları -->
                        <h5 class="mt-4 mb-3">Öğrenci Yoklama Listesi</h5>
                        <?php if ($detayResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Öğrenci</th>
                                            <th>Durum</th>
                                            <th>Açıklama</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $count = 1; ?>
                                        <?php while ($detay = $detayResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo $detay['ogrenci_adi']; ?></td>
                                                <td><?php echo formatAttendance($detay['durum']); ?></td>
                                                <td><?php echo $detay['aciklama'] ?: '-'; ?></td>
                                                <td>
                                                    <a href="ogrenci_detay.php?id=<?php echo $detay['ogrenci_id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-user"></i> Profil
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i> Bu yoklamaya ait öğrenci kayıtları bulunamadı.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>