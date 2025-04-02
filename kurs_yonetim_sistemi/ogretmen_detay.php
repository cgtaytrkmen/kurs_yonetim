<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Öğretmen ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz öğretmen ID.');
    header("Location: ogretmenler.php");
    exit;
}

// Öğretmen bilgilerini al
$teacherQuery = "SELECT * FROM ogretmenler WHERE ogretmen_id = $id";
$teacherResult = $conn->query($teacherQuery);

if ($teacherResult->num_rows === 0) {
    setMessage('error', 'Öğretmen bulunamadı.');
    header("Location: ogretmenler.php");
    exit;
}

$teacher = $teacherResult->fetch_assoc();

// Ders programlarını al
$programsQuery = "SELECT dp.*, d.ders_adi, s.sinif_adi, dm.donem_adi
                 FROM ders_programi dp
                 JOIN dersler d ON dp.ders_id = d.ders_id
                 JOIN siniflar s ON dp.sinif_id = s.sinif_id
                 JOIN donemler dm ON dp.donem_id = dm.donem_id
                 WHERE dp.ogretmen_id = $id
                 ORDER BY dm.donem_adi, dp.gun, dp.baslangic_saat";
$programsResult = $conn->query($programsQuery);

// Verilen dersler için istatistikler
$statsQuery = "SELECT
                COUNT(DISTINCT dp.program_id) as total_programs,
                COUNT(DISTINCT dp.ders_id) as total_courses,
                COUNT(DISTINCT dp.sinif_id) as total_classes,
                (
                    SELECT COUNT(DISTINCT sk.ogrenci_id)
                    FROM sinif_kayitlari sk
                    JOIN ders_programi dp2 ON sk.program_id = dp2.program_id
                    WHERE dp2.ogretmen_id = $id
                ) as total_students
              FROM ders_programi dp
              WHERE dp.ogretmen_id = $id";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğretmen Detayı - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Öğretmen Detayı</h2>
                    <div>
                        <a href="ogretmen_duzenle.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Düzenle
                        </a>
                        <a href="ogretmenler.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-arrow-left me-1"></i> Listeye Dön
                        </a>
                    </div>
                </div>

                <?php echo showMessage(); ?>

                <div class="row">
                    <!-- Öğretmen Profil Bilgileri -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-chalkboard-teacher me-1"></i> Öğretmen Bilgileri
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="mb-3">
                                        <i class="fas fa-user-circle fa-6x text-primary"></i>
                                    </div>
                                    <h4><?php echo $teacher['ad'] . ' ' . $teacher['soyad']; ?></h4>
                                    <p class="text-muted">Öğretmen ID: <?php echo $teacher['ogretmen_id']; ?></p>
                                    <div class="mt-2"><?php echo formatStatus($teacher['durum']); ?></div>
                                </div>

                                <ul class="list-group list-group-flush">
                                    <?php if (!empty($teacher['uzmanlik_alani'])): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-graduation-cap me-2"></i> Uzmanlık Alanı:</span>
                                            <span class="text-muted"><?php echo $teacher['uzmanlik_alani']; ?></span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-phone me-2"></i> Telefon:</span>
                                        <span class="text-muted"><?php echo $teacher['telefon'] ?: '-'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-envelope me-2"></i> E-posta:</span>
                                        <span class="text-muted"><?php echo $teacher['email'] ?: '-'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-calendar-alt me-2"></i> Başlangıç Tarihi:</span>
                                        <span class="text-muted"><?php echo $teacher['baslangic_tarihi'] ? formatDate($teacher['baslangic_tarihi']) : '-'; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- İstatistikler -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-chart-pie me-1"></i> İstatistikler
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="border rounded p-3">
                                            <h4 class="mb-0"><?php echo $stats['total_programs'] ?: 0; ?></h4>
                                            <small class="text-muted">Program</small>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="border rounded p-3">
                                            <h4 class="mb-0"><?php echo $stats['total_courses'] ?: 0; ?></h4>
                                            <small class="text-muted">Ders</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-3">
                                            <h4 class="mb-0"><?php echo $stats['total_classes'] ?: 0; ?></h4>
                                            <small class="text-muted">Sınıf</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-3">
                                            <h4 class="mb-0"><?php echo $stats['total_students'] ?: 0; ?></h4>
                                            <small class="text-muted">Öğrenci</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <!-- Ders Programları -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-calendar-week me-1"></i> Ders Programları
                            </div>
                            <div class="card-body">
                                <?php if ($programsResult->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Dönem</th>
                                                    <th>Ders</th>
                                                    <th>Sınıf</th>
                                                    <th>Gün</th>
                                                    <th>Saat</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($program = $programsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo $program['donem_adi']; ?></td>
                                                        <td><?php echo $program['ders_adi']; ?></td>
                                                        <td><?php echo $program['sinif_adi']; ?></td>
                                                        <td><?php echo $program['gun']; ?></td>
                                                        <td>
                                                            <?php echo date('H:i', strtotime($program['baslangic_saat'])); ?> -
                                                            <?php echo date('H:i', strtotime($program['bitis_saat'])); ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="yoklama.php?program_id=<?php echo $program['program_id']; ?>" class="btn btn-success" title="Yoklama">
                                                                    <i class="fas fa-clipboard-check"></i>
                                                                </a>
                                                                <a href="sinif_kayit.php?program_id=<?php echo $program['program_id']; ?>" class="btn btn-info" title="Öğrenci Listesi">
                                                                    <i class="fas fa-users"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Bu öğretmene ait ders programı bulunmamaktadır.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Haftalık Ders Programı -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <i class="fas fa-calendar-alt me-1"></i> Haftalık Ders Programı
                            </div>
                            <div class="card-body">
                                <?php
                                // Programları günlere göre grupla
                                $programData = [];
                                $gunler = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

                                foreach ($gunler as $g) {
                                    $programData[$g] = [];
                                }

                                // Verileri yeniden sorgula
                                $programsResult->data_seek(0);
                                while ($program = $programsResult->fetch_assoc()) {
                                    $programData[$program['gun']][] = $program;
                                }
                                ?>

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <?php foreach ($gunler as $g): ?>
                                                    <th class="text-center"><?php echo $g; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <?php foreach ($gunler as $g): ?>
                                                    <td class="align-top p-2" style="min-width: 150px; height: 200px;">
                                                        <?php if (!empty($programData[$g])): ?>
                                                            <?php foreach ($programData[$g] as $p): ?>
                                                                <div class="mb-2 p-2 bg-light border rounded">
                                                                    <div class="small fw-bold"><?php echo date('H:i', strtotime($p['baslangic_saat'])); ?> - <?php echo date('H:i', strtotime($p['bitis_saat'])); ?></div>
                                                                    <div class="small"><?php echo $p['ders_adi']; ?></div>
                                                                    <div class="small text-muted"><?php echo $p['sinif_adi']; ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="text-center text-muted small py-3">Ders yok</div>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        </tbody>
                                    </table>
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