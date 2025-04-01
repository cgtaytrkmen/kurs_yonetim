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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: ogrenciler.php");
    exit;
}

// Öğrenci bilgilerini al
$studentQuery = "SELECT * FROM ogrenciler WHERE ogrenci_id = $id";
$studentResult = $conn->query($studentQuery);

if ($studentResult->num_rows === 0) {
    setMessage('error', 'Öğrenci bulunamadı.');
    header("Location: ogrenciler.php");
    exit;
}

$student = $studentResult->fetch_assoc();

// Veli bilgilerini al
$parentQuery = "SELECT * FROM veliler WHERE ogrenci_id = $id";
$parentResult = $conn->query($parentQuery);
$parent = $parentResult->num_rows > 0 ? $parentResult->fetch_assoc() : null;

// Ödeme bilgilerini al
$paymentsQuery = "SELECT o.*, d.donem_adi 
                 FROM odemeler o 
                 LEFT JOIN donemler d ON o.donem_id = d.donem_id
                 WHERE o.ogrenci_id = $id 
                 ORDER BY o.odeme_tarihi DESC";
$paymentsResult = $conn->query($paymentsQuery);

// Ders kayıtlarını al
$classesQuery = "SELECT sk.*, dp.gun, dp.baslangic_saat, dp.bitis_saat, d.ders_adi, s.sinif_adi, 
                CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dm.donem_adi
                FROM sinif_kayitlari sk
                JOIN ders_programi dp ON sk.program_id = dp.program_id
                JOIN dersler d ON dp.ders_id = d.ders_id
                JOIN siniflar s ON dp.sinif_id = s.sinif_id
                JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                JOIN donemler dm ON dp.donem_id = dm.donem_id
                WHERE sk.ogrenci_id = $id
                ORDER BY dm.baslangic_tarihi DESC, dp.gun, dp.baslangic_saat";
$classesResult = $conn->query($classesQuery);

// Yoklama kayıtlarını al
$attendanceQuery = "SELECT yd.*, y.ders_tarihi, dp.gun, d.ders_adi, s.sinif_adi
                   FROM yoklama_detay yd
                   JOIN yoklamalar y ON yd.yoklama_id = y.yoklama_id
                   JOIN ders_programi dp ON y.program_id = dp.program_id
                   JOIN dersler d ON dp.ders_id = d.ders_id
                   JOIN siniflar s ON dp.sinif_id = s.sinif_id
                   WHERE yd.ogrenci_id = $id
                   ORDER BY y.ders_tarihi DESC
                   LIMIT 10";
$attendanceResult = $conn->query($attendanceQuery);

// Yoklama istatistikleri
$statsQuery = "SELECT 
                SUM(CASE WHEN yd.durum = 'Var' THEN 1 ELSE 0 END) as var_sayisi,
                SUM(CASE WHEN yd.durum = 'Yok' THEN 1 ELSE 0 END) as yok_sayisi,
                SUM(CASE WHEN yd.durum = 'İzinli' THEN 1 ELSE 0 END) as izinli_sayisi,
                SUM(CASE WHEN yd.durum = 'Geç' THEN 1 ELSE 0 END) as gec_sayisi,
                COUNT(*) as toplam
              FROM yoklama_detay yd
              WHERE yd.ogrenci_id = $id";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Toplam ödeme ve borç durumu
$paymentSummaryQuery = "SELECT 
                         SUM(miktar) as toplam_odeme 
                        FROM odemeler 
                        WHERE ogrenci_id = $id";
$paymentSummaryResult = $conn->query($paymentSummaryQuery);
$paymentSummary = $paymentSummaryResult->fetch_assoc();
$totalPayment = $paymentSummary['toplam_odeme'] ?: 0;

// Ekleme durumu mesajı
$showAddedMessage = isset($_GET['status']) && $_GET['status'] === 'added';
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Detayı - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Öğrenci Detayı</h2>
                    <div>
                        <a href="ogrenci_duzenle.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Düzenle
                        </a>
                        <a href="ogrenciler.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-arrow-left me-1"></i> Listeye Dön
                        </a>
                    </div>
                </div>

                <?php echo showMessage(); ?>

                <?php if ($showAddedMessage): ?>
                    <?php echo successMessage('Öğrenci başarıyla eklendi.'); ?>
                <?php endif; ?>

                <div class="row">
                    <!-- Öğrenci Profil Bilgileri -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-user-graduate me-1"></i> Öğrenci Bilgileri
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="mb-3">
                                        <i class="fas fa-user-circle fa-6x text-primary"></i>
                                    </div>
                                    <h4><?php echo $student['ad'] . ' ' . $student['soyad']; ?></h4>
                                    <p class="text-muted">Öğrenci ID: <?php echo $student['ogrenci_id']; ?></p>
                                    <div class="mt-2"><?php echo formatStatus($student['durum']); ?></div>
                                </div>

                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-birthday-cake me-2"></i> Doğum Tarihi:</span>
                                        <span class="text-muted"><?php echo formatDate($student['dogum_tarihi']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-venus-mars me-2"></i> Cinsiyet:</span>
                                        <span class="text-muted"><?php echo $student['cinsiyet']; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-phone me-2"></i> Telefon:</span>
                                        <span class="text-muted"><?php echo $student['telefon'] ?: '-'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-envelope me-2"></i> E-posta:</span>
                                        <span class="text-muted"><?php echo $student['email'] ?: '-'; ?></span>
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-map-marker-alt me-2"></i> Adres:
                                        <div class="text-muted mt-1"><?php echo $student['adres'] ?: '-'; ?></div>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-calendar-alt me-2"></i> Kayıt Tarihi:</span>
                                        <span class="text-muted"><?php echo formatDateTime($student['kayit_tarihi']); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Veli Bilgileri -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-users me-1"></i> Veli Bilgileri
                            </div>
                            <div class="card-body">
                                <?php if ($parent): ?>
                                    <h5><?php echo $parent['ad'] . ' ' . $parent['soyad']; ?></h5>
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-user-tag me-1"></i>
                                        <?php echo $parent['yakinlik'] ?: 'Belirtilmemiş'; ?>
                                    </p>

                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-phone me-2"></i> Telefon:</span>
                                            <span class="text-muted"><?php echo $parent['telefon']; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-envelope me-2"></i> E-posta:</span>
                                            <span class="text-muted"><?php echo $parent['email'] ?: '-'; ?></span>
                                        </li>
                                        <li class="list-group-item">
                                            <i class="fas fa-map-marker-alt me-2"></i> Adres:
                                            <div class="text-muted mt-1"><?php echo $parent['adres'] ?: '-'; ?></div>
                                        </li>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                        <p>Veli bilgisi bulunamadı.</p>
                                        <a href="veli_ekle.php?ogrenci_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-plus-circle me-1"></i> Veli Ekle
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- Yoklama İstatistikleri -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-clipboard-check me-1"></i> Yoklama İstatistikleri
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-6 text-center mb-3">
                                        <div class="p-3 rounded bg-success text-white">
                                            <h3><?php echo $stats['var_sayisi'] ?: 0; ?></h3>
                                            <p class="mb-0">Var</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 text-center mb-3">
                                        <div class="p-3 rounded bg-danger text-white">
                                            <h3><?php echo $stats['yok_sayisi'] ?: 0; ?></h3>
                                            <p class="mb-0">Yok</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 text-center mb-3">
                                        <div class="p-3 rounded bg-warning text-white">
                                            <h3><?php echo $stats['izinli_sayisi'] ?: 0; ?></h3>
                                            <p class="mb-0">İzinli</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 text-center mb-3">
                                        <div class="p-3 rounded bg-info text-white">
                                            <h3><?php echo $stats['gec_sayisi'] ?: 0; ?></h3>
                                            <p class="mb-0">Geç</p>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($stats['toplam'] > 0): ?>
                                    <div class="mt-3">
                                        <div class="progress" style="height: 25px;">
                                            <?php
                                            $varYuzde = $stats['toplam'] > 0 ? ($stats['var_sayisi'] / $stats['toplam'] * 100) : 0;
                                            $izinliYuzde = $stats['toplam'] > 0 ? ($stats['izinli_sayisi'] / $stats['toplam'] * 100) : 0;
                                            $gecYuzde = $stats['toplam'] > 0 ? ($stats['gec_sayisi'] / $stats['toplam'] * 100) : 0;
                                            $yokYuzde = $stats['toplam'] > 0 ? ($stats['yok_sayisi'] / $stats['toplam'] * 100) : 0;
                                            ?>
                                            <div class="progress-bar bg-success" style="width: <?php echo $varYuzde; ?>%" title="Var: <?php echo round($varYuzde, 1); ?>%"></div>
                                            <div class="progress-bar bg-warning" style="width: <?php echo $izinliYuzde; ?>%" title="İzinli: <?php echo round($izinliYuzde, 1); ?>%"></div>
                                            <div class="progress-bar bg-info" style="width: <?php echo $gecYuzde; ?>%" title="Geç: <?php echo round($gecYuzde, 1); ?>%"></div>
                                            <div class="progress-bar bg-danger" style="width: <?php echo $yokYuzde; ?>%" title="Yok: <?php echo round($yokYuzde, 1); ?>%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <small>Toplam <?php echo $stats['toplam']; ?> ders</small>
                                            <small>Devam Oranı: %<?php echo round($varYuzde + $gecYuzde, 1); ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-1"></i> Henüz yoklama kaydı bulunmamaktadır.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Ders Kayıtları -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-book-open me-1"></i> Ders Kayıtları
                            </div>
                            <div class="card-body">
                                <?php if ($classesResult->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Dönem</th>
                                                    <th>Ders</th>
                                                    <th>Sınıf</th>
                                                    <th>Öğretmen</th>
                                                    <th>Gün</th>
                                                    <th>Saat</th>
                                                    <th>Kayıt Tarihi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($class = $classesResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $class['donem_adi']; ?></td>
                                                        <td><?php echo $class['ders_adi']; ?></td>
                                                        <td><?php echo $class['sinif_adi']; ?></td>
                                                        <td><?php echo $class['ogretmen_adi']; ?></td>
                                                        <td><?php echo $class['gun']; ?></td>
                                                        <td>
                                                            <?php echo date('H:i', strtotime($class['baslangic_saat'])); ?> -
                                                            <?php echo date('H:i', strtotime($class['bitis_saat'])); ?>
                                                        </td>
                                                        <td><?php echo formatDateTime($class['kayit_tarihi']); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Öğrencinin herhangi bir ders kaydı bulunmamaktadır.
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <a href="sinif_kayit.php?ogrenci_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus-circle me-1"></i> Yeni Ders Kaydı
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Yoklama Kayıtları -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <i class="fas fa-clipboard-list me-1"></i> Son Yoklama Kayıtları
                            </div>
                            <div class="card-body">
                                <?php if ($attendanceResult->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>Ders</th>
                                                    <th>Sınıf</th>
                                                    <th>Durum</th>
                                                    <th>Açıklama</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($attendance = $attendanceResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo formatDate($attendance['ders_tarihi']); ?></td>
                                                        <td><?php echo $attendance['ders_adi']; ?></td>
                                                        <td><?php echo $attendance['sinif_adi']; ?></td>
                                                        <td><?php echo formatAttendance($attendance['durum']); ?></td>
                                                        <td><?php echo $attendance['aciklama'] ?: '-'; ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-3">
                                        <a href="yoklama_rapor.php?ogrenci_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-list me-1"></i> Tüm Yoklama Kayıtları
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Henüz yoklama kaydı bulunmamaktadır.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Ödeme Kayıtları -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-money-bill-wave me-1"></i> Ödeme Kayıtları
                                <span class="badge bg-light text-dark ms-2">
                                    Toplam: <?php echo formatMoney($totalPayment); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <?php if ($paymentsResult->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Tarih</th>
                                                    <th>Dönem</th>
                                                    <th>Tutar</th>
                                                    <th>Ödeme Türü</th>
                                                    <th>Açıklama</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($payment = $paymentsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo formatDateTime($payment['odeme_tarihi']); ?></td>
                                                        <td><?php echo $payment['donem_adi'] ?: '-'; ?></td>
                                                        <td><?php echo formatMoney($payment['miktar']); ?></td>
                                                        <td><?php echo $payment['odeme_turu']; ?></td>
                                                        <td><?php echo $payment['aciklama'] ?: '-'; ?></td>
                                                        <td>
                                                            <a href="odeme_detay.php?id=<?php echo $payment['odeme_id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Henüz ödeme kaydı bulunmamaktadır.
                                    </div>
                                <?php endif; ?>

                                <div class="mt-3">
                                    <a href="odeme_ekle.php?ogrenci_id=<?php echo $id; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-plus-circle me-1"></i> Yeni Ödeme Ekle
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