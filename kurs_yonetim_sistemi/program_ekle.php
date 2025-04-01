<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Yetki kontrolü
if (!checkPermission(['Admin', 'Yönetici'])) {
    setMessage('error', 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
    header("Location: dashboard.php");
    exit;
}

// Dönemleri al
$donemsQuery = "SELECT * FROM donemler WHERE durum = 'Aktif' ORDER BY baslangic_tarihi DESC";
$donemsResult = $conn->query($donemsQuery);

// Sınıfları al
$sinifsQuery = "SELECT * FROM siniflar ORDER BY sinif_adi";
$sinifsResult = $conn->query($sinifsQuery);

// Dersleri al
$derssQuery = "SELECT * FROM dersler ORDER BY ders_adi";
$derssResult = $conn->query($derssQuery);

// Öğretmenleri al
$ogretmensQuery = "SELECT * FROM ogretmenler WHERE durum = 'Aktif' ORDER BY ad, soyad";
$ogretmensResult = $conn->query($ogretmensQuery);

// Günler
$gunler = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $donem_id = post('donem_id');
    $sinif_id = post('sinif_id');
    $ders_id = post('ders_id');
    $ogretmen_id = post('ogretmen_id');
    $gun = post('gun');
    $baslangic_saat = post('baslangic_saat');
    $bitis_saat = post('bitis_saat');

    // Doğrulama
    if (empty($donem_id) || empty($sinif_id) || empty($ders_id) || empty($ogretmen_id) || empty($gun) || empty($baslangic_saat) || empty($bitis_saat)) {
        $error = "Lütfen tüm alanları doldurun.";
    } elseif (strtotime($bitis_saat) <= strtotime($baslangic_saat)) {
        $error = "Bitiş saati, başlangıç saatinden sonra olmalıdır.";
    } else {
        // Çakışma kontrolü - Sınıf için
        $checkSinifQuery = "SELECT dp.*, d.ders_adi, s.sinif_adi 
                          FROM ders_programi dp 
                          JOIN dersler d ON dp.ders_id = d.ders_id
                          JOIN siniflar s ON dp.sinif_id = s.sinif_id
                          WHERE dp.donem_id = ? AND dp.sinif_id = ? AND dp.gun = ? AND 
                                ((? BETWEEN dp.baslangic_saat AND dp.bitis_saat) OR 
                                 (? BETWEEN dp.baslangic_saat AND dp.bitis_saat) OR
                                 (dp.baslangic_saat BETWEEN ? AND ?) OR
                                 (dp.bitis_saat BETWEEN ? AND ?))";

        $checkStmt = $conn->prepare($checkSinifQuery);
        $checkStmt->bind_param("iissssss", $donem_id, $sinif_id, $gun, $baslangic_saat, $bitis_saat, $baslangic_saat, $bitis_saat, $baslangic_saat, $bitis_saat);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $conflict = $checkResult->fetch_assoc();
            $error = "Bu sınıf için çakışan bir ders programı mevcut: {$conflict['ders_adi']} ({$conflict['sinif_adi']}) - " .
                date('H:i', strtotime($conflict['baslangic_saat'])) . "-" . date('H:i', strtotime($conflict['bitis_saat']));
        } else {
            // Çakışma kontrolü - Öğretmen için
            $checkOgretmenQuery = "SELECT dp.*, d.ders_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi 
                                 FROM ders_programi dp 
                                 JOIN dersler d ON dp.ders_id = d.ders_id
                                 JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                                 WHERE dp.donem_id = ? AND dp.ogretmen_id = ? AND dp.gun = ? AND 
                                       ((? BETWEEN dp.baslangic_saat AND dp.bitis_saat) OR 
                                        (? BETWEEN dp.baslangic_saat AND dp.bitis_saat) OR
                                        (dp.baslangic_saat BETWEEN ? AND ?) OR
                                        (dp.bitis_saat BETWEEN ? AND ?))";

            $checkStmt = $conn->prepare($checkOgretmenQuery);
            $checkStmt->bind_param("iissssss", $donem_id, $ogretmen_id, $gun, $baslangic_saat, $bitis_saat, $baslangic_saat, $bitis_saat, $baslangic_saat, $bitis_saat);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $conflict = $checkResult->fetch_assoc();
                $error = "Bu öğretmen için çakışan bir ders programı mevcut: {$conflict['ders_adi']} ({$conflict['ogretmen_adi']}) - " .
                    date('H:i', strtotime($conflict['baslangic_saat'])) . "-" . date('H:i', strtotime($conflict['bitis_saat']));
            } else {
                // Program ekleme işlemi
                $insertQuery = "INSERT INTO ders_programi (donem_id, sinif_id, ders_id, ogretmen_id, gun, baslangic_saat, bitis_saat) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iiissss", $donem_id, $sinif_id, $ders_id, $ogretmen_id, $gun, $baslangic_saat, $bitis_saat);

                if ($stmt->execute()) {
                    setMessage('success', 'Ders programı başarıyla eklendi.');
                    header("Location: ders_programi.php");
                    exit;
                } else {
                    $error = "Ders programı eklenirken bir hata oluştu: " . $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Program Ekle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yeni Ders Programı Ekle</h2>
                    <a href="ders_programi.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Programa Dön
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                    <?php echo errorMessage($error); ?>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <?php echo successMessage($success); ?>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-calendar-plus me-1"></i> Program Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="donem_id" class="form-label">Dönem <span class="text-danger">*</span></label>
                                        <select class="form-select" id="donem_id" name="donem_id" required>
                                            <option value="">-- Dönem Seçiniz --</option>
                                            <?php while ($donem = $donemsResult->fetch_assoc()): ?>
                                                <option value="<?php echo $donem['donem_id']; ?>">
                                                    <?php echo $donem['donem_adi']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Lütfen bir dönem seçin.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sinif_id" class="form-label">Sınıf <span class="text-danger">*</span></label>
                                        <select class="form-select" id="sinif_id" name="sinif_id" required>
                                            <option value="">-- Sınıf Seçiniz --</option>
                                            <?php while ($sinif = $sinifsResult->fetch_assoc()): ?>
                                                <option value="<?php echo $sinif['sinif_id']; ?>">
                                                    <?php echo $sinif['sinif_adi']; ?>
                                                    <?php echo $sinif['kapasite'] ? ' (Kapasite: ' . $sinif['kapasite'] . ')' : ''; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Lütfen bir sınıf seçin.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="ders_id" class="form-label">Ders <span class="text-danger">*</span></label>
                                        <select class="form-select" id="ders_id" name="ders_id" required>
                                            <option value="">-- Ders Seçiniz --</option>
                                            <?php while ($ders = $derssResult->fetch_assoc()): ?>
                                                <option value="<?php echo $ders['ders_id']; ?>">
                                                    <?php echo $ders['ders_adi']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Lütfen bir ders seçin.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ogretmen_id" class="form-label">Öğretmen <span class="text-danger">*</span></label>
                                        <select class="form-select" id="ogretmen_id" name="ogretmen_id" required>
                                            <option value="">-- Öğretmen Seçiniz --</option>
                                            <?php while ($ogretmen = $ogretmensResult->fetch_assoc()): ?>
                                                <option value="<?php echo $ogretmen['ogretmen_id']; ?>">
                                                    <?php echo $ogretmen['ad'] . ' ' . $ogretmen['soyad']; ?>
                                                    <?php echo $ogretmen['uzmanlik_alani'] ? ' (' . $ogretmen['uzmanlik_alani'] . ')' : ''; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="invalid-feedback">Lütfen bir öğretmen seçin.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="gun" class="form-label">Gün <span class="text-danger">*</span></label>
                                        <select class="form-select" id="gun" name="gun" required>
                                            <option value="">-- Gün Seçiniz --</option>
                                            <?php foreach ($gunler as $g): ?>
                                                <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Lütfen bir gün seçin.</div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="baslangic_saat" class="form-label">Başlangıç Saati <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="baslangic_saat" name="baslangic_saat" required>
                                                <div class="invalid-feedback">Lütfen başlangıç saatini seçin.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="bitis_saat" class="form-label">Bitiş Saati <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" id="bitis_saat" name="bitis_saat" required>
                                                <div class="invalid-feedback">Lütfen bitiş saatini seçin.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-2">
                                    <i class="fas fa-eraser me-1"></i> Temizle
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>