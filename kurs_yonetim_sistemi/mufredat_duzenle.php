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
if (!checkPermission(['Admin', 'Yönetici', 'Öğretmen'])) {
    setMessage('error', 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
    header("Location: dashboard.php");
    exit;
}

// Müfredat ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz müfredat ID.');
    header("Location: mufredat.php");
    exit;
}

// Müfredat bilgilerini al
$mufredatQuery = "SELECT * FROM mufredat WHERE mufredat_id = $id";
$mufredatResult = $conn->query($mufredatQuery);

if ($mufredatResult->num_rows === 0) {
    setMessage('error', 'Müfredat bulunamadı.');
    header("Location: mufredat.php");
    exit;
}

$mufredat = $mufredatResult->fetch_assoc();

// Dersleri al
$dersQuery = "SELECT * FROM dersler ORDER BY ders_adi";
$dersResult = $conn->query($dersQuery);

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $ders_id = post('ders_id');
    $konu_basligi = post('konu_basligi');
    $suresi = post('suresi') ?: null;
    $icerik = post('icerik');
    $sirasi = post('sirasi') ?: 0;

    // Doğrulama
    if (empty($ders_id) || empty($konu_basligi)) {
        $error = "Ders ve konu başlığı zorunludur.";
    } else {
        // Müfredat güncelleme işlemi
        $stmt = $conn->prepare("UPDATE mufredat SET ders_id = ?, konu_basligi = ?, suresi = ?, icerik = ?, sirasi = ? WHERE mufredat_id = ?");
        $stmt->bind_param("isisii", $ders_id, $konu_basligi, $suresi, $icerik, $sirasi, $id);

        if ($stmt->execute()) {
            $success = "Müfredat başarıyla güncellendi.";

            // Müfredat bilgilerini yenile
            $mufredatResult = $conn->query($mufredatQuery);
            $mufredat = $mufredatResult->fetch_assoc();
        } else {
            $error = "Müfredat güncellenirken bir hata oluştu: " . $stmt->error;
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
    <title>Müfredat Düzenle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Müfredat Düzenle</h2>
                    <div>
                        <a href="mufredat_detay.php?id=<?php echo $id; ?>" class="btn btn-outline-info me-2">
                            <i class="fas fa-eye me-1"></i> Detay
                        </a>
                        <a href="mufredat.php?ders_id=<?php echo $mufredat['ders_id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Müfredata Dön
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
                        <i class="fas fa-list-ol me-1"></i> Müfredat Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="ders_id" class="form-label">Ders <span class="text-danger">*</span></label>
                                <select class="form-select" id="ders_id" name="ders_id" required>
                                    <option value="">-- Ders Seçiniz --</option>
                                    <?php while ($ders = $dersResult->fetch_assoc()): ?>
                                        <option value="<?php echo $ders['ders_id']; ?>" <?php echo ($mufredat['ders_id'] == $ders['ders_id']) ? 'selected' : ''; ?>>
                                            <?php echo $ders['ders_adi']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Lütfen bir ders seçin.</div>
                            </div>

                            <div class="mb-3">
                                <label for="konu_basligi" class="form-label">Konu Başlığı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="konu_basligi" name="konu_basligi" value="<?php echo $mufredat['konu_basligi']; ?>" required>
                                <div class="invalid-feedback">Konu başlığı zorunludur.</div>
                            </div>

                            <div class="mb-3">
                                <label for="suresi" class="form-label">Süresi (Saat)</label>
                                <input type="number" class="form-control" id="suresi" name="suresi" step="0.5" min="0" value="<?php echo $mufredat['suresi']; ?>">
                                <div class="form-text">Ders saati cinsinden süre giriniz (örn: 2, 1.5 gibi).</div>
                            </div>

                            <div class="mb-3">
                                <label for="sirasi" class="form-label">Sırası</label>
                                <input type="number" class="form-control" id="sirasi" name="sirasi" value="<?php echo $mufredat['sirasi']; ?>" min="0">
                                <div class="form-text">Müfredat içindeki sıralama (0 veya boş bırakılırsa otomatik sıralanır).</div>
                            </div>

                            <div class="mb-3">
                                <label for="icerik" class="form-label">İçerik</label>
                                <textarea class="form-control" id="icerik" name="icerik" rows="10"><?php echo $mufredat['icerik']; ?></textarea>
                                <div class="form-text">Ders içeriği, kazanımlar, notlar vb. bilgileri girebilirsiniz.</div>
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