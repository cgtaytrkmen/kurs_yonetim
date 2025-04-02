<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Öğrenci ID'sini al
$ogrenci_id = isset($_GET['ogrenci_id']) ? (int)$_GET['ogrenci_id'] : 0;

if ($ogrenci_id <= 0) {
    setMessage('error', 'Geçersiz öğrenci ID.');
    header("Location: ogrenciler.php");
    exit;
}

// Öğrenci bilgilerini al
$ogrenciQuery = "SELECT * FROM ogrenciler WHERE ogrenci_id = $ogrenci_id";
$ogrenciResult = $conn->query($ogrenciQuery);

if ($ogrenciResult->num_rows === 0) {
    setMessage('error', 'Öğrenci bulunamadı.');
    header("Location: ogrenciler.php");
    exit;
}

$ogrenci = $ogrenciResult->fetch_assoc();

// Tarih aralığı filtresi
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-3 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Tarih formatı kontrolü
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)) {
    $start_date = date('Y-m-d', strtotime('-3 months'));
    $end_date = date('Y-m-d');
}

// Yoklama kayıtlarını al
$yoklamaQuery = "SELECT yd.*, y.ders_tarihi, dp.gun, d.ders_adi, s.sinif_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi
               FROM yoklama_detay yd
               JOIN yoklamalar y ON yd.yoklama_id = y.yoklama_id
               JOIN ders_programi dp ON y.program_id = dp.program_id
               JOIN dersler d ON dp.ders_id = d.ders_id
               JOIN siniflar s ON dp.sinif_id = s.sinif_id
               JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
               WHERE yd.ogrenci_id = $ogrenci_id AND y.ders_tarihi BETWEEN '$start_date' AND '$end_date'
               ORDER BY y.ders_tarihi DESC, dp.gun, dp.baslangic_saat";
$yoklamaResult = $conn->query($yoklamaQuery);

// Yoklama istatistikleri
$statsQuery = "SELECT 
                SUM(CASE WHEN yd.durum = 'Var' THEN 1 ELSE 0 END) as var_sayisi,
                SUM(CASE WHEN yd.durum = 'Yok' THEN 1 ELSE 0 END) as yok_sayisi,
                SUM(CASE WHEN yd.durum = 'İzinli' THEN 1 ELSE 0 END) as izinli_sayisi,
                SUM(CASE WHEN yd.durum = 'Geç' THEN 1 ELSE 0 END) as gec_sayisi,
                COUNT(*) as toplam
              FROM yoklama_detay yd
              JOIN yoklamalar y ON yd.yoklama_id = y.yoklama_id
              WHERE yd.ogrenci_id = $ogrenci_id AND y.ders_tarihi BETWEEN '$start_date' AND '$end_date'";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Dersler bazında devam durumu
$dersStatQuery = "SELECT d.ders_adi,
                COUNT(*) as toplam_ders,
                SUM(CASE WHEN yd.durum = 'Var' THEN 1 ELSE 0 END) as var_sayisi,
                SUM(CASE WHEN yd.durum = 'Geç' THEN 1 ELSE 0 END) as gec_sayisi,
                SUM(CASE WHEN yd.durum = 'İzinli' THEN 1 ELSE 0 END) as izinli_sayisi,
                SUM(CASE WHEN yd.durum = 'Yok' THEN 1 ELSE 0 END) as yok_sayisi
              FROM yoklama_detay yd
              JOIN yoklamalar y ON yd.yoklama_id = y.yoklama_id
              JOIN ders_programi dp ON y.program_id = dp.program_id
              JOIN dersler d ON dp.ders_id = d.ders_id
              WHERE yd.ogrenci_id = $ogrenci_id AND y.ders_tarihi BETWEEN '$start_date' AND '$end_date'
              GROUP BY d.ders_adi
              ORDER BY d.ders_adi";
$dersStatResult = $conn->query($dersStatQuery);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoklama Raporu - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yoklama Raporu: <?php echo $ogrenci['ad'] . ' ' . $ogrenci['soyad']; ?></h2>
                    <div>
                        <button class="btn btn-outline-primary me-2" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Yazdır
                        </button>
                        <a href="ogrenci_detay.php?id=<?php echo $ogrenci_id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Öğrenci Detayına Dön
                        </a>
                    </div>
                </div>

                <?php echo showMessage(); ?>

                <!-- Filtreler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter me-1"></i> Tarih Aralığı
                    </div>
                    <div class="card-body">
                        <form method="GET" action="yoklama_rapor.php" class="row g-3">
                            <input type="hidden" name="ogrenci_id" value="<?php echo $ogrenci_id; ?>">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Filtrele
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Özet İstatistikler -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-chart-pie me-1"></i> Devam Durumu Özeti
                                <span class="badge bg-light text-dark ms-2">
                                    <?php echo formatDate($start_date); ?> - <?php echo formatDate($end_date); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <?php if ($stats['toplam'] > 0): ?>
                                            <div class="row text-center mb-3">
                                                <div class="col-md-3 col-6 mb-3">
                                                    <div class="p-3 rounded bg-success text-white">
                                                        <h4><?php echo $stats['var_sayisi']; ?></h4>
                                                        <p class="mb-0">Var</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-6 mb-3">
                                                    <div class="p-3 rounded bg-danger text-white">
                                                        <h4><?php echo $stats['yok_sayisi']; ?></h4>
                                                        <p class="mb-0">Yok</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-6 mb-3">
                                                    <div class="p-3 rounded bg-warning text-white">
                                                        <h4><?php echo $stats['izinli_sayisi']; ?></h4>
                                                        <p class="mb-0">İzinli</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-6 mb-3">
                                                    <div class="p-3 rounded bg-info text-white">
                                                        <h4><?php echo $stats['gec_sayisi']; ?></h4>
                                                        <p class="mb-0">Geç</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <h6>Genel Devam Oranı</h6>
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
                                                    <small>Toplam <?php echo $stats['toplam']; ?> ders</small>
                                                    <small>Devam Oranı: %<?php echo round($varYuzde + $gecYuzde, 1); ?></small>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-1"></i> Seçili tarih aralığında yoklama kaydı bulunmamaktadır.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <i class="fas fa-user-graduate me-1"></i> Öğrenci Bilgileri
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>Öğrenci No:</span>
                                                        <span class="text-muted"><?php echo $ogrenci['ogrenci_id']; ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>Adı Soyadı:</span>
                                                        <span class="text-muted"><?php echo $ogrenci['ad'] . ' ' . $ogrenci['soyad']; ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>Telefon:</span>
                                                        <span class="text-muted"><?php echo $ogrenci['telefon'] ?: '-'; ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <span>Durum:</span>
                                                        <span class="text-muted"><?php echo formatStatus($ogrenci['durum']); ?></span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dersler Bazında Devam Durumu -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-1"></i> Dersler Bazında Devam Durumu
                    </div>
                    <div class="card-body">
                        <?php if ($dersStatResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ders</th>
                                            <th>Toplam Ders</th>
                                            <th>Var</th>
                                            <th>Geç</th>
                                            <th>İzinli</th>
                                            <th>Yok</th>
                                            <th>Devam Oranı</th>
                                            <th>Grafik</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($dersStat = $dersStatResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $dersStat['ders_adi']; ?></td>
                                                <td><?php echo $dersStat['toplam_ders']; ?></td>
                                                <td class="text-success"><?php echo $dersStat['var_sayisi']; ?></td>
                                                <td class="text-info"><?php echo $dersStat['gec_sayisi']; ?></td>
                                                <td class="text-warning"><?php echo $dersStat['izinli_sayisi']; ?></td>
                                                <td class="text-danger"><?php echo $dersStat['yok_sayisi']; ?></td>
                                                <td>
                                                    <?php
                                                    $totalDers = $dersStat['toplam_ders'];
                                                    $devamOrani = $totalDers > 0 ? (($dersStat['var_sayisi'] + $dersStat['gec_sayisi']) / $totalDers * 100) : 0;
                                                    echo "%" . round($devamOrani, 1);
                                                    ?>
                                                </td>
                                                <td width="20%">
                                                    <div class="progress">
                                                        <?php
                                                        $varYuzde = $totalDers > 0 ? ($dersStat['var_sayisi'] / $totalDers * 100) : 0;
                                                        $gecYuzde = $totalDers > 0 ? ($dersStat['gec_sayisi'] / $totalDers * 100) : 0;
                                                        $izinliYuzde = $totalDers > 0 ? ($dersStat['izinli_sayisi'] / $totalDers * 100) : 0;
                                                        $yokYuzde = $totalDers > 0 ? ($dersStat['yok_sayisi'] / $totalDers * 100) : 0;
                                                        ?>
                                                        <div class="progress-bar bg-success" style="width: <?php echo $varYuzde; ?>%" title="Var: <?php echo round($varYuzde, 1); ?>%"></div>
                                                        <div class="progress-bar bg-info" style="width: <?php echo $gecYuzde; ?>%" title="Geç: <?php echo round($gecYuzde, 1); ?>%"></div>
                                                        <div class="progress-bar bg-warning" style="width: <?php echo $izinliYuzde; ?>%" title="İzinli: <?php echo round($izinliYuzde, 1); ?>%"></div>
                                                        <div class="progress-bar bg-danger" style="width: <?php echo $yokYuzde; ?>%" title="Yok: <?php echo round($yokYuzde, 1); ?>%"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i> Seçili tarih aralığında ders bazında yoklama kaydı bulunmamaktadır.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Yoklama Detayları -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-list me-1"></i> Yoklama Detayları
                    </div>
                    <div class="card-body">
                        <?php if ($yoklamaResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Gün</th>
                                            <th>Ders</th>
                                            <th>Sınıf</th>
                                            <th>Öğretmen</th>
                                            <th>Durum</th>
                                            <th>Açıklama</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($yoklama = $yoklamaResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo formatDate($yoklama['ders_tarihi']); ?></td>
                                                <td><?php echo $yoklama['gun']; ?></td>
                                                <td><?php echo $yoklama['ders_adi']; ?></td>
                                                <td><?php echo $yoklama['sinif_adi']; ?></td>
                                                <td><?php echo $yoklama['ogretmen_adi']; ?></td>
                                                <td><?php echo formatAttendance($yoklama['durum']); ?></td>
                                                <td><?php echo $yoklama['aciklama'] ?: '-'; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i> Seçili tarih aralığında yoklama kaydı bulunmamaktadır.
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