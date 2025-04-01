<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Veritabanından özet bilgilerini al
$totalStudents = getCount($conn, "ogrenciler", "durum = 'Aktif'");
$totalTeachers = getCount($conn, "ogretmenler", "durum = 'Aktif'");
$totalClasses = getCount($conn, "siniflar");
$totalCourses = getCount($conn, "dersler");

// Aktif dönem bilgisi
$activeTermQuery = "SELECT * FROM donemler WHERE durum = 'Aktif' ORDER BY baslangic_tarihi DESC LIMIT 1";
$activeTermResult = $conn->query($activeTermQuery);
$activeTerm = $activeTermResult->fetch_assoc();

// Son kayıt olan öğrenciler
$recentStudentsQuery = "SELECT * FROM ogrenciler ORDER BY kayit_tarihi DESC LIMIT 5";
$recentStudentsResult = $conn->query($recentStudentsQuery);

// Bugünkü dersler
$today = date('Y-m-d');
$dayOfWeek = date('l'); // İngilizce gün adı
$turkishDays = [
    'Monday' => 'Pazartesi',
    'Tuesday' => 'Salı',
    'Wednesday' => 'Çarşamba',
    'Thursday' => 'Perşembe',
    'Friday' => 'Cuma',
    'Saturday' => 'Cumartesi',
    'Sunday' => 'Pazar'
];
$turkishDay = $turkishDays[$dayOfWeek];

$todayClassesQuery = "SELECT dp.*, d.ders_adi, s.sinif_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi 
                     FROM ders_programi dp
                     JOIN dersler d ON dp.ders_id = d.ders_id
                     JOIN siniflar s ON dp.sinif_id = s.sinif_id
                     JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                     WHERE dp.gun = '$turkishDay'
                     ORDER BY dp.baslangic_saat";
$todayClassesResult = $conn->query($todayClassesQuery);

// Aylık tahsilat ve giderler için veri
$currentMonth = date('m');
$currentYear = date('Y');
$incomeQuery = "SELECT SUM(miktar) as toplam FROM odemeler WHERE MONTH(odeme_tarihi) = $currentMonth AND YEAR(odeme_tarihi) = $currentYear";
$incomeResult = $conn->query($incomeQuery);
$income = $incomeResult->fetch_assoc()['toplam'] ?: 0;

$expenseQuery = "SELECT SUM(miktar) as toplam FROM giderler WHERE MONTH(gider_tarihi) = $currentMonth AND YEAR(gider_tarihi) = $currentYear";
$expenseResult = $conn->query($expenseQuery);
$expense = $expenseResult->fetch_assoc()['toplam'] ?: 0;
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurs Yönetim Sistemi - Ana Sayfa</title>
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
                <h2 class="mt-4 mb-4">Ana Sayfa</h2>

                <!-- Bilgi Kartları -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $totalStudents; ?></h5>
                                        <div class="small">Toplam Öğrenci</div>
                                    </div>
                                    <div><i class="fas fa-user-graduate fa-2x"></i></div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="ogrenciler.php">Detayları Görüntüle</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $totalTeachers; ?></h5>
                                        <div class="small">Toplam Öğretmen</div>
                                    </div>
                                    <div><i class="fas fa-chalkboard-teacher fa-2x"></i></div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="ogretmenler.php">Detayları Görüntüle</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $totalClasses; ?></h5>
                                        <div class="small">Toplam Sınıf</div>
                                    </div>
                                    <div><i class="fas fa-door-open fa-2x"></i></div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="siniflar.php">Detayları Görüntüle</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-warning text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?php echo $totalCourses; ?></h5>
                                        <div class="small">Toplam Ders</div>
                                    </div>
                                    <div><i class="fas fa-book fa-2x"></i></div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="dersler.php">Detayları Görüntüle</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aktif Dönem -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-calendar-alt me-1"></i> Aktif Dönem
                            </div>
                            <div class="card-body">
                                <?php if ($activeTerm): ?>
                                    <h5 class="card-title"><?php echo $activeTerm['donem_adi']; ?></h5>
                                    <p class="card-text">
                                        Başlangıç: <?php echo formatDate($activeTerm['baslangic_tarihi']); ?><br>
                                        Bitiş: <?php echo formatDate($activeTerm['bitis_tarihi']); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="card-text text-center">Aktif dönem bulunmamaktadır.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mali Durum Özeti -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-chart-pie me-1"></i> Aylık Mali Durum
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-6 mb-3">
                                        <div class="bg-success text-white p-3 rounded">
                                            <h5>Gelir</h5>
                                            <h3><?php echo number_format($income, 2, ',', '.'); ?> ₺</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="bg-danger text-white p-3 rounded">
                                            <h5>Gider</h5>
                                            <h3><?php echo number_format($expense, 2, ',', '.'); ?> ₺</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <h5>Net Kâr/Zarar:
                                        <span class="<?php echo ($income - $expense >= 0) ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo number_format($income - $expense, 2, ',', '.'); ?> ₺
                                        </span>
                                    </h5>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="raporlar.php?type=mali" class="btn btn-sm btn-outline-primary">Detaylı Rapor</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-user-plus me-1"></i> Son Kayıt Olan Öğrenciler
                            </div>
                            <div class="card-body">
                                <?php if ($recentStudentsResult->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Adı Soyadı</th>
                                                    <th>Kayıt Tarihi</th>
                                                    <th>İşlem</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($student = $recentStudentsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $student['ad'] . ' ' . $student['soyad']; ?></td>
                                                        <td><?php echo formatDateTime($student['kayit_tarihi']); ?></td>
                                                        <td>
                                                            <a href="ogrenci_detay.php?id=<?php echo $student['ogrenci_id']; ?>" class="btn btn-sm btn-outline-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">Henüz öğrenci kaydı bulunmamaktadır.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bugünkü Dersler -->
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-calendar-day me-1"></i> Bugünkü Dersler (<?php echo $turkishDay; ?>)
                            </div>
                            <div class="card-body">
                                <?php if ($todayClassesResult->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Ders</th>
                                                    <th>Sınıf</th>
                                                    <th>Öğretmen</th>
                                                    <th>Başlangıç</th>
                                                    <th>Bitiş</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($class = $todayClassesResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $class['ders_adi']; ?></td>
                                                        <td><?php echo $class['sinif_adi']; ?></td>
                                                        <td><?php echo $class['ogretmen_adi']; ?></td>
                                                        <td><?php echo date('H:i', strtotime($class['baslangic_saat'])); ?></td>
                                                        <td><?php echo date('H:i', strtotime($class['bitis_saat'])); ?></td>
                                                        <td>
                                                            <a href="yoklama.php?program_id=<?php echo $class['program_id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-clipboard-list"></i> Yoklama
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">Bugün ders programı bulunmamaktadır.</p>
                                <?php endif; ?>
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