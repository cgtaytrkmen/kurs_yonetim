<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Arama filtresi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = "1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where = "(v.ad LIKE '%$search%' OR v.soyad LIKE '%$search%' OR v.telefon LIKE '%$search%' OR 
             v.email LIKE '%$search%' OR o.ad LIKE '%$search%' OR o.soyad LIKE '%$search%')";
}

// Veli listesi
$query = "SELECT v.*, CONCAT(o.ad, ' ', o.soyad) as ogrenci_adi, o.ogrenci_id
          FROM veliler v
          JOIN ogrenciler o ON v.ogrenci_id = o.ogrenci_id
          WHERE $where
          ORDER BY v.ad, v.soyad";
$result = $conn->query($query);

// Veli silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $id = (int)$_GET['delete'];
    $deleteQuery = "DELETE FROM veliler WHERE veli_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Veli başarıyla silindi.');
    } else {
        setMessage('error', 'Veli silinirken bir hata oluştu: ' . $conn->error);
    }

    // Sayfayı yenile
    header("Location: veliler.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veliler - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Veliler</h2>
                </div>

                <?php echo showMessage(); ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-search me-1"></i> Veli Ara
                    </div>
                    <div class="card-body">
                        <form method="GET" action="veliler.php" class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="İsim, soyisim, telefon, e-posta veya öğrenci adı" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i> Ara
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="veliler.php" class="btn btn-outline-secondary w-100">Sıfırla</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-users me-1"></i> Veli Listesi
                        <span class="badge bg-primary ms-2"><?php echo $result->num_rows; ?> kayıt</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Adı Soyadı</th>
                                        <th>Yakınlık</th>
                                        <th>Telefon</th>
                                        <th>E-posta</th>
                                        <th>Öğrenci</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['veli_id']; ?></td>
                                                <td><?php echo $row['ad'] . ' ' . $row['soyad']; ?></td>
                                                <td><?php echo $row['yakinlik'] ?: 'Belirtilmemiş'; ?></td>
                                                <td><?php echo $row['telefon']; ?></td>
                                                <td><?php echo $row['email'] ?: '-'; ?></td>
                                                <td>
                                                    <a href="ogrenci_detay.php?id=<?php echo $row['ogrenci_id']; ?>"><?php echo $row['ogrenci_adi']; ?></a>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="veli_duzenle.php?id=<?php echo $row['veli_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                                                            <a href="#" class="btn btn-danger" title="Sil" onclick="return confirm('Bu veli kaydını silmek istediğinize emin misiniz?') ? window.location.href='veliler.php?delete=<?php echo $row['veli_id']; ?>' : false">
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