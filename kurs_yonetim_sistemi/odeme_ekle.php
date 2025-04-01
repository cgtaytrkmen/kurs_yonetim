<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Öğrenci ID kontrolü (opsiyonel)
$ogrenci_id = isset($_GET['ogrenci_id']) ? (int)$_GET['ogrenci_id'] : 0;
$ogrenci = null;

if ($ogrenci_id > 0) {
    $ogrenciQuery = "SELECT * FROM ogrenciler WHERE ogrenci_id = $ogrenci_id";
    $ogrenciResult = $conn->query($ogrenciQuery);

    if ($ogrenciResult->num_rows > 0) {
        $ogrenci = $ogrenciResult->fetch_assoc();
    }
}

// Öğrenci listesi
$ogrencilerQuery = "SELECT ogrenci_id, ad, soyad FROM ogrenciler WHERE durum = 'Aktif' ORDER BY ad, soyad";
$ogrencilerResult = $conn->query($ogrencilerQuery);

// Dönem listesi
$donemlerQuery = "SELECT * FROM donemler ORDER BY baslangic_tarihi DESC";
$donemlerResult = $conn->query($donemlerQuery);

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $ogrenci_id = post('ogrenci_id');
    $donem_id = post('donem_id') ?: null;
    $miktar = post('miktar');
    $odeme_turu = post('odeme_turu');
    $odeme_tarihi = post('odeme_tarihi') ?: date('Y-m-d H:i:s');
    $aciklama = post('aciklama');

    // Doğrulama
    if (empty($ogrenci_id) || empty($miktar) || empty($odeme_turu)) {
        $error = "Lütfen zorunlu alanları doldurun.";
    } elseif (!is_numeric($miktar) || $miktar <= 0) {
        $error = "Geçerli bir ödeme miktarı giriniz.";
    } else {
        // Ödeme ekleme işlemi
        $miktar = (float)$miktar;

        if ($donem_id === null) {
            $stmt = $conn->prepare("INSERT INTO odemeler (ogrenci_id, miktar, odeme_turu, odeme_tarihi, aciklama) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idsss", $ogrenci_id, $miktar, $odeme_turu, $odeme_tarihi, $aciklama);
        } else {
            $stmt = $conn->prepare("INSERT INTO odemeler (ogrenci_id, donem_id, miktar, odeme_turu, odeme_tarihi, aciklama) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iidsss", $ogrenci_id, $donem_id, $miktar, $odeme_turu, $odeme_tarihi, $aciklama);
        }

        if ($stmt->execute()) {
            $odeme_id = $conn->insert_id;
            setMessage('success', 'Ödeme başarıyla kaydedildi.');

            // Ödeme detay sayfasına yönlendir
            header("Location: odeme_detay.php?id=$odeme_id");
            exit;
        } else {
            $error = "Ödeme kaydedilirken bir hata oluştu: " . $stmt->error;
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
    <title>Yeni Ödeme - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yeni Ödeme Ekle</h2>
                    <a href="odemeler.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Ödeme Listesine Dön
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
                        <i class="fas fa-money-bill-wave me-1"></i> Ödeme Bilgileri
                    </div>
                    <div class="card-body">
                        <form id="paymentForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($ogrenci_id ? "?ogrenci_id=$ogrenci_id" : '')); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ogrenci_id" class="form-label">Öğrenci <span class="text-danger">*</span></label>
                                        <select class="form-select" id="ogrenci_id" name="ogrenci_id" required <?php echo $ogrenci ? 'disabled' : ''; ?>>
                                            <option value="">-- Öğrenci Seçiniz --</option>
                                            <?php $ogrencilerResult->data_seek(0); ?>
                                            <?php while ($ogrenci_row = $ogrencilerResult->fetch_assoc()): ?>
                                                <option value="<?php echo $ogrenci_row['ogrenci_id']; ?>" <?php echo ($ogrenci && $ogrenci['ogrenci_id'] == $ogrenci_row['ogrenci_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $ogrenci_row['ad'] . ' ' . $ogrenci_row['soyad']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <?php if ($ogrenci): ?>
                                            <input type="hidden" name="ogrenci_id" value="<?php echo $ogrenci['ogrenci_id']; ?>">
                                        <?php endif; ?>
                                        <div class="invalid-feedback">Lütfen bir öğrenci seçin.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="donem_id" class="form-label">Dönem</label>
                                        <select class="form-select" id="donem_id" name="donem_id">
                                            <option value="">-- Seçiniz (Opsiyonel) --</option>
                                            <?php while ($donem = $donemlerResult->fetch_assoc()): ?>
                                                <option value="<?php echo $donem['donem_id']; ?>">
                                                    <?php echo $donem['donem_adi']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="miktar" class="form-label">Ödeme Miktarı (₺) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="miktar" name="miktar" step="0.01" min="0" required>
                                        <div class="invalid-feedback">Lütfen geçerli bir miktar girin.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="odeme_turu" class="form-label">Ödeme Türü <span class="text-danger">*</span></label>
                                        <select class="form-select" id="odeme_turu" name="odeme_turu" required>
                                            <option value="">-- Seçiniz --</option>
                                            <option value="Nakit">Nakit</option>
                                            <option value="Kredi Kartı">Kredi Kartı</option>
                                            <option value="Havale">Havale</option>
                                            <option value="Diğer">Diğer</option>
                                        </select>
                                        <div class="invalid-feedback">Lütfen ödeme türünü seçin.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="odeme_tarihi" class="form-label">Ödeme Tarihi</label>
                                        <input type="datetime-local" class="form-control" id="odeme_tarihi" name="odeme_tarihi" value="<?php echo date('Y-m-d\TH:i'); ?>">
                                        <div class="form-text">Boş bırakılırsa şu anki tarih/saat kullanılır.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="aciklama" class="form-label">Açıklama</label>
                                        <textarea class="form-control" id="aciklama" name="aciklama" rows="4"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Özet</label>
                                        <div class="p-3 bg-light rounded">
                                            <div class="d-flex justify-content-between">
                                                <span>Toplam Ödeme:</span>
                                                <span id="totalAmount" class="fw-bold">0.00 ₺</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-2">
                                    <i class="fas fa-eraser me-1"></i> Temizle
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Ödemeyi Kaydet
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