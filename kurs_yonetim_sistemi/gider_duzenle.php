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

// Gider ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz gider ID.');
    header("Location: giderler.php");
    exit;
}

// Gider bilgilerini al
$giderQuery = "SELECT * FROM giderler WHERE gider_id = $id";
$giderResult = $conn->query($giderQuery);

if ($giderResult->num_rows === 0) {
    setMessage('error', 'Gider bulunamadı.');
    header("Location: giderler.php");
    exit;
}

$gider = $giderResult->fetch_assoc();

// Varolan gider türlerini al (öneriler için)
$typesQuery = "SELECT DISTINCT gider_turu FROM giderler WHERE gider_id != $id ORDER BY gider_turu";
$typesResult = $conn->query($typesQuery);
$giderTurleri = [];
while ($row = $typesResult->fetch_assoc()) {
    $giderTurleri[] = $row['gider_turu'];
}

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $gider_turu = post('gider_turu');
    $miktar = post('miktar');
    $gider_tarihi = post('gider_tarihi') ?: date('Y-m-d H:i:s');
    $aciklama = post('aciklama');

    // Doğrulama
    if (empty($gider_turu) || empty($miktar)) {
        $error = "Gider türü ve miktar zorunludur.";
    } elseif (!is_numeric($miktar) || $miktar <= 0) {
        $error = "Geçerli bir miktar giriniz.";
    } else {
        // Gider güncelleme işlemi
        $miktar = (float)$miktar;

        $stmt = $conn->prepare("UPDATE giderler SET gider_turu = ?, miktar = ?, gider_tarihi = ?, aciklama = ? WHERE gider_id = ?");
        $stmt->bind_param("sdssi", $gider_turu, $miktar, $gider_tarihi, $aciklama, $id);

        if ($stmt->execute()) {
            $success = "Gider başarıyla güncellendi.";
            // Gider bilgilerini yenile
            $giderResult = $conn->query($giderQuery);
            $gider = $giderResult->fetch_assoc();
        } else {
            $error = "Gider güncellenirken bir hata oluştu: " . $stmt->error;
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
    <title>Gider Düzenle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Gider Düzenle</h2>
                    <div>
                        <a href="#" class="btn btn-danger me-2" onclick="return confirm('Bu gider kaydını silmek istediğinize emin misiniz?') ? window.location.href='giderler.php?delete=<?php echo $id; ?>' : false">
                            <i class="fas fa-trash me-1"></i> Sil
                        </a>
                        <a href="giderler.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Gider Listesine Dön
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
                        <i class="fas fa-money-bill-wave me-1"></i> Gider Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gider_turu" class="form-label">Gider Türü <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="gider_turu" name="gider_turu" list="giderTurleri" value="<?php echo htmlspecialchars($gider['gider_turu']); ?>" required>
                                        <datalist id="giderTurleri">
                                            <?php foreach ($giderTurleri as $tur): ?>
                                                <option value="<?php echo $tur; ?>">
                                                <?php endforeach; ?>
                                        </datalist>
                                        <div class="form-text">Mevcut bir gider türü seçebilir veya yeni bir tür ekleyebilirsiniz.</div>
                                        <div class="invalid-feedback">Gider türü gereklidir.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="miktar" class="form-label">Miktar (₺) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="miktar" name="miktar" step="0.01" min="0" value="<?php echo $gider['miktar']; ?>" required>
                                        <div class="invalid-feedback">Geçerli bir miktar giriniz.</div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gider_tarihi" class="form-label">Gider Tarihi</label>
                                        <input type="datetime-local" class="form-control" id="gider_tarihi" name="gider_tarihi" value="<?php echo date('Y-m-d\TH:i', strtotime($gider['gider_tarihi'])); ?>">
                                        <div class="form-text">Boş bırakılırsa şu anki tarih/saat kullanılır.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="aciklama" class="form-label">Açıklama</label>
                                        <textarea class="form-control" id="aciklama" name="aciklama" rows="4" placeholder="Gider hakkında detaylı açıklama"><?php echo htmlspecialchars($gider['aciklama']); ?></textarea>
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