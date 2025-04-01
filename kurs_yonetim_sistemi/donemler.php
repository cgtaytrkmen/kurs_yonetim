<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Dönem silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $id = (int)$_GET['delete'];
    $deleteQuery = "DELETE FROM donemler WHERE donem_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Dönem başarıyla silindi.');
    } else {
        setMessage('error', 'Dönem silinirken bir hata oluştu: ' . $conn->error);
    }

    header("Location: donemler.php");
    exit;
}

// Dönem listesi
$query = "SELECT * FROM donemler ORDER BY baslangic_tarihi DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dönemler - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Dönemler</h2>
                    <a href="donem_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Dönem
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-calendar-alt me-1"></i> Dönem Listesi
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Dönem Adı</th>
                                        <th>Başlangıç</th>
                                        <th>Bitiş</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['donem_id']; ?></td>
                                                <td><?php echo $row['donem_adi']; ?></td>
                                                <td><?php echo formatDate($row['baslangic_tarihi']); ?></td>
                                                <td><?php echo formatDate($row['bitis_tarihi']); ?></td>
                                                <td><?php echo formatStatus($row['durum']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="donem_duzenle.php?id=<?php echo $row['donem_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                                                            <a href="#" class="btn btn-danger" title="Sil"
                                                                onclick="return confirm('Bu dönemi silmek istediğinize emin misiniz?') ? window.location.href='donemler.php?delete=<?php echo $row['donem_id']; ?>' : false">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Kayıt bulunamadı</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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