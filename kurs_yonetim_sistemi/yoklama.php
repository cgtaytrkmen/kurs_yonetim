<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Program ID'sini al
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;

if ($program_id <= 0) {
    setMessage('error', 'Geçersiz program ID.');
    header("Location: ders_programi.php");
    exit;
}

// Ders programı bilgilerini al
$programQuery = "SELECT dp.*, d.ders_adi, s.sinif_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dm.donem_adi
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

// Sınıfa kayıtlı öğrencileri al
$studentsQuery = "SELECT o.*, sk.kayit_id
                 FROM ogrenciler o
                 JOIN sinif_kayitlari sk ON o.ogrenci_id = sk.ogrenci_id
                 WHERE sk.program_id = $program_id AND o.durum = 'Aktif'
                 ORDER BY o.ad, o.soyad";
$studentsResult = $conn->query($studentsQuery);

// Tarih kontrolü
$today = date('Y-m-d');
$selected_date = isset($_GET['date']) ? $_GET['date'] : $today;

// Geçerli tarih mi kontrol et
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $selected_date)) {
    $selected_date = $today;
}

// Yoklama kaydı var mı?
$checkQuery = "SELECT * FROM yoklamalar WHERE program_id = $program_id AND ders_tarihi = '$selected_date'";
$checkResult = $conn->query($checkQuery);
$yoklama_id = 0;

if ($checkResult->num_rows > 0) {
    $yoklama = $checkResult->fetch_assoc();
    $yoklama_id = $yoklama['yoklama_id'];
}

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ders_tarihi = post('ders_tarihi');

    // Yoklama kaydı oluştur veya mevcut olanı kullan
    if ($yoklama_id == 0) {
        $insertYoklamaQuery = "INSERT INTO yoklamalar (program_id, ders_tarihi) VALUES ($program_id, '$ders_tarihi')";
        if ($conn->query($insertYoklamaQuery)) {
            $yoklama_id = $conn->insert_id;
        } else {
            setMessage('error', 'Yoklama kaydı oluşturulurken bir hata oluştu: ' . $conn->error);
        }
    }

    if ($yoklama_id > 0) {
        // Önce mevcut yoklama detaylarını temizle
        $deleteQuery = "DELETE FROM yoklama_detay WHERE yoklama_id = $yoklama_id";
        $conn->query($deleteQuery);

        // Tüm öğrencilerin yoklama bilgilerini ekle
        $success = true;
        $error_message = '';

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'durum_') === 0) {
                $ogrenci_id = intval(substr($key, 6));
                $durum = $value;
                $aciklama = isset($_POST['aciklama_' . $ogrenci_id]) ? $_POST['aciklama_' . $ogrenci_id] : '';

                $insertDetailQuery = "INSERT INTO yoklama_detay (yoklama_id, ogrenci_id, durum, aciklama) 
                VALUES ($yoklama_id, $ogrenci_id, '$durum', ?)";

                $stmt = $conn->prepare($insertDetailQuery);
                $stmt->bind_param("s", $aciklama);

                if (!$stmt->execute()) {
                    $success = false;
                    $error_message = $stmt->error;
                    break;
                }

                $stmt->close();
            }
        }

        if ($success) {
            setMessage('success', 'Yoklama başarıyla kaydedildi.');
        } else {
            setMessage('error', 'Yoklama kaydedilirken bir hata oluştu: ' . $error_message);
        }
    } else {
        setMessage('error', 'Yoklama kaydı oluşturulamadı.');
    }

    // Sayfayı yenile
    header("Location: yoklama.php?program_id=$program_id&date=$ders_tarihi");
    exit;
}

// Öğrencilerin mevcut yoklama durumlarını al
$attendance = [];
if ($yoklama_id > 0) {
    $attendanceQuery = "SELECT * FROM yoklama_detay WHERE yoklama_id = $yoklama_id";
    $attendanceResult = $conn->query($attendanceQuery);

    while ($row = $attendanceResult->fetch_assoc()) {
        $attendance[$row['ogrenci_id']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoklama - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yoklama</h2>
                    <a href="yoklama_listesi.php" class="btn btn-outline-primary">
                        <i class="fas fa-clipboard-list me-1"></i> Tüm Yoklamalar
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                        <div>
                            <i class="fas fa-clipboard-check me-1"></i> <?php echo $program['ders_adi']; ?> - <?php echo $program['sinif_adi']; ?>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark">
                                <?php echo $program['gun']; ?> |
                                <?php echo date('H:i', strtotime($program['baslangic_saat'])); ?> -
                                <?php echo date('H:i', strtotime($program['bitis_saat'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Ders Bilgileri -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <strong>Dönem:</strong> <?php echo $program['donem_adi']; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Öğretmen:</strong> <?php echo $program['ogretmen_adi']; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Sınıf:</strong> <?php echo $program['sinif_adi']; ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Ders:</strong> <?php echo $program['ders_adi']; ?>
                            </div>
                        </div>

                        <!-- Tarih Seçimi -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <form method="GET" action="yoklama.php" class="d-flex">
                                    <input type="hidden" name="program_id" value="<?php echo $program_id; ?>">
                                    <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                                    <button type="submit" class="btn btn-outline-primary ms-2">
                                        <i class="fas fa-calendar-day me-1"></i> Tarih Seç
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success btn-sm" onclick="setAllAttendance('Var')">
                                        <i class="fas fa-check me-1"></i> Tümü Var
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="setAllAttendance('Yok')">
                                        <i class="fas fa-times me-1"></i> Tümü Yok
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php if ($studentsResult->num_rows > 0): ?>
                            <form id="attendanceForm" method="POST" action="yoklama.php?program_id=<?php echo $program_id; ?>">
                                <input type="hidden" name="ders_tarihi" value="<?php echo $selected_date; ?>">

                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="20%">Öğrenci</th>
                                                <th width="45%" class="text-center">Yoklama Durumu</th>
                                                <th width="30%">Açıklama</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $count = 1; ?>
                                            <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                                <?php
                                                $ogrenci_id = $student['ogrenci_id'];
                                                $current_status = isset($attendance[$ogrenci_id]) ? $attendance[$ogrenci_id]['durum'] : 'Yok';
                                                $current_note = isset($attendance[$ogrenci_id]) ? $attendance[$ogrenci_id]['aciklama'] : '';

                                                $row_class = '';
                                                switch ($current_status) {
                                                    case 'Var':
                                                        $row_class = 'table-success';
                                                        break;
                                                    case 'Yok':
                                                        $row_class = 'table-danger';
                                                        break;
                                                    case 'İzinli':
                                                        $row_class = 'table-warning';
                                                        break;
                                                    case 'Geç':
                                                        $row_class = 'table-info';
                                                        break;
                                                }
                                                ?>
                                                <tr class="<?php echo $row_class; ?>">
                                                    <td><?php echo $count++; ?></td>
                                                    <td>
                                                        <a href="ogrenci_detay.php?id=<?php echo $student['ogrenci_id']; ?>" target="_blank">
                                                            <?php echo $student['ad'] . ' ' . $student['soyad']; ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <div class="text-center">
                                                            <div class="btn-group" role="group">
                                                                <input type="radio" class="btn-check" name="durum_<?php echo $student['ogrenci_id']; ?>" id="var_<?php echo $student['ogrenci_id']; ?>" value="Var" <?php echo ($current_status == 'Var') ? 'checked' : ''; ?>>
                                                                <label class="btn btn-outline-success btn-sm" for="var_<?php echo $student['ogrenci_id']; ?>">
                                                                    <i class="fas fa-check me-1"></i> Var
                                                                </label>

                                                                <input type="radio" class="btn-check" name="durum_<?php echo $student['ogrenci_id']; ?>" id="yok_<?php echo $student['ogrenci_id']; ?>" value="Yok" <?php echo ($current_status == 'Yok') ? 'checked' : ''; ?>>
                                                                <label class="btn btn-outline-danger btn-sm" for="yok_<?php echo $student['ogrenci_id']; ?>">
                                                                    <i class="fas fa-times me-1"></i> Yok
                                                                </label>

                                                                <input type="radio" class="btn-check" name="durum_<?php echo $student['ogrenci_id']; ?>" id="izinli_<?php echo $student['ogrenci_id']; ?>" value="İzinli" <?php echo ($current_status == 'İzinli') ? 'checked' : ''; ?>>
                                                                <label class="btn btn-outline-warning btn-sm" for="izinli_<?php echo $student['ogrenci_id']; ?>">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i> İzinli
                                                                </label>

                                                                <input type="radio" class="btn-check" name="durum_<?php echo $student['ogrenci_id']; ?>" id="gec_<?php echo $student['ogrenci_id']; ?>" value="Geç" <?php echo ($current_status == 'Geç') ? 'checked' : ''; ?>>
                                                                <label class="btn btn-outline-info btn-sm" for="gec_<?php echo $student['ogrenci_id']; ?>">
                                                                    <i class="fas fa-clock me-1"></i> Geç
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" name="aciklama_<?php echo $student['ogrenci_id']; ?>" value="<?php echo htmlspecialchars($current_note); ?>" placeholder="Açıklama (opsiyonel)">
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Yoklamayı Kaydet
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i> Bu derse kayıtlı öğrenci bulunmamaktadır.
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                                <a href="sinif_kayit.php?program_id=<?php echo $program_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-1"></i> Öğrenci Ekle
                                </a>
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