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

// Arama filtresi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = "1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where = "(aciklama LIKE '%$search%' OR gider_turu LIKE '%$search%')";
}

// Tarih filtresi
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Bu ayın ilk günü
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Bu ayın son günü

if (!empty($start_date) && !empty($end_date)) {
    $where .= " AND gider_tarihi BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}

// Gider türü filtresi
$gider_turu = isset($_GET['gider_turu']) ? $_GET['gider_turu'] : '';
if (!empty($gider_turu)) {
    $gider_turu = $conn->real_escape_string($gider_turu);
    $where .= " AND gider_turu = '$gider_turu'";
}

// Sayfalama için
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Her sayfada gösterilecek kayıt sayısı
$offset = ($page - 1) * $limit;

// Toplam kayıt sayısı
$countQuery = "SELECT COUNT(*) as total FROM giderler WHERE $where";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Gider listesi
$query = "SELECT * FROM giderler WHERE $where ORDER BY gider_tarihi DESC LIMIT $offset, $limit";
$result = $conn->query($query);

// Gider türleri (filtre için)
$typesQuery = "SELECT DISTINCT gider_turu FROM giderler ORDER BY gider_turu";
$typesResult = $conn->query($typesQuery);

// Toplam gider tutarı
$totalQuery = "SELECT SUM(miktar) as toplam FROM giderler WHERE $where";
$totalResult = $conn->query($totalQuery);
$totalAmount = $totalResult->fetch_assoc()['toplam'] ?: 0;

// Gider silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $deleteQuery = "DELETE FROM giderler WHERE gider_id = $id";

    if ($conn->query($deleteQuery)) {
        setMessage('success', 'Gider kaydı başarıyla silindi.');
    } else {
        setMessage('error', 'Gider kaydı silinirken bir hata oluştu: ' . $conn->error);
    }

    // Sayfayı yenile
    header("Location: giderler.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giderler - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Giderler</h2>
                    <a href="gider_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Gider
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <!-- Filtre -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </div>
                    <div class="card-body">
                        <form method="GET" action="giderler.php" class="row g-3">
                            <div class="col-md-2">
                                <label for="search" class="form-label">Arama</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Açıklama veya tür">
                            </div>
                            <div class="col-md-2">
                                <label for="gider_turu" class="form-label">Gider Türü</label>
                                <select class="form-select" id="gider_turu" name="gider_turu">
                                    <option value="">Tümü</option>
                                    <?php while ($type = $typesResult->fetch_assoc()): ?>
                                        <option value="<?php echo $type['gider_turu']; ?>" <?php echo ($gider_turu == $type['gider_turu']) ? 'selected' : ''; ?>>
                                            <?php echo $type['gider_turu']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Başlangıç</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">Bitiş</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i> Filtrele
                                </button>
                                <a href="giderler.php" class="btn btn-secondary">
                                    <i class="fas fa-sync-alt me-1"></i> Sıfırla
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Gider Listesi -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-money-bill-wave me-1"></i> Gider Listesi
                        </div>
                        <div>
                            <span class="badge bg-danger p-2">
                                Toplam: <?php echo formatMoney($totalAmount); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tarih</th>
                                        <th>Gider Türü</th>
                                        <th>Tutar</th>
                                        <th>Açıklama</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['gider_id']; ?></td>
                                                <td><?php echo formatDateTime($row['gider_tarihi']); ?></td>
                                                <td><?php echo $row['gider_turu']; ?></td>
                                                <td><?php echo formatMoney($row['miktar']); ?></td>
                                                <td><?php echo $row['aciklama'] ?: '-'; ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="gider_duzenle.php?id=<?php echo $row['gider_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-danger" title="Sil" onclick="return confirm('Bu gider kaydını silmek istediğinize emin misiniz?') ? window.location.href='giderler.php?delete=<?php echo $row['gider_id']; ?>' : false">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
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

                        <?php if ($totalPages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <nav aria-label="Sayfalama">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&gider_turu=<?php echo urlencode($gider_turu); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">Önceki</a>
                                        </li>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&gider_turu=<?php echo urlencode($gider_turu); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&gider_turu=<?php echo urlencode($gider_turu); ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">Sonraki</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <button type="button" class="btn btn-outline-primary" onclick="exportTableToCSV('giderler.csv')">
                            <i class="fas fa-file-csv me-1"></i> CSV İndir
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Yazdır
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>