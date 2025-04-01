<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Sınıf listesi
$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM ders_programi WHERE sinif_id = s.sinif_id) as program_count
          FROM siniflar s ORDER BY sinif_adi";
$result = $conn->query($query);

// Sınıf silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $id = (int)$_GET['delete'];

    // Sınıfa bağlı kayıtlar var mı?
    $checkQuery = "SELECT COUNT(*) as count FROM ders_programi WHERE sinif_id = $id";
    $checkResult = $conn->query($checkQuery);
    $hasPrograms = $checkResult->fetch_assoc()['count'] > 0;

    if ($hasPrograms) {
        setMessage('error', 'Bu sınıfa ait ders programı kayıtları bulunmaktadır. Önce bu kayıtları silmelisiniz.');
    } else {
        $deleteQuery = "DELETE FROM siniflar WHERE sinif_id = $id";

        if ($conn->query($deleteQuery)) {
            setMessage('success', 'Sınıf başarıyla silindi.');
        } else {
            setMessage('error', 'Sınıf silinirken bir hata oluştu: ' . $conn->error);
        }
    }

    // Sayfayı yenile
    header("Location: siniflar.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sınıflar - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Sınıflar</h2>
                    <a href="sinif_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Sınıf
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i> Sınıf Listesi
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Sınıf Adı</th>
                                        <th>Kapasite</th>
                                        <th>Lokasyon</th>
                                        <th>Program Sayısı</th>
                                        <th>Açıklama</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['sinif_id']; ?></td>
                                                <td><?php echo $row['sinif_adi']; ?></td>
                                                <td><?php echo $row['kapasite'] ?: 'Sınırsız'; ?></td>
                                                <td><?php echo $row['lokasyon'] ?: '-'; ?></td>
                                                <td>
                                                    <?php if ($row['program_count'] > 0): ?>
                                                        <span class="badge bg-success"><?php echo $row['program_count']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $row['aciklama'] ?: '-'; ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="sinif_duzenle.php?id=<?php echo $row['sinif_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici']) && $row['program_count'] == 0): ?>
                                                            <a href="#" class="btn btn-danger" title="Sil" onclick="return confirm('Bu sınıfı silmek istediğinize emin misiniz?') ? window.location.href='siniflar.php?delete=<?php echo $row['sinif_id']; ?>' : false">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Kayıt bulunamadı</td>
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