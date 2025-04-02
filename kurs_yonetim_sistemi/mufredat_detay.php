<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
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
$mufredatQuery = "SELECT m.*, d.ders_adi 
                 FROM mufredat m
                 JOIN dersler d ON m.ders_id = d.ders_id
                 WHERE m.mufredat_id = $id";
$mufredatResult = $conn->query($mufredatQuery);

if ($mufredatResult->num_rows === 0) {
    setMessage('error', 'Müfredat bulunamadı.');
    header("Location: mufredat.php");
    exit;
}

$mufredat = $mufredatResult->fetch_assoc();

// Diğer konu başlıklarını al (aynı derse ait)
$konularQuery = "SELECT mufredat_id, konu_basligi, sirasi 
                FROM mufredat 
                WHERE ders_id = {$mufredat['ders_id']} AND mufredat_id != $id
                ORDER BY sirasi, konu_basligi";
$konularResult = $conn->query($konularQuery);

// Müfredat silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $deleteQuery = "DELETE FROM mufredat WHERE mufredat_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Müfredat başarıyla silindi.');
        header("Location: mufredat.php?ders_id={$mufredat['ders_id']}");
        exit;
    } else {
        setMessage('error', 'Müfredat silinirken bir hata oluştu: ' . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müfredat Detayı - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Müfredat Detayı</h2>
                    <div>
                        <a href="mufredat_duzenle.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Düzenle
                        </a>
                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                            <a href="#" class="btn btn-danger ms-2" onclick="return confirm('Bu müfredatı silmek istediğinize emin misiniz?') ? window.location.href='mufredat_detay.php?id=<?php echo $id; ?>&delete=1' : false">
                                <i class="fas fa-trash me-1"></i> Sil
                            </a>
                        <?php endif; ?>
                        <a href="mufredat.php?ders_id=<?php echo $mufredat['ders_id']; ?>" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-arrow-left me-1"></i> Listeye Dön
                        </a>
                    </div>
                </div>

                <?php echo showMessage(); ?>

                <div class="row">
                    <!-- Ana İçerik -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><?php echo $mufredat['konu_basligi']; ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <?php if (!empty($mufredat['icerik'])): ?>
                                        <div class="mb-3">
                                            <?php echo nl2br(htmlspecialchars($mufredat['icerik'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> Bu konu için içerik girilmemiş.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yan Panel -->
                    <div class="col-md-4">
                        <!-- Ders Bilgileri -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-book me-1"></i> Ders Bilgileri
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Ders:</span>
                                        <a href="mufredat.php?ders_id=<?php echo $mufredat['ders_id']; ?>" class="fw-bold">
                                            <?php echo $mufredat['ders_adi']; ?>
                                        </a>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Konu:</span>
                                        <span class="text-muted"><?php echo $mufredat['konu_basligi']; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Süre:</span>
                                        <span class="text-muted"><?php echo $mufredat['suresi'] ? $mufredat['suresi'] . ' saat' : '-'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Sıra:</span>
                                        <span class="text-muted"><?php echo $mufredat['sirasi'] ?: '-'; ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Diğer Konular -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-list me-1"></i> Diğer Konular
                            </div>
                            <div class="card-body">
                                <?php if ($konularResult->num_rows > 0): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php while ($konu = $konularResult->fetch_assoc()): ?>
                                            <li class="list-group-item">
                                                <a href="mufredat_detay.php?id=<?php echo $konu['mufredat_id']; ?>">
                                                    <?php echo $konu['sirasi'] ? $konu['sirasi'] . '. ' : ''; ?>
                                                    <?php echo $konu['konu_basligi']; ?>
                                                </a>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-1"></i> Bu derse ait başka konu bulunmamaktadır.
                                    </div>
                                <?php endif; ?>
                                <div class="text-center mt-3">
                                    <a href="mufredat_ekle.php?ders_id=<?php echo $mufredat['ders_id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-plus-circle me-1"></i> Yeni Konu Ekle
                                    </a>
                                </div>
                            </div>
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