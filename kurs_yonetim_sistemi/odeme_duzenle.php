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

// Ödeme ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz ödeme ID.');
    header("Location: odemeler.php");
    exit;
}

// Ödeme bilgilerini al
$odemeQuery = "SELECT * FROM odemeler WHERE odeme_id = $id";
$odemeResult = $conn->query($odemeQuery);

if ($odemeResult->num_rows === 0) {
    setMessage('error', 'Ödeme kaydı bulunamadı.');
    header("Location: odemeler.php");
    exit;
}

$odeme = $odemeResult->fetch_assoc();

// Öğrenci bilgilerini al
$ogrenciQuery = "SELECT ogrenci_id, ad, soyad FROM ogrenciler WHERE ogrenci_id = {$odeme['ogrenci_id']}";
$ogrenciResult = $conn->query($ogrenciQuery);
$ogrenci = $ogrenciResult->fetch_assoc();

// Dönem listesi
$donemlerQuery = "SELECT * FROM donemler ORDER BY baslangic_tarihi DESC";
$donemlerResult = $conn->query($donemlerQuery);

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $donem_id = post('donem_id') ?: null;
    $miktar = post('miktar');
    $odeme_turu = post('odeme_turu');
    $odeme_tarihi = post('odeme_tarihi') ?: date('Y-m-d H:i:s');
    $aciklama = post('aciklama');

    // Doğrulama
    if (empty($miktar) || empty($odeme_turu)) {
        $error = "Miktar ve ödeme türü zorunludur.";
    } elseif (!is_numeric($miktar) || $miktar <= 0) {
        $error = "Geçerli bir ödeme miktarı giriniz.";
    } else {
        // Ödeme güncelleme işlemi
        $miktar = (float)$miktar;

        if ($donem_id === null) {
            $stmt = $conn->prepare("UPDATE odemeler SET miktar = ?, odeme_turu = ?, odeme_tarihi = ?, aciklama = ?, donem_id = NULL WHERE odeme_id = ?");
            $stmt->bind_param("dsssi", $miktar, $odeme_turu, $odeme_tarihi, $aciklama, $id);
        } else {
            $stmt = $conn->prepare("UPDATE odemeler SET miktar = ?, odeme_turu = ?, odeme_tarihi = ?, aciklama = ?, donem_id = ? WHERE odeme_id = ?");
            $stmt->bind_param("dsssii", $miktar, $odeme_turu, $odeme_tarihi, $aciklama, $donem_id, $id);
        }

        if ($stmt->execute()) {
            $success = "Ödeme başarıyla güncellendi.";

            // Ödeme bilgilerini yenile
            $odemeResult = $conn->query($odemeQuery);
            $odeme = $odemeResult->fetch_assoc();
        } else {
            $error = "Ödeme güncellenirken bir hata oluştu: " . $stmt->error;
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
    <title>Ödeme Düzenle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Ödeme Düzenle</h2>
                    <div>
                        <a href="odeme_detay.php?id=<?php echo $id; ?>" class="btn btn-outline-info me-2">
                            <i class="fas fa-eye me-1"></i> Detay
                        </a>
                        <a href="odemeler.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Listeye Dön
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
                        <i class="fas fa-money-bill-wave me-1"></i> Ödeme Bilgileri
                    </div>
                    <div class="card-body">
                        <form id="paymentForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="ogrenci_id" class="form-label">Öğrenci</label>
                                        <input type="text" class="form-control" value="<?php echo $ogrenci['ad'] . ' ' . $ogrenci['soyad']; ?>" disabled>
                                        <div class="form-text">
                                            Öğrenci değiştirmek için mevcut ödemeyi silip yeni ödeme eklemeniz gerekir.
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="donem_id" class="form-label">Dönem</label>
                                        <select class="form-select" id="donem_id" name="donem_id">
                                            <option value="">-- Seçiniz (Opsiyonel) --</option>
                                            <?php while ($donem = $donemlerResult->fetch_assoc()): ?>
                                                <option value="<?php echo $donem['donem_id']; ?>" <?php echo ($odeme['donem_id'] == $donem['donem_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $donem['donem_adi']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="miktar" class="form-label">Ödeme Miktarı (₺) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="miktar" name="miktar" step="0.01" min="0" value="<?php echo $odeme['miktar']; ?>" required>
                                        <div class="invalid-feedback">Lütfen geçerli bir miktar girin.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="odeme_turu" class="form-label">Ödeme Türü <span class="text-danger">*</span></label>
                                        <select class="form-select" id="odeme_turu" name="odeme_turu" required>
                                            <option value="">-- Seçiniz --</option>
                                            <option value="Nakit" <?php echo ($odeme['odeme_turu'] == 'Nakit') ? 'selected' : ''; ?>>Nakit</option>
                                            <option value="Kredi Kartı" <?php echo ($odeme['odeme_turu'] == 'Kredi Kartı') ? 'selected' : ''; ?>>Kredi Kartı</option>
                                            <option value="Havale" <?php echo ($odeme['odeme_turu'] == 'Havale') ? 'selected' : ''; ?>>Havale</option>
                                            <option value="Diğer" <?php echo ($odeme['odeme_turu'] == 'Diğer') ? 'selected' : ''; ?>>Diğer</option>
                                        </select>
                                        <div class="invalid-feedback">Lütfen ödeme türünü seçin.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="odeme_tarihi" class="form-label">Ödeme Tarihi</label>
                                        <input type="datetime-local" class="form-control" id="odeme_tarihi" name="odeme_tarihi" value="<?php echo date('Y-m-d\TH:i', strtotime($odeme['odeme_tarihi'])); ?>">
                                        <div class="form-text">Boş bırakılırsa şu anki tarih/saat kullanılır.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="aciklama" class="form-label">Açıklama</label>
                                        <textarea class="form-control" id="aciklama" name="aciklama" rows="4"><?php echo $odeme['aciklama']; ?></textarea>
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