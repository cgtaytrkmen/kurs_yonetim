<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Ders listesi
$query = "SELECT d.*, 
          (SELECT COUNT(*) FROM ders_programi WHERE ders_id = d.ders_id) as program_count,
          (SELECT COUNT(*) FROM mufredat WHERE ders_id = d.ders_id) as mufredat_count
          FROM dersler d ORDER BY ders_adi";
$result = $conn->query($query);

// Ders silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $id = (int)$_GET['delete'];

    // Derse bağlı program veya müfredat var mı?
    $checkQuery = "SELECT 
                  (SELECT COUNT(*) FROM ders_programi WHERE ders_id = $id) as program_count,
                  (SELECT COUNT(*) FROM mufredat WHERE ders_id = $id) as mufredat_count";
    $checkResult = $conn->query($checkQuery);
    $check = $checkResult->fetch_assoc();

    if ($check['program_count'] > 0 || $check['mufredat_count'] > 0) {
        setMessage('error', 'Bu derse ait program veya müfredat kayıtları bulunmaktadır. Önce bu kayıtları silmelisiniz.');
    } else {
        $deleteQuery = "DELETE FROM dersler WHERE ders_id = $id";

        if ($conn->query($deleteQuery)) {
            setMessage('success', 'Ders başarıyla silindi.');
        } else {
            setMessage('error', 'Ders silinirken bir hata oluştu: ' . $conn->error);
        }
    }

    // Sayfayı yenile
    header("Location: dersler.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dersler - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Dersler</h2>
                    <a href="ders_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Ders
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i> Ders Listesi
                        <span class="badge bg-primary ms-2"><?php echo $result->num_rows; ?> kayıt</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Ders Adı</th>
                                        <th>Program Sayısı</th>
                                        <th>Müfredat Sayısı</th>
                                        <th>Açıklama</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['ders_id']; ?></td>
                                                <td><?php echo $row['ders_adi']; ?></td>
                                                <td>
                                                    <?php if ($row['program_count'] > 0): ?>
                                                        <span class="badge bg-success"><?php echo $row['program_count']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($row['mufredat_count'] > 0): ?>
                                                        <span class="badge bg-info"><?php echo $row['mufredat_count']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $row['aciklama'] ?: '-'; ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="ders_duzenle.php?id=<?php echo $row['ders_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="mufredat.php?ders_id=<?php echo $row['ders_id']; ?>" class="btn btn-info" title="Müfredat">
                                                            <i class="fas fa-list-ol"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici']) && $row['program_count'] == 0 && $row['mufredat_count'] == 0): ?>
                                                            <a href="#" class="btn btn-danger" title="Sil" onclick="return confirm('Bu dersi silmek istediğinize emin misiniz?') ? window.location.href='dersler.php?delete=<?php echo $row['ders_id']; ?>' : false">
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