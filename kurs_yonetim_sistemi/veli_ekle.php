<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Öğrenci ID kontrolü
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

// Öğrencinin mevcut velisi var mı?
$veliCheckQuery = "SELECT * FROM veliler WHERE ogrenci_id = $ogrenci_id";
$veliCheckResult = $conn->query($veliCheckQuery);

if ($veliCheckResult->num_rows > 0) {
    setMessage('warning', 'Bu öğrenciye ait kayıtlı bir veli zaten bulunmaktadır. Mevcut veliyi güncelleyebilirsiniz.');
    header("Location: veli_duzenle.php?ogrenci_id=$ogrenci_id");
    exit;
}

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $ad = post('ad');
    $soyad = post('soyad');
    $yakinlik = post('yakinlik');
    $telefon = post('telefon');
    $email = post('email');
    $adres = post('adres');

    // Doğrulama
    if (empty($ad) || empty($soyad) || empty($yakinlik) || empty($telefon)) {
        $error = "Ad, soyad, yakınlık ve telefon alanları zorunludur.";
    } else {
        // Veli ekleme işlemi
        $stmt = $conn->prepare("INSERT INTO veliler (ogrenci_id, ad, soyad, yakinlik, telefon, email, adres) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $ogrenci_id, $ad, $soyad, $yakinlik, $telefon, $email, $adres);

        if ($stmt->execute()) {
            setMessage('success', 'Veli bilgileri başarıyla eklendi.');
            header("Location: ogrenci_detay.php?id=$ogrenci_id");
            exit;
        } else {
            $error = "Veli eklenirken bir hata oluştu: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veli Ekle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Veli Ekle</h2>
                    <a href="ogrenci_detay.php?id=<?php echo $ogrenci_id; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Öğrenci Detayına Dön
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
                        <i class="fas fa-users me-1"></i> Veli Bilgileri: <?php echo $ogrenci['ad'] . ' ' . $ogrenci['soyad']; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?ogrenci_id=" . $ogrenci_id); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ad" class="form-label">Ad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ad" name="ad" required>
                                        <div class="invalid-feedback">Ad alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="soyad" class="form-label">Soyad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="soyad" name="soyad" required>
                                        <div class="invalid-feedback">Soyad alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="yakinlik" class="form-label">Yakınlık Derecesi <span class="text-danger">*</span></label>
                                        <select class="form-select" id="yakinlik" name="yakinlik" required>
                                            <option value="">-- Seçiniz --</option>
                                            <option value="Anne">Anne</option>
                                            <option value="Baba">Baba</option>
                                            <option value="Ağabey">Ağabey</option>
                                            <option value="Abla">Abla</option>
                                            <option value="Amca">Amca</option>
                                            <option value="Dayı">Dayı</option>
                                            <option value="Teyze">Teyze</option>
                                            <option value="Hala">Hala</option>
                                            <option value="Dede">Dede</option>
                                            <option value="Babaanne">Babaanne</option>
                                            <option value="Anneanne">Anneanne</option>
                                            <option value="Diğer">Diğer</option>
                                        </select>
                                        <div class="invalid-feedback">Yakınlık derecesi seçimi zorunludur.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="telefon" class="form-label">Telefon <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="telefon" name="telefon" placeholder="05xxxxxxxxx" required>
                                        <div class="invalid-feedback">Telefon alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="adres" class="form-label">Adres</label>
                                        <textarea class="form-control" id="adres" name="adres" rows="5"></textarea>
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