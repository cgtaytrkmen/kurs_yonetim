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
    $where = "(o.ad LIKE '%$search%' OR o.soyad LIKE '%$search%')";
}

// Dönem filtresi
$donem_id = isset($_GET['donem_id']) ? (int)$_GET['donem_id'] : 0;
if ($donem_id > 0) {
    $where .= " AND p.donem_id = $donem_id";
}

// Tarih filtreleri
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Bu ayın ilk günü
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Bu ayın son günü

if (!empty($start_date) && !empty($end_date)) {
    $where .= " AND p.odeme_tarihi BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}

// Sayfalama için
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15; // Her sayfada gösterilecek kayıt sayısı
$offset = ($page - 1) * $limit;

// Toplam kayıt sayısı
$countQuery = "SELECT COUNT(*) as total 
               FROM odemeler p
               JOIN ogrenciler o ON p.ogrenci_id = o.ogrenci_id
               LEFT JOIN donemler d ON p.donem_id = d.donem_id
               WHERE $where";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Ödeme listesi
$query = "SELECT p.*, o.ad, o.soyad, d.donem_adi 
          FROM odemeler p
          JOIN ogrenciler o ON p.ogrenci_id = o.ogrenci_id
          LEFT JOIN donemler d ON p.donem_id = d.donem_id
          WHERE $where
          ORDER BY p.odeme_tarihi DESC
          LIMIT $offset, $limit";
$result = $conn->query($query);

// Dönemler listesi (filtre için)
$donemsQuery = "SELECT * FROM donemler ORDER BY baslangic_tarihi DESC";
$donemsResult = $conn->query($donemsQuery);

// Toplam ödeme tutarı
$toplamQuery = "SELECT SUM(miktar) as toplam 
               FROM odemeler p
               JOIN ogrenciler o ON p.ogrenci_id = o.ogrenci_id
               WHERE $where";
$toplamResult = $conn->query($toplamQuery);
$toplam = $toplamResult->fetch_assoc()['toplam'] ?: 0;
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödemeler - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Ödemeler</h2>
                    <a href="odeme_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Yeni Ödeme
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <!-- Filtre -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter me-1"></i> Filtrele
                    </div>
                    <div class="card-body">
                        <form method="GET" action="odemeler.php" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Öğrenci Adı</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Öğrenci adı, soyadı">
                            </div>
                            <div class="col-md-3">
                                <label for="donem_id" class="form-label">Dönem</label>
                                <select class="form-select" id="donem_id" name="donem_id">
                                    <option value="0">Tüm Dönemler</option>
                                    <?php while ($donem = $donemsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $donem['donem_id']; ?>" <?php echo ($donem_id == $donem['donem_id']) ? 'selected' : ''; ?>>
                                            <?php echo $donem['donem_adi']; ?>
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
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i> Filtrele
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ödeme Listesi -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-money-bill-wave me-1"></i> Ödeme Listesi
                        </div>
                        <div>
                            <span class="badge bg-success p-2">
                                Toplam: <?php echo formatMoney($toplam); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Öğrenci</th>
                                        <th>Dönem</th>
                                        <th>Tutar</th>
                                        <th>Ödeme Türü</th>
                                        <th>Açıklama</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo formatDateTime($row['odeme_tarihi']); ?></td>
                                                <td>
                                                    <a href="ogrenci_detay.php?id=<?php echo $row['ogrenci_id']; ?>">
                                                        <?php echo $row['ad'] . ' ' . $row['soyad']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $row['donem_adi'] ?: '-'; ?></td>
                                                <td><?php echo formatMoney($row['miktar']); ?></td>
                                                <td><?php echo $row['odeme_turu']; ?></td>
                                                <td><?php echo $row['aciklama'] ?: '-'; ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="odeme_detay.php?id=<?php echo $row['odeme_id']; ?>" class="btn btn-info" title="Detay">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
                                                            <a href="odeme_duzenle.php?id=<?php echo $row['odeme_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                                <i class="fas fa-edit"></i>
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

                        <?php if ($totalPages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <nav aria-label="Sayfalama">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&donem_id=<?php echo $donem_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">Önceki</a>
                                        </li>

                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&donem_id=<?php echo $donem_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&donem_id=<?php echo $donem_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">Sonraki</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <button type="button" class="btn btn-outline-success" onclick="printTable('payment-table', 'Ödeme Listesi')">
                            <i class="fas fa-print me-1"></i> Yazdır
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="exportTableToCSV('payment-table', 'odemeler.csv')">
                            <i class="fas fa-file-csv me-1"></i> CSV İndir
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