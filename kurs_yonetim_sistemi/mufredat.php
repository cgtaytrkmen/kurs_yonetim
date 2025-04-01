<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Ders ID filtresi
$ders_id = isset($_GET['ders_id']) ? (int)$_GET['ders_id'] : 0;

// Ders listesi
$derssQuery = "SELECT * FROM dersler ORDER BY ders_adi";
$derssResult = $conn->query($derssQuery);

// Filtreye göre başlık
$title = "Tüm Müfredat";
$selectedDers = null;

if ($ders_id > 0) {
    $dersQuery = "SELECT * FROM dersler WHERE ders_id = $ders_id";
    $dersResult = $conn->query($dersQuery);

    if ($dersResult->num_rows > 0) {
        $selectedDers = $dersResult->fetch_assoc();
        $title = $selectedDers['ders_adi'] . " Müfredatı";
    }
}

// Müfredat listesi
$query = "SELECT m.*, d.ders_adi
          FROM mufredat m
          JOIN dersler d ON m.ders_id = d.ders_id";

if ($ders_id > 0) {
    $query .= " WHERE m.ders_id = $ders_id";
}

$query .= " ORDER BY m.ders_id, m.mufredat_id";
$result = $conn->query($query);

// Müfredat silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $id = (int)$_GET['delete'];

    $deleteQuery = "DELETE FROM mufredat WHERE mufredat_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Müfredat başarıyla silindi.');
    } else {
        setMessage('error', 'Müfredat silinirken bir hata oluştu: ' . $conn->error);
    }

    // Sayfayı yenile
    header("Location: mufredat.php" . ($ders_id > 0 ? "?ders_id=$ders_id" : ""));
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müfredat - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4"><?php echo $title; ?></h2>
                    <a href="mufredat_ekle.php<?php echo $ders_id > 0 ? "?ders_id=$ders_id" : ""; ?>" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Müfredat
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <!-- Ders Filtresi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter me-1"></i> Ders Filtresi
                    </div>
                    <div class="card-body">
                        <form method="GET" action="mufredat.php" class="row g-3">
                            <div class="col-md-10">
                                <select class="form-select" id="ders_id" name="ders_id">
                                    <option value="0">Tüm Dersler</option>
                                    <?php while ($ders = $derssResult->fetch_assoc()): ?>
                                        <option value="<?php echo $ders['ders_id']; ?>" <?php echo ($ders_id == $ders['ders_id']) ? 'selected' : ''; ?>>
                                            <?php echo $ders['ders_adi']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> Filtrele
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Müfredat Listesi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-list-ol me-1"></i> Müfredat Listesi
                    </div>
                    <div class="card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <?php if ($ders_id == 0): ?>
                                                <th>Ders</th>
                                            <?php endif; ?>
                                            <th>Konu Başlığı</th>
                                            <th>Süresi (Saat)</th>
                                            <th>İçerik</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['mufredat_id']; ?></td>
                                                <?php if ($ders_id == 0): ?>
                                                    <td><?php echo $row['ders_adi']; ?></td>
                                                <?php endif; ?>
                                                <td><?php echo $row['konu_basligi']; ?></td>
                                                <td><?php echo $row['suresi'] ?: '-'; ?></td>
                                                <td>
                                                    <?php
                                                    if (!empty($row['icerik'])) {
                                                        $content = strlen($row['icerik']) > 100 ? substr($row['icerik'], 0, 100) . '...' : $row['icerik'];
                                                        echo htmlspecialchars($content);
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="mufredat_duzenle.php?id=<?php echo $row['mufredat_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="mufredat_detay.php?id=<?php echo $row['mufredat_id']; ?>" class="btn btn-info" title="Detay">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                                                            <a href="#" class="btn btn-danger" title="Sil" onclick="return confirm('Bu müfredatı silmek istediğinize emin misiniz?') ? window.location.href='mufredat.php?<?php echo $ders_id > 0 ? "ders_id=$ders_id&" : ""; ?>delete=<?php echo $row['mufredat_id']; ?>' : false">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                <?php if ($ders_id > 0): ?>
                                    Bu derse ait müfredat kaydı bulunmamaktadır.
                                <?php else: ?>
                                    Henüz hiçbir müfredat kaydı bulunmamaktadır.
                                <?php endif; ?>
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