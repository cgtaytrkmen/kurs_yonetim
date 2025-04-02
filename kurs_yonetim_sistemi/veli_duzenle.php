<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Veli ID veya Öğrenci ID kontrolü
$veli_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ogrenci_id = isset($_GET['ogrenci_id']) ? (int)$_GET['ogrenci_id'] : 0;

// Veli bilgilerini al
if ($veli_id > 0) {
    $veliQuery = "SELECT * FROM veliler WHERE veli_id = $veli_id";
} elseif ($ogrenci_id > 0) {
    $veliQuery = "SELECT * FROM veliler WHERE ogrenci_id = $ogrenci_id";
} else {
    setMessage('error', 'Geçersiz veli ID veya öğrenci ID.');
    header("Location: ogrenciler.php");
    exit;
}

$veliResult = $conn->query($veliQuery);

if ($veliResult->num_rows === 0) {
    setMessage('error', 'Veli kaydı bulunamadı.');
    if ($ogrenci_id > 0) {
        header("Location: veli_ekle.php?ogrenci_id=$ogrenci_id");
    } else {
        header("Location: ogrenciler.php");
    }
    exit;
}

$veli = $veliResult->fetch_assoc();
$veli_id = $veli['veli_id']; // veli_id'yi güncelle
$ogrenci_id = $veli['ogrenci_id']; // ogrenci_id'yi güncelle

// Öğrenci bilgilerini al
$ogrenciQuery = "SELECT * FROM ogrenciler WHERE ogrenci_id = $ogrenci_id";
$ogrenciResult = $conn->query($ogrenciQuery);
$ogrenci = $ogrenciResult->fetch_assoc();

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
        // Veli güncelleme işlemi
        $stmt = $conn->prepare("UPDATE veliler SET ad = ?, soyad = ?, yakinlik = ?, telefon = ?, email = ?, adres = ? WHERE veli_id = ?");
        $stmt->bind_param("ssssssi", $ad, $soyad, $yakinlik, $telefon, $email, $adres, $veli_id);

        if ($stmt->execute()) {
            $success = "Veli bilgileri başarıyla güncellendi.";

            // Veli bilgilerini yenile
            $veliResult = $conn->query($veliQuery);
            $veli = $veliResult->fetch_assoc();
        } else {
            $error = "Veli güncellenirken bir hata oluştu: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Veli silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $deleteQuery = "DELETE FROM veliler WHERE veli_id = $veli_id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Veli kaydı başarıyla silindi.');
        header("Location: ogrenci_detay.php?id=$ogrenci_id");
        exit;
    } else {
        setMessage('error', 'Veli kaydı silinirken bir hata oluştu: ' . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veli Düzenle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Veli Düzenle</h2>
                    <div>
                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                            <a href="#" class="btn btn-danger me-2" onclick="return confirm('Bu veli kaydını silmek istediğinize emin misiniz?') ? window.location.href='veli_duzenle.php?id=<?php echo $veli_id; ?>&delete=1' : false">
                                <i class="fas fa-trash me-1"></i> Sil
                            </a>
                        <?php endif; ?>
                        <a href="ogrenci_detay.php?id=<?php echo $ogrenci_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Öğrenci Detayına Dön
                        </a>
                    </div>
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
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $veli_id); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ad" class="form-label">Ad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ad" name="ad" value="<?php echo $veli['ad']; ?>" required>
                                        <div class="invalid-feedback">Ad alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="soyad" class="form-label">Soyad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="soyad" name="soyad" value="<?php echo $veli['soyad']; ?>" required>
                                        <div class="invalid-feedback">Soyad alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="yakinlik" class="form-label">Yakınlık Derecesi <span class="text-danger">*</span></label>
                                        <select class="form-select" id="yakinlik" name="yakinlik" required>
                                            <option value="">-- Seçiniz --</option>
                                            <option value="Anne" <?php echo ($veli['yakinlik'] == 'Anne') ? 'selected' : ''; ?>>Anne</option>
                                            <option value="Baba" <?php echo ($veli['yakinlik'] == 'Baba') ? 'selected' : ''; ?>>Baba</option>
                                            <option value="Ağabey" <?php echo ($veli['yakinlik'] == 'Ağabey') ? 'selected' : ''; ?>>Ağabey</option>
                                            <option value="Abla" <?php echo ($veli['yakinlik'] == 'Abla') ? 'selected' : ''; ?>>Abla</option>
                                            <option value="Amca" <?php echo ($veli['yakinlik'] == 'Amca') ? 'selected' : ''; ?>>Amca</option>
                                            <option value="Dayı" <?php echo ($veli['yakinlik'] == 'Dayı') ? 'selected' : ''; ?>>Dayı</option>
                                            <option value="Teyze" <?php echo ($veli['yakinlik'] == 'Teyze') ? 'selected' : ''; ?>>Teyze</option>
                                            <option value="Hala" <?php echo ($veli['yakinlik'] == 'Hala') ? 'selected' : ''; ?>>Hala</option>
                                            <option value="Dede" <?php echo ($veli['yakinlik'] == 'Dede') ? 'selected' : ''; ?>>Dede</option>
                                            <option value="Babaanne" <?php echo ($veli['yakinlik'] == 'Babaanne') ? 'selected' : ''; ?>>Babaanne</option>
                                            <option value="Anneanne" <?php echo ($veli['yakinlik'] == 'Anneanne') ? 'selected' : ''; ?>>Anneanne</option>
                                            <option value="Diğer" <?php echo ($veli['yakinlik'] == 'Diğer') ? 'selected' : ''; ?>>Diğer</option>
                                        </select>
                                        <div class="invalid-feedback">Yakınlık derecesi seçimi zorunludur.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="telefon" class="form-label">Telefon <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo $veli['telefon']; ?>" placeholder="05xxxxxxxxx" required>
                                        <div class="invalid-feedback">Telefon alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $veli['email']; ?>">
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="adres" class="form-label">Adres</label>
                                        <textarea class="form-control" id="adres" name="adres" rows="5"><?php echo $veli['adres']; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Değişiklikleri Kaydet
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