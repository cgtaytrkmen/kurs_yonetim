<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Tarih aralığı için varsayılan değerler
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Ayın başlangıcı
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Ayın sonu

// Rapor türü
$report_type = isset($_GET['type']) ? $_GET['type'] : 'genel';
$valid_report_types = ['genel', 'mali', 'yoklama', 'ogrenci'];

if (!in_array($report_type, $valid_report_types)) {
    $report_type = 'genel';
}

// Genel istatistikler
$totalStudentsQuery = "SELECT COUNT(*) as total FROM ogrenciler WHERE durum = 'Aktif'";
$totalStudentsResult = $conn->query($totalStudentsQuery);
$totalStudents = $totalStudentsResult->fetch_assoc()['total'];

$totalTeachersQuery = "SELECT COUNT(*) as total FROM ogretmenler WHERE durum = 'Aktif'";
$totalTeachersResult = $conn->query($totalTeachersQuery);
$totalTeachers = $totalTeachersResult->fetch_assoc()['total'];

$totalClassesQuery = "SELECT COUNT(*) as total FROM siniflar";
$totalClassesResult = $conn->query($totalClassesQuery);
$totalClasses = $totalClassesResult->fetch_assoc()['total'];

$totalCoursesQuery = "SELECT COUNT(*) as total FROM dersler";
$totalCoursesResult = $conn->query($totalCoursesQuery);
$totalCourses = $totalCoursesResult->fetch_assoc()['total'];

// Mali raporlar
$incomeQuery = "SELECT SUM(miktar) as toplam FROM odemeler WHERE odeme_tarihi BETWEEN '$start_date' AND '$end_date'";
$incomeResult = $conn->query($incomeQuery);
$totalIncome = $incomeResult->fetch_assoc()['toplam'] ?: 0;

$expenseQuery = "SELECT SUM(miktar) as toplam FROM giderler WHERE gider_tarihi BETWEEN '$start_date' AND '$end_date'";
$expenseResult = $conn->query($expenseQuery);
$totalExpense = $expenseResult->fetch_assoc()['toplam'] ?: 0;

// Gelir dağılımı (ödeme türlerine göre)
$incomeDistributionQuery = "SELECT odeme_turu, SUM(miktar) as toplam FROM odemeler 
                           WHERE odeme_tarihi BETWEEN '$start_date' AND '$end_date' 
                           GROUP BY odeme_turu ORDER BY toplam DESC";
$incomeDistributionResult = $conn->query($incomeDistributionQuery);

// Gider dağılımı
$expenseDistributionQuery = "SELECT gider_turu, SUM(miktar) as toplam FROM giderler 
                            WHERE gider_tarihi BETWEEN '$start_date' AND '$end_date' 
                            GROUP BY gider_turu ORDER BY toplam DESC";
$expenseDistributionResult = $conn->query($expenseDistributionQuery);

// Yoklama istatistikleri
$attendanceQuery = "SELECT 
                    SUM(CASE WHEN yd.durum = 'Var' THEN 1 ELSE 0 END) as var_sayisi,
                    SUM(CASE WHEN yd.durum = 'Yok' THEN 1 ELSE 0 END) as yok_sayisi,
                    SUM(CASE WHEN yd.durum = 'İzinli' THEN 1 ELSE 0 END) as izinli_sayisi,
                    SUM(CASE WHEN yd.durum = 'Geç' THEN 1 ELSE 0 END) as gec_sayisi,
                    COUNT(*) as toplam
                FROM yoklama_detay yd
                JOIN yoklamalar y ON yd.yoklama_id = y.yoklama_id
                WHERE y.ders_tarihi BETWEEN '$start_date' AND '$end_date'";
$attendanceResult = $conn->query($attendanceQuery);
$attendance = $attendanceResult->fetch_assoc();

// Öğrenci istatistikleri
$studentGenderDistributionQuery = "SELECT cinsiyet, COUNT(*) as toplam FROM ogrenciler 
                                 WHERE durum = 'Aktif'
                                 GROUP BY cinsiyet";
$studentGenderDistributionResult = $conn->query($studentGenderDistributionQuery);

// Yeni kayıt olan öğrenciler (son bir ay)
$newStudentsQuery = "SELECT COUNT(*) as total FROM ogrenciler 
                    WHERE kayit_tarihi BETWEEN '$start_date' AND '$end_date'";
$newStudentsResult = $conn->query($newStudentsQuery);
$newStudents = $newStudentsResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporlar - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Raporlar</h2>
                    <div>
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Yazdır
                        </button>
                    </div>
                </div>

                <!-- Rapor türü seçimi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter me-1"></i> Rapor Türü ve Tarih Aralığı
                    </div>
                    <div class="card-body">
                        <form method="GET" action="raporlar.php" class="row g-3">
                            <div class="col-md-3">
                                <label for="type" class="form-label">Rapor Türü</label>
                                <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                                    <option value="genel" <?php echo ($report_type == 'genel') ? 'selected' : ''; ?>>Genel İstatistikler</option>
                                    <option value="mali" <?php echo ($report_type == 'mali') ? 'selected' : ''; ?>>Mali Raporlar</option>
                                    <option value="yoklama" <?php echo ($report_type == 'yoklama') ? 'selected' : ''; ?>>Yoklama İstatistikleri</option>
                                    <option value="ogrenci" <?php echo ($report_type == 'ogrenci') ? 'selected' : ''; ?>>Öğrenci İstatistikleri</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sync-alt me-1"></i> Raporu Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Genel İstatistikler -->
                <?php if ($report_type == 'genel'): ?>
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="mb-0"><?php echo $totalStudents; ?></h2>
                                            <div>Toplam Öğrenci</div>
                                        </div>
                                        <div><i class="fas fa-user-graduate fa-3x"></i></div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="ogrenciler.php">Detayları Görüntüle</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="mb-0"><?php echo $totalTeachers; ?></h2>
                                            <div>Toplam Öğretmen</div>
                                        </div>
                                        <div><i class="fas fa-chalkboard-teacher fa-3x"></i></div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="ogretmenler.php">Detayları Görüntüle</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="mb-0"><?php echo $totalClasses; ?></h2>
                                            <div>Toplam Sınıf</div>
                                        </div>
                                        <div><i class="fas fa-door-open fa-3x"></i></div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="siniflar.php">Detayları Görüntüle</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card bg-warning text-white h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h2 class="mb-0"><?php echo $totalCourses; ?></h2>
                                            <div>Toplam Ders</div>
                                        </div>
                                        <div><i class="fas fa-book fa-3x"></i></div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="dersler.php">Detayları Görüntüle</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="fas fa-chart-bar me-1"></i> Yeni Kayıtlar
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">Seçilen Tarih Aralığında Yeni Kayıt Olan Öğrenciler</h5>
                                    <h1 class="display-4 text-center"><?php echo $newStudents; ?></h1>
                                    <p class="card-text text-center">
                                        <?php echo formatDate($start_date); ?> - <?php echo formatDate($end_date); ?> tarihleri arasında
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="fas fa-money-bill-wave me-1"></i> Mali Durum Özeti
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-6 mb-3">
                                            <div class="bg-success text-white p-3 rounded">
                                                <h5>Gelir</h5>
                                                <h3><?php echo formatMoney($totalIncome); ?></h3>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="bg-danger text-white p-3 rounded">
                                                <h5>Gider</h5>
                                                <h3><?php echo formatMoney($totalExpense); ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-3">
                                        <h5>Net Kâr/Zarar:
                                            <span class="<?php echo ($totalIncome - $totalExpense >= 0) ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatMoney($totalIncome - $totalExpense); ?>
                                            </span>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Mali Raporlar -->
                <?php if ($report_type == 'mali'): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-chart-pie me-1"></i> Mali Durum Özeti
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">Toplam Gelir</h5>
                                                    <h1 class="text-success"><?php echo formatMoney($totalIncome); ?></h1>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">Toplam Gider</h5>
                                                    <h1 class="text-danger"><?php echo formatMoney($totalExpense); ?></h1>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">Net Kâr/Zarar</h5>
                                                    <h1 class="<?php echo ($totalIncome - $totalExpense >= 0) ? 'text-success' : 'text-danger'; ?>">
                                                        <?php echo formatMoney($totalIncome - $totalExpense); ?>
                                                    </h1>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-3">
                                        <p>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?php echo formatDate($start_date); ?> - <?php echo formatDate($end_date); ?> tarihleri arasındaki mali durum özeti
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="fas fa-chart-bar me-1"></i> Gelir Dağılımı (Ödeme Türlerine Göre)
                                </div>
                                <div class="card-body">
                                    <?php if ($incomeDistributionResult->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Ödeme Türü</th>
                                                        <th class="text-end">Toplam</th>
                                                        <th class="text-end">Oran</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = $incomeDistributionResult->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $row['odeme_turu']; ?></td>
                                                            <td class="text-end"><?php echo formatMoney($row['toplam']); ?></td>
                                                            <td class="text-end">
                                                                <?php echo $totalIncome > 0 ? number_format(($row['toplam'] / $totalIncome) * 100, 1) : 0; ?>%
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-primary">
                                                        <th>Toplam</th>
                                                        <th class="text-end"><?php echo formatMoney($totalIncome); ?></th>
                                                        <th class="text-end">100%</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> Seçilen tarih aralığında gelir kaydı bulunmamaktadır.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="fas fa-chart-bar me-1"></i> Gider Dağılımı (Gider Türlerine Göre)
                                </div>
                                <div class="card-body">
                                    <?php if ($expenseDistributionResult->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Gider Türü</th>
                                                        <th class="text-end">Toplam</th>
                                                        <th class="text-end">Oran</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = $expenseDistributionResult->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $row['gider_turu']; ?></td>
                                                            <td class="text-end"><?php echo formatMoney($row['toplam']); ?></td>
                                                            <td class="text-end">
                                                                <?php echo $totalExpense > 0 ? number_format(($row['toplam'] / $totalExpense) * 100, 1) : 0; ?>%
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-danger">
                                                        <th>Toplam</th>
                                                        <th class="text-end"><?php echo formatMoney($totalExpense); ?></th>
                                                        <th class="text-end">100%</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> Seçilen tarih aralığında gider kaydı bulunmamaktadır.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Yoklama İstatistikleri -->
                <?php if ($report_type == 'yoklama'): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-clipboard-check me-1"></i> Yoklama İstatistikleri
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <div class="p-3 rounded bg-success text-white">
                                                <h3><?php echo $attendance['var_sayisi'] ?: 0; ?></h3>
                                                <p class="mb-0">Var</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <div class="p-3 rounded bg-danger text-white">
                                                <h3><?php echo $attendance['yok_sayisi'] ?: 0; ?></h3>
                                                <p class="mb-0">Yok</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <div class="p-3 rounded bg-warning text-white">
                                                <h3><?php echo $attendance['izinli_sayisi'] ?: 0; ?></h3>
                                                <p class="mb-0">İzinli</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <div class="p-3 rounded bg-info text-white">
                                                <h3><?php echo $attendance['gec_sayisi'] ?: 0; ?></h3>
                                                <p class="mb-0">Geç</p>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($attendance['toplam'] > 0): ?>
                                        <div class="mt-4">
                                            <h5 class="text-center mb-3">Katılım Oranları</h5>
                                            <div class="progress" style="height: 30px;">
                                                <?php
                                                $total = $attendance['toplam'];
                                                $varYuzde = $total > 0 ? ($attendance['var_sayisi'] / $total * 100) : 0;
                                                $gecYuzde = $total > 0 ? ($attendance['gec_sayisi'] / $total * 100) : 0;
                                                $izinliYuzde = $total > 0 ? ($attendance['izinli_sayisi'] / $total * 100) : 0;
                                                $yokYuzde = $total > 0 ? ($attendance['yok_sayisi'] / $total * 100) : 0;
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
                                                <small>Toplam <?php echo $attendance['toplam']; ?> yoklama kaydı</small>
                                                <small>Devam Oranı: %<?php echo round($varYuzde + $gecYuzde, 1); ?></small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle me-1"></i> Seçilen tarih aralığında yoklama kaydı bulunmamaktadır.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Öğrenci İstatistikleri -->
                <?php if ($report_type == 'ogrenci'): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-users me-1"></i> Öğrenci Dağılımı (Cinsiyete Göre)
                                </div>
                                <div class="card-body">
                                    <?php if ($studentGenderDistributionResult->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Cinsiyet</th>
                                                        <th class="text-end">Öğrenci Sayısı</th>
                                                        <th class="text-end">Oran</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $total = 0;
                                                    $genderData = [];

                                                    while ($row = $studentGenderDistributionResult->fetch_assoc()) {
                                                        $genderData[] = $row;
                                                        $total += $row['toplam'];
                                                    }

                                                    foreach ($genderData as $row):
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $row['cinsiyet'] ?: 'Belirtilmemiş'; ?></td>
                                                            <td class="text-end"><?php echo $row['toplam']; ?></td>
                                                            <td class="text-end">
                                                                <?php echo $total > 0 ? number_format(($row['toplam'] / $total) * 100, 1) : 0; ?>%
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-primary">
                                                        <th>Toplam</th>
                                                        <th class="text-end"><?php echo $total; ?></th>
                                                        <th class="text-end">100%</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> Öğrenci kaydı bulunmamaktadır.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-user-plus me-1"></i> Yeni Kayıt İstatistikleri
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">Seçilen Tarih Aralığında Yeni Kayıt Sayısı</h5>
                                    <h1 class="display-3 text-center mb-4"><?php echo $newStudents; ?></h1>
                                    <p class="card-text text-center">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo formatDate($start_date); ?> - <?php echo formatDate($end_date); ?> tarihleri arasında
                                    </p>
                                    <div class="text-center mt-4">
                                        <a href="ogrenciler.php" class="btn btn-outline-primary">Tüm Öğrencileri Görüntüle</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>