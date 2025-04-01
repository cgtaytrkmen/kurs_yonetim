<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Program ID veya Öğrenci ID'yi al
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$ogrenci_id = isset($_GET['ogrenci_id']) ? (int)$_GET['ogrenci_id'] : 0;

// Program veya öğrenci belirlenmemişse
if ($program_id <= 0 && $ogrenci_id <= 0) {
    setMessage('error', 'Program veya öğrenci ID gereklidir.');
    header("Location: ders_programi.php");
    exit;
}

// Program bilgilerini al
$program = null;
if ($program_id > 0) {
    $programQuery = "SELECT dp.*, d.ders_adi, s.sinif_adi, s.kapasite, 
                           CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dm.donem_adi
                    FROM ders_programi dp
                    JOIN dersler d ON dp.ders_id = d.ders_id
                    JOIN siniflar s ON dp.sinif_id = s.sinif_id
                    JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                    JOIN donemler dm ON dp.donem_id = dm.donem_id
                    WHERE dp.program_id = $program_id";
    $programResult = $conn->query($programQuery);

    if ($programResult->num_rows === 0) {
        setMessage('error', 'Program bulunamadı.');
        header("Location: ders_programi.php");
        exit;
    }

    $program = $programResult->fetch_assoc();
}

// Öğrenci bilgilerini al
$ogrenci = null;
if ($ogrenci_id > 0) {
    $ogrenciQuery = "SELECT * FROM ogrenciler WHERE ogrenci_id = $ogrenci_id";
    $ogrenciResult = $conn->query($ogrenciQuery);

    if ($ogrenciResult->num_rows === 0) {
        setMessage('error', 'Öğrenci bulunamadı.');
        header("Location: ogrenciler.php");
        exit;
    }

    $ogrenci = $ogrenciResult->fetch_assoc();
}

// Seçilen öğrencilerin listesi (program_id varsa)
$selectedStudentsQuery = "SELECT o.*, sk.kayit_id
                         FROM ogrenciler o
                         JOIN sinif_kayitlari sk ON o.ogrenci_id = sk.ogrenci_id
                         WHERE sk.program_id = '$program_id' AND o.durum = 'Aktif'
                         ORDER BY o.ad, o.soyad";
$selectedStudentsResult = $conn->query($selectedStudentsQuery);

// Mevcut sınıf mevcudu
$currentCount = $selectedStudentsResult->num_rows;

// Öğrenci listesi (ogrenci_id varsa)
$programsQuery = "SELECT dp.*, d.ders_adi, s.sinif_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dm.donem_adi,
                        (SELECT COUNT(*) FROM sinif_kayitlari sk WHERE sk.program_id = dp.program_id) as mevcut,
                        (SELECT COUNT(*) FROM sinif_kayitlari sk WHERE sk.program_id = dp.program_id AND sk.ogrenci_id = $ogrenci_id) as kayitli
                 FROM ders_programi dp
                 JOIN dersler d ON dp.ders_id = d.ders_id
                 JOIN siniflar s ON dp.sinif_id = s.sinif_id
                 JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                 JOIN donemler dm ON dp.donem_id = dm.donem_id
                 WHERE dm.durum = 'Aktif'
                 ORDER BY dm.donem_adi, dp.gun, dp.baslangic_saat";
$programsResult = $ogrenci_id > 0 ? $conn->query($programsQuery) : null;

// Kayıtlı olduğu programlar
$enrolledProgramsQuery = "SELECT dp.*, d.ders_adi, s.sinif_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dm.donem_adi,
                             sk.kayit_id, sk.kayit_tarihi
                         FROM sinif_kayitlari sk
                         JOIN ders_programi dp ON sk.program_id = dp.program_id
                         JOIN dersler d ON dp.ders_id = d.ders_id
                         JOIN siniflar s ON dp.sinif_id = s.sinif_id
                         JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                         JOIN donemler dm ON dp.donem_id = dm.donem_id
                         WHERE sk.ogrenci_id = $ogrenci_id
                         ORDER BY dm.donem_adi, dp.gun, dp.baslangic_saat";
$enrolledProgramsResult = $ogrenci_id > 0 ? $conn->query($enrolledProgramsQuery) : null;

// Aktif öğrenciler (kayıt olmamış olanlar)
$availableStudentsQuery = "SELECT * FROM ogrenciler 
                         WHERE durum = 'Aktif' AND ogrenci_id NOT IN 
                         (SELECT ogrenci_id FROM sinif_kayitlari WHERE program_id = $program_id)
                         ORDER BY ad, soyad";
$availableStudentsResult = $program_id > 0 ? $conn->query($availableStudentsQuery) : null;

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // İşlem türü
    $action = post('action');

    if ($action === 'add_students' && $program_id > 0) {
        // Öğrenci ekleme
        if (isset($_POST['students']) && is_array($_POST['students'])) {
            $addedCount = 0;

            // Sınıf kapasitesi kontrolü
            if ($program['kapasite'] > 0 && ($currentCount + count($_POST['students'])) > $program['kapasite']) {
                $error = "Bu sınıfın kapasitesi {$program['kapasite']} öğrenci ile sınırlıdır. Şu anda {$currentCount} öğrenci kayıtlıdır.";
            } else {
                foreach ($_POST['students'] as $student_id) {
                    $student_id = (int)$student_id;

                    // Öğrencinin bu programa zaten kayıtlı olup olmadığını kontrol et
                    $checkQuery = "SELECT COUNT(*) as count FROM sinif_kayitlari WHERE ogrenci_id = $student_id AND program_id = $program_id";
                    $checkResult = $conn->query($checkQuery);
                    $isRegistered = $checkResult->fetch_assoc()['count'] > 0;

                    if (!$isRegistered) {
                        $insertQuery = "INSERT INTO sinif_kayitlari (ogrenci_id, program_id) VALUES ($student_id, $program_id)";

                        if ($conn->query($insertQuery)) {
                            $addedCount++;
                        }
                    }
                }

                if ($addedCount > 0) {
                    $success = "{$addedCount} öğrenci programa başarıyla eklendi.";
                } else {
                    $error = "Öğrenci eklenirken bir hata oluştu veya seçilen öğrenciler zaten kayıtlı.";
                }

                // Sayfayı yenile
                header("Location: sinif_kayit.php?program_id=$program_id");
                exit;
            }
        } else {
            $error = "Lütfen en az bir öğrenci seçin.";
        }
    } elseif ($action === 'add_programs' && $ogrenci_id > 0) {
        // Program ekleme
        if (isset($_POST['programs']) && is_array($_POST['programs'])) {
            $addedCount = 0;

            foreach ($_POST['programs'] as $prog_id) {
                $prog_id = (int)$prog_id;

                // Bu programa zaten kayıtlı mı?
                $checkQuery = "SELECT COUNT(*) as count FROM sinif_kayitlari WHERE ogrenci_id = $ogrenci_id AND program_id = $prog_id";
                $checkResult = $conn->query($checkQuery);
                $isRegistered = $checkResult->fetch_assoc()['count'] > 0;

                if (!$isRegistered) {
                    // Bu programın kapasitesi dolmuş mu?
                    $capacityQuery = "SELECT s.kapasite, COUNT(sk.kayit_id) as mevcut 
                                     FROM ders_programi dp
                                     JOIN siniflar s ON dp.sinif_id = s.sinif_id
                                     LEFT JOIN sinif_kayitlari sk ON dp.program_id = sk.program_id
                                     WHERE dp.program_id = $prog_id
                                     GROUP BY dp.program_id";
                    $capacityResult = $conn->query($capacityQuery);
                    $capacity = $capacityResult->fetch_assoc();

                    if ($capacity['kapasite'] > 0 && $capacity['mevcut'] >= $capacity['kapasite']) {
                        $error .= "Program ID $prog_id: Sınıf kapasitesi dolmuş. ";
                        continue;
                    }

                    $insertQuery = "INSERT INTO sinif_kayitlari (ogrenci_id, program_id) VALUES ($ogrenci_id, $prog_id)";

                    if ($conn->query($insertQuery)) {
                        $addedCount++;
                    }
                }
            }

            if ($addedCount > 0) {
                $success = "{$addedCount} programa başarıyla kayıt yapıldı.";
            } else {
                $error = "Program eklenirken bir hata oluştu veya öğrenci bu programlara zaten kayıtlı.";
            }

            // Sayfayı yenile
            header("Location: sinif_kayit.php?ogrenci_id=$ogrenci_id");
            exit;
        } else {
            $error = "Lütfen en az bir program seçin.";
        }
    }
}

// Kayıt silme işlemi
if (isset($_GET['delete'])) {
    $kayit_id = (int)$_GET['delete'];

    $deleteQuery = "DELETE FROM sinif_kayitlari WHERE kayit_id = $kayit_id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Kayıt başarıyla silindi.');
    } else {
        setMessage('error', 'Kayıt silinirken bir hata oluştu: ' . $conn->error);
    }

    // Sayfayı yenile
    if ($program_id > 0) {
        header("Location: sinif_kayit.php?program_id=$program_id");
    } else {
        header("Location: sinif_kayit.php?ogrenci_id=$ogrenci_id");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sınıf Kayıt İşlemleri - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">
                        <?php if ($program_id > 0): ?>
                            Sınıf Kayıt İşlemleri
                        <?php else: ?>
                            Öğrenci Program Kayıtları
                        <?php endif; ?>
                    </h2>
                    <div>
                        <?php if ($program_id > 0): ?>
                            <a href="ders_programi.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i> Ders Programına Dön
                            </a>
                        <?php else: ?>
                            <a href="ogrenci_detay.php?id=<?php echo $ogrenci_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-1"></i> Öğrenci Detayına Dön
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <?php echo errorMessage($error); ?>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <?php echo successMessage($success); ?>
                <?php endif; ?>

                <?php echo showMessage(); ?>

                <?php if ($program_id > 0): ?>
                    <!-- Program için öğrenci kayıt -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-info-circle me-1"></i> Program Bilgileri
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $program['ders_adi']; ?></h5>
                                    <ul class="list-group list-group-flush mb-3">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Dönem:</span>
                                            <span class="text-muted"><?php echo $program['donem_adi']; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Sınıf:</span>
                                            <span class="text-muted"><?php echo $program['sinif_adi']; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Öğretmen:</span>
                                            <span class="text-muted"><?php echo $program['ogretmen_adi']; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Gün/Saat:</span>
                                            <span class="text-muted">
                                                <?php echo $program['gun']; ?>
                                                <?php echo date('H:i', strtotime($program['baslangic_saat'])); ?> -
                                                <?php echo date('H:i', strtotime($program['bitis_saat'])); ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Mevcut/Kapasite:</span>
                                            <span class="text-muted">
                                                <?php echo $currentCount; ?> /
                                                <?php echo $program['kapasite'] ? $program['kapasite'] : 'Sınırsız'; ?>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <!-- Mevcut Öğrenciler -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-users me-1"></i> Kayıtlı Öğrenciler
                                    <span class="badge bg-primary ms-1"><?php echo $currentCount; ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if ($selectedStudentsResult->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Öğrenci</th>
                                                        <th>Telefon</th>
                                                        <th>E-posta</th>
                                                        <th>Kayıt Tarihi</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $i = 1; ?>
                                                    <?php while ($student = $selectedStudentsResult->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $i++; ?></td>
                                                            <td>
                                                                <a href="ogrenci_detay.php?id=<?php echo $student['ogrenci_id']; ?>">
                                                                    <?php echo $student['ad'] . ' ' . $student['soyad']; ?>
                                                                </a>
                                                            </td>
                                                            <td><?php echo $student['telefon'] ?: '-'; ?></td>
                                                            <td><?php echo $student['email'] ?: '-'; ?></td>
                                                            <td><?php echo formatDateTime($student['kayit_tarihi']); ?></td>
                                                            <td>
                                                                <a href="#" class="btn btn-sm btn-danger" title="Kaydı Sil" onclick="return confirm('Bu öğrenciyi programdan çıkarmak istediğinize emin misiniz?') ? window.location.href='sinif_kayit.php?program_id=<?php echo $program_id; ?>&delete=<?php echo $student['kayit_id']; ?>' : false">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> Bu programa kayıtlı öğrenci bulunmamaktadır.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Öğrenci Ekle -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-user-plus me-1"></i> Yeni Öğrenci Ekle
                                </div>
                                <div class="card-body">
                                    <?php if ($availableStudentsResult->num_rows > 0): ?>
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?program_id=" . $program_id); ?>" class="needs-validation" novalidate>
                                            <input type="hidden" name="action" value="add_students">

                                            <div class="mb-3">
                                                <label for="students" class="form-label">Öğrenciler</label>
                                                <select class="form-select" id="students" name="students[]" multiple size="10" required>
                                                    <?php while ($student = $availableStudentsResult->fetch_assoc()): ?>
                                                        <option value="<?php echo $student['ogrenci_id']; ?>">
                                                            <?php echo $student['ad'] . ' ' . $student['soyad']; ?>
                                                            <?php echo $student['telefon'] ? ' (' . $student['telefon'] . ')' : ''; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <div class="form-text">
                                                    Birden fazla öğrenci seçmek için Ctrl tuşuna basılı tutun.
                                                </div>
                                                <div class="invalid-feedback">Lütfen en az bir öğrenci seçin.</div>
                                            </div>

                                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-plus-circle me-1"></i> Seçilen Öğrencileri Ekle
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> Eklenebilecek öğrenci bulunmamaktadır.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Öğrenci için program kayıt -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-user-graduate me-1"></i> Öğrenci Bilgileri
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                                    </div>
                                    <h5 class="card-title text-center mb-3"><?php echo $ogrenci['ad'] . ' ' . $ogrenci['soyad']; ?></h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Telefon:</span>
                                            <span class="text-muted"><?php echo $ogrenci['telefon'] ?: '-'; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>E-posta:</span>
                                            <span class="text-muted"><?php echo $ogrenci['email'] ?: '-'; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Durum:</span>
                                            <span class="text-muted"><?php echo formatStatus($ogrenci['durum']); ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>Kayıt Tarihi:</span>
                                            <span class="text-muted"><?php echo formatDateTime($ogrenci['kayit_tarihi']); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <!-- Kayıtlı Olduğu Programlar -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-calendar-check me-1"></i> Kayıtlı Olduğu Programlar
                                </div>
                                <div class="card-body">
                                    <?php if ($enrolledProgramsResult->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Dönem</th>
                                                        <th>Ders</th>
                                                        <th>Sınıf</th>
                                                        <th>Gün/Saat</th>
                                                        <th>Öğretmen</th>
                                                        <th>Kayıt Tarihi</th>
                                                        <th>İşlemler</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($program = $enrolledProgramsResult->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo $program['donem_adi']; ?></td>
                                                            <td><?php echo $program['ders_adi']; ?></td>
                                                            <td><?php echo $program['sinif_adi']; ?></td>
                                                            <td>
                                                                <?php echo $program['gun']; ?>
                                                                <?php echo date('H:i', strtotime($program['baslangic_saat'])); ?> -
                                                                <?php echo date('H:i', strtotime($program['bitis_saat'])); ?>
                                                            </td>
                                                            <td><?php echo $program['ogretmen_adi']; ?></td>
                                                            <td><?php echo formatDateTime($program['kayit_tarihi']); ?></td>
                                                            <td>
                                                                <a href="#" class="btn btn-sm btn-danger" title="Kaydı Sil" onclick="return confirm('Bu programdan öğrenciyi çıkarmak istediğinize emin misiniz?') ? window.location.href='sinif_kayit.php?ogrenci_id=<?php echo $ogrenci_id; ?>&delete=<?php echo $program['kayit_id']; ?>' : false">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> Öğrenci henüz bir programa kayıtlı değil.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Program Ekle -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-plus-circle me-1"></i> Yeni Program Ekle
                                </div>
                                <div class="card-body">
                                    <?php if ($programsResult->num_rows > 0): ?>
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?ogrenci_id=" . $ogrenci_id); ?>" class="needs-validation" novalidate>
                                            <input type="hidden" name="action" value="add_programs">

                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Seç</th>
                                                            <th>Dönem</th>
                                                            <th>Ders</th>
                                                            <th>Sınıf</th>
                                                            <th>Gün/Saat</th>
                                                            <th>Öğretmen</th>
                                                            <th>Mevcut/Kapasite</th>
                                                            <th>Durum</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($program = $programsResult->fetch_assoc()): ?>
                                                            <?php
                                                            $disabled = $program['kayitli'] > 0 || ($program['kapasite'] > 0 && $program['mevcut'] >= $program['kapasite']);
                                                            $status = '';

                                                            if ($program['kayitli'] > 0) {
                                                                $status = '<span class="badge bg-success">Kayıtlı</span>';
                                                            } elseif ($program['kapasite'] > 0 && $program['mevcut'] >= $program['kapasite']) {
                                                                $status = '<span class="badge bg-danger">Dolu</span>';
                                                            } else {
                                                                $status = '<span class="badge bg-info">Kayıt Edilebilir</span>';
                                                            }
                                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" class="form-check-input" name="programs[]" value="<?php echo $program['program_id']; ?>" <?php echo $disabled ? 'disabled' : ''; ?>>
                                                                </td>
                                                                <td><?php echo $program['donem_adi']; ?></td>
                                                                <td><?php echo $program['ders_adi']; ?></td>
                                                                <td><?php echo $program['sinif_adi']; ?></td>
                                                                <td>
                                                                    <?php echo $program['gun']; ?>
                                                                    <?php echo date('H:i', strtotime($program['baslangic_saat'])); ?> -
                                                                    <?php echo date('H:i', strtotime($program['bitis_saat'])); ?>
                                                                </td>
                                                                <td><?php echo $program['ogretmen_adi']; ?></td>
                                                                <td>
                                                                    <?php echo $program['mevcut']; ?> /
                                                                    <?php echo $program['kapasite'] ? $program['kapasite'] : 'Sınırsız'; ?>
                                                                </td>
                                                                <td><?php echo $status; ?></td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-plus-circle me-1"></i> Seçilen Programlara Kaydet
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> Kayıt edilebilecek aktif dönem programı bulunmamaktadır.
                                        </div>
                                    <?php endif; ?>
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