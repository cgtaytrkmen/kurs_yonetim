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
    $where = "(ad LIKE '%$search%' OR soyad LIKE '%$search%' OR telefon LIKE '%$search%' OR email LIKE '%$search%')";
}

// Durum filtresi
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
if ($status != 'all') {
    $status = $conn->real_escape_string($status);
    $where .= " AND durum = '$status'";
}

// Sayfalama için
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Her sayfada gösterilecek kayıt sayısı
$offset = ($page - 1) * $limit;

// Toplam kayıt sayısı
$countQuery = "SELECT COUNT(*) as total FROM ogrenciler WHERE $where";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Öğrenci listesi
$query = "SELECT * FROM ogrenciler WHERE $where ORDER BY ad, soyad LIMIT $offset, $limit";
$result = $conn->query($query);

// Öğrenci silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $id = (int)$_GET['delete'];
    $deleteQuery = "DELETE FROM ogrenciler WHERE ogrenci_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Öğrenci başarıyla silindi.');
    } else {
        setMessage('error', 'Öğrenci silinirken bir hata oluştu: ' . $conn->error);
    }

    // Sayfayı yenile (temiz URL ile)
    header("Location: ogrenciler.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Yönetimi - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Öğrenciler</h2>
                    <a href="ogrenci_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Öğrenci
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-search me-1"></i> Öğrenci Ara
                    </div>
                    <div class="card-body">
                        <form method="GET" action="ogrenciler.php" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="İsim, soyisim, telefon veya e-posta" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>Tüm Durumlar</option>
                                    <option value="Aktif" <?php echo $status == 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="Pasif" <?php echo $status == 'Pasif' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="ogrenciler.php" class="btn btn-outline-secondary w-100">Sıfırla</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i> Öğrenci Listesi
                        <span class="badge bg-primary ms-2"><?php echo $totalRecords; ?> kayıt</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Adı Soyadı</th>
                                        <th>Cinsiyet</th>
                                        <th>Telefon</th>
                                        <th>E-posta</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['ogrenci_id']; ?></td>
                                                <td><?php echo $row['ad'] . ' ' . $row['soyad']; ?></td>
                                                <td><?php echo formatGender($row['cinsiyet']); ?></td>
                                                <td><?php echo $row['telefon']; ?></td>
                                                <td><?php echo $row['email']; ?></td>
                                                <td><?php echo formatDateTime($row['kayit_tarihi']); ?></td>
                                                <td><?php echo formatStatus($row['durum']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="ogrenci_detay.php?id=<?php echo $row['ogrenci_id']; ?>" class="btn btn-info" title="Detay">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="ogrenci_duzenle.php?id=<?php echo $row['ogrenci_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                                                            <a href="#" class="btn btn-danger" title="Sil"
                                                                onclick="return confirm('Bu öğrenciyi silmek istediğinize emin misiniz?') ? window.location.href='ogrenciler.php?delete=<?php echo $row['ogrenci_id']; ?>' : false">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Kayıt bulunamadı</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <nav aria-label="Sayfalama">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">Önceki</a>
                                        </li>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">Sonraki</a>
                                        </li>
                                    </ul>
                                </nav>
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