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
    $where = "(ad LIKE '%$search%' OR soyad LIKE '%$search%' OR uzmanlik_alani LIKE '%$search%')";
}

// Durum filtresi
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
if ($status != 'all') {
    $status = $conn->real_escape_string($status);
    $where .= " AND durum = '$status'";
}

// Öğretmen listesi
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM ders_programi WHERE ogretmen_id = o.ogretmen_id) as program_count
          FROM ogretmenler o WHERE $where ORDER BY ad, soyad";
$result = $conn->query($query);

// Öğretmen silme işlemi
if (isset($_GET['delete']) && checkPermission(['Admin', 'Yönetici'])) {
    $id = (int)$_GET['delete'];

    // Öğretmene bağlı ders programları var mı?
    $checkQuery = "SELECT COUNT(*) as count FROM ders_programi WHERE ogretmen_id = $id";
    $checkResult = $conn->query($checkQuery);
    $hasPrograms = $checkResult->fetch_assoc()['count'] > 0;

    if ($hasPrograms) {
        setMessage('error', 'Bu öğretmene ait ders programı kayıtları bulunmaktadır. Önce bu kayıtları silmelisiniz.');
    } else {
        $deleteQuery = "DELETE FROM ogretmenler WHERE ogretmen_id = $id";

        if ($conn->query($deleteQuery)) {
            setMessage('success', 'Öğretmen başarıyla silindi.');
        } else {
            setMessage('error', 'Öğretmen silinirken bir hata oluştu: ' . $conn->error);
        }
    }

    // Sayfayı yenile
    header("Location: ogretmenler.php");
    exit;
}

// Aktif/Pasif durumunu değiştirme
if (isset($_GET['toggle']) && isset($_GET['status'])) {
    $id = (int)$_GET['toggle'];
    $status = $_GET['status'] === 'Aktif' ? 'Pasif' : 'Aktif';

    $updateQuery = "UPDATE ogretmenler SET durum = '$status' WHERE ogretmen_id = $id";

    if ($conn->query($updateQuery)) {
        setMessage('success', 'Öğretmen durumu başarıyla güncellendi.');
    } else {
        setMessage('error', 'Öğretmen durumu güncellenirken bir hata oluştu: ' . $conn->error);
    }

    // Sayfayı yenile
    header("Location: ogretmenler.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğretmenler - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Öğretmenler</h2>
                    <a href="ogretmen_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Öğretmen
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-search me-1"></i> Öğretmen Ara
                    </div>
                    <div class="card-body">
                        <form method="GET" action="ogretmenler.php" class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="İsim, soyisim veya uzmanlık alanı" value="<?php echo htmlspecialchars($search); ?>">
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
                                <a href="ogretmenler.php" class="btn btn-outline-secondary w-100">Sıfırla</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i> Öğretmen Listesi
                        <span class="badge bg-primary ms-2"><?php echo $result->num_rows; ?> kayıt</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Adı Soyadı</th>
                                        <th>Telefon</th>
                                        <th>E-posta</th>
                                        <th>Uzmanlık Alanı</th>
                                        <th>Başlangıç Tarihi</th>
                                        <th>Ders Sayısı</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['ogretmen_id']; ?></td>
                                                <td><?php echo $row['ad'] . ' ' . $row['soyad']; ?></td>
                                                <td><?php echo $row['telefon'] ?: '-'; ?></td>
                                                <td><?php echo $row['email'] ?: '-'; ?></td>
                                                <td><?php echo $row['uzmanlik_alani'] ?: '-'; ?></td>
                                                <td><?php echo $row['baslangic_tarihi'] ? formatDate($row['baslangic_tarihi']) : '-'; ?></td>
                                                <td>
                                                    <?php if ($row['program_count'] > 0): ?>
                                                        <span class="badge bg-success"><?php echo $row['program_count']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo formatStatus($row['durum']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="ogretmen_detay.php?id=<?php echo $row['ogretmen_id']; ?>" class="btn btn-info" title="Detay">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="ogretmen_duzenle.php?id=<?php echo $row['ogretmen_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="ogretmenler.php?toggle=<?php echo $row['ogretmen_id']; ?>&status=<?php echo $row['durum']; ?>" class="btn btn-warning" title="Durumu Değiştir">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici']) && $row['program_count'] == 0): ?>
                                                            <a href="#" class="btn btn-danger" title="Sil" onclick="return confirm('Bu öğretmeni silmek istediğinize emin misiniz?') ? window.location.href='ogretmenler.php?delete=<?php echo $row['ogretmen_id']; ?>' : false">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">Kayıt bulunamadı</td>
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