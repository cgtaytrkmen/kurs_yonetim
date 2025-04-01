<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Filtreleme parametreleri
$donem_id = isset($_GET['donem_id']) ? (int)$_GET['donem_id'] : 0;
$gun = isset($_GET['gun']) ? $_GET['gun'] : '';

// Dönemleri al
$donemsQuery = "SELECT * FROM donemler ORDER BY baslangic_tarihi DESC";
$donemsResult = $conn->query($donemsQuery);

// Günler
$gunler = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

// Ders programı
$programQuery = "SELECT dp.*, d.ders_adi, s.sinif_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dm.donem_adi
                FROM ders_programi dp
                JOIN dersler d ON dp.ders_id = d.ders_id
                JOIN siniflar s ON dp.sinif_id = s.sinif_id
                JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                JOIN donemler dm ON dp.donem_id = dm.donem_id
                WHERE 1";

// Filtreler
if ($donem_id > 0) {
    $programQuery .= " AND dp.donem_id = $donem_id";
}

if (!empty($gun)) {
    $programQuery .= " AND dp.gun = '$gun'";
}

$programQuery .= " ORDER BY FIELD(dp.gun, 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'), dp.baslangic_saat";

$programResult = $conn->query($programQuery);

// Program silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $id = (int)$_GET['delete'];

    // Sınıf kayıtları var mı?
    $checkQuery = "SELECT COUNT(*) as count FROM sinif_kayitlari WHERE program_id = $id";
    $checkResult = $conn->query($checkQuery);
    $hasStudents = $checkResult->fetch_assoc()['count'] > 0;

    // Yoklama kayıtları var mı?
    $checkAttendanceQuery = "SELECT COUNT(*) as count FROM yoklamalar WHERE program_id = $id";
    $checkAttendanceResult = $conn->query($checkAttendanceQuery);
    $hasAttendance = $checkAttendanceResult->fetch_assoc()['count'] > 0;

    if ($hasStudents || $hasAttendance) {
        setMessage('error', 'Bu programa ait öğrenci veya yoklama kayıtları bulunmaktadır. Önce bu kayıtları silmelisiniz.');
    } else {
        $deleteQuery = "DELETE FROM ders_programi WHERE program_id = $id";

        if ($conn->query($deleteQuery)) {
            setMessage('success', 'Program başarıyla silindi.');
        } else {
            setMessage('error', 'Program silinirken bir hata oluştu: ' . $conn->error);
        }
    }

    // Sayfayı yenile
    header("Location: ders_programi.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ders Programı - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Ders Programı</h2>
                    <a href="program_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Program
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <!-- Filtreler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter me-1"></i> Program Filtreleri
                    </div>
                    <div class="card-body">
                        <form method="GET" action="ders_programi.php" class="row g-3">
                            <div class="col-md-5">
                                <label for="donem_id" class="form-label">Dönem</label>
                                <select class="form-select" id="donem_id" name="donem_id">
                                    <option value="0">Tümü</option>
                                    <?php while ($donem = $donemsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $donem['donem_id']; ?>" <?php echo ($donem_id == $donem['donem_id']) ? 'selected' : ''; ?>>
                                            <?php echo $donem['donem_adi']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label for="gun" class="form-label">Gün</label>
                                <select class="form-select" id="gun" name="gun">
                                    <option value="">Tümü</option>
                                    <?php foreach ($gunler as $g): ?>
                                        <option value="<?php echo $g; ?>" <?php echo ($gun == $g) ? 'selected' : ''; ?>>
                                            <?php echo $g; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Filtrele
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Program Listesi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-calendar-week me-1"></i> Ders Programı Listesi
                        <span class="badge bg-primary ms-2"><?php echo $programResult->num_rows; ?> kayıt</span>
                    </div>
                    <div class="card-body">
                        <?php if ($programResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Dönem</th>
                                            <th>Gün</th>
                                            <th>Saat</th>
                                            <th>Ders</th>
                                            <th>Sınıf</th>
                                            <th>Öğretmen</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($program = $programResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $program['donem_adi']; ?></td>
                                                <td><?php echo $program['gun']; ?></td>
                                                <td>
                                                    <?php echo date('H:i', strtotime($program['baslangic_saat'])); ?> -
                                                    <?php echo date('H:i', strtotime($program['bitis_saat'])); ?>
                                                </td>
                                                <td><?php echo $program['ders_adi']; ?></td>
                                                <td><?php echo $program['sinif_adi']; ?></td>
                                                <td><?php echo $program['ogretmen_adi']; ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="yoklama.php?program_id=<?php echo $program['program_id']; ?>" class="btn btn-success" title="Yoklama">
                                                            <i class="fas fa-clipboard-check"></i>
                                                        </a>
                                                        <a href="sinif_kayit.php?program_id=<?php echo $program['program_id']; ?>" class="btn btn-info" title="Öğrenci Kayıt">
                                                            <i class="fas fa-user-plus"></i>
                                                        </a>
                                                        <a href="program_duzenle.php?id=<?php echo $program['program_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                                                            <a href="#" class="btn btn-danger" title="Sil" onclick="return confirm('Bu programı silmek istediğinize emin misiniz?') ? window.location.href='ders_programi.php?delete=<?php echo $program['program_id']; ?>' : false">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i> Seçilen kriterlere uygun ders programı bulunamadı.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Haftalık Program -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-calendar-alt me-1"></i> Haftalık Görünüm
                    </div>
                    <div class="card-body">
                        <?php
                        // Programları günlere göre grupla
                        $programData = [];
                        foreach ($gunler as $g) {
                            $programData[$g] = [];
                        }

                        $programResult->data_seek(0);
                        while ($program = $programResult->fetch_assoc()) {
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
                                            <td class="align-top p-2" style="min-width: 150px; height: 300px;">
                                                <?php if (!empty($programData[$g])): ?>
                                                    <?php foreach ($programData[$g] as $p): ?>
                                                        <div class="mb-2 p-2 bg-light border rounded">
                                                            <div class="small fw-bold"><?php echo date('H:i', strtotime($p['baslangic_saat'])); ?> - <?php echo date('H:i', strtotime($p['bitis_saat'])); ?></div>
                                                            <div class="small"><?php echo $p['ders_adi']; ?></div>
                                                            <div class="small text-muted"><?php echo $p['sinif_adi']; ?></div>
                                                            <div class="small text-muted"><?php echo $p['ogretmen_adi']; ?></div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>