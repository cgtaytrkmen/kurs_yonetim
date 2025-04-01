<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Filtreleme parametreleri
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Tarih formatı kontrolü
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)) {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
}

// Ders programlarını al
$programsQuery = "SELECT dp.program_id, d.ders_adi, s.sinif_adi, CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dp.gun, 
                  TIME_FORMAT(dp.baslangic_saat, '%H:%i') as baslangic, TIME_FORMAT(dp.bitis_saat, '%H:%i') as bitis
                 FROM ders_programi dp
                 JOIN dersler d ON dp.ders_id = d.ders_id
                 JOIN siniflar s ON dp.sinif_id = s.sinif_id
                 JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                 ORDER BY dp.gun, dp.baslangic_saat";
$programsResult = $conn->query($programsQuery);

// Yoklama kayıtlarını al
$yoklamaQuery = "SELECT y.yoklama_id, y.ders_tarihi, dp.program_id, d.ders_adi, s.sinif_adi, 
                CONCAT(o.ad, ' ', o.soyad) as ogretmen_adi, dp.gun,
                COUNT(yd.yoklama_detay_id) as ogrenci_sayisi,
                SUM(CASE WHEN yd.durum = 'Var' THEN 1 ELSE 0 END) as var_sayisi,
                SUM(CASE WHEN yd.durum = 'Yok' THEN 1 ELSE 0 END) as yok_sayisi,
                SUM(CASE WHEN yd.durum = 'İzinli' THEN 1 ELSE 0 END) as izinli_sayisi,
                SUM(CASE WHEN yd.durum = 'Geç' THEN 1 ELSE 0 END) as gec_sayisi
                FROM yoklamalar y
                JOIN ders_programi dp ON y.program_id = dp.program_id
                JOIN dersler d ON dp.ders_id = d.ders_id
                JOIN siniflar s ON dp.sinif_id = s.sinif_id
                JOIN ogretmenler o ON dp.ogretmen_id = o.ogretmen_id
                LEFT JOIN yoklama_detay yd ON y.yoklama_id = yd.yoklama_id
                WHERE y.ders_tarihi BETWEEN '$start_date' AND '$end_date'";

// Program ID filtresi ekle
if ($program_id > 0) {
    $yoklamaQuery .= " AND dp.program_id = $program_id";
}

$yoklamaQuery .= " GROUP BY y.yoklama_id, y.ders_tarihi, dp.program_id, d.ders_adi, s.sinif_adi, ogretmen_adi, dp.gun
                   ORDER BY y.ders_tarihi DESC, dp.gun, dp.baslangic_saat";

$yoklamaResult = $conn->query($yoklamaQuery);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoklama Listesi - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yoklama Listesi</h2>
                    <a href="ders_programi.php" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-1"></i> Ders Programı
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <!-- Filtreler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-filter me-1"></i> Yoklama Filtreleri
                    </div>
                    <div class="card-body">
                        <form method="GET" action="yoklama_listesi.php" class="row g-3">
                            <div class="col-md-4">
                                <label for="program_id" class="form-label">Ders Programı</label>
                                <select class="form-select" id="program_id" name="program_id">
                                    <option value="0">Tümü</option>
                                    <?php while ($program = $programsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $program['program_id']; ?>" <?php echo ($program_id == $program['program_id']) ? 'selected' : ''; ?>>
                                            <?php echo $program['ders_adi'] . ' - ' . $program['sinif_adi'] . ' (' . $program['gun'] . ' ' . $program['baslangic'] . '-' . $program['bitis'] . ')'; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Filtrele
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Yoklama Listesi -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-clipboard-list me-1"></i> Yoklama Kayıtları
                            <span class="badge bg-primary ms-2"><?php echo $yoklamaResult->num_rows; ?> kayıt</span>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Yazdır
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($yoklamaResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="yoklamaTable">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Ders</th>
                                            <th>Sınıf</th>
                                            <th>Öğretmen</th>
                                            <th>Gün</th>
                                            <th>Katılım</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($yoklama = $yoklamaResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo formatDate($yoklama['ders_tarihi']); ?></td>
                                                <td><?php echo $yoklama['ders_adi']; ?></td>
                                                <td><?php echo $yoklama['sinif_adi']; ?></td>
                                                <td><?php echo $yoklama['ogretmen_adi']; ?></td>
                                                <td><?php echo $yoklama['gun']; ?></td>
                                                <td>
                                                    <div class="progress">
                                                        <?php
                                                        $total = $yoklama['ogrenci_sayisi'];
                                                        $varYuzde = $total > 0 ? ($yoklama['var_sayisi'] / $total * 100) : 0;
                                                        $gecYuzde = $total > 0 ? ($yoklama['gec_sayisi'] / $total * 100) : 0;
                                                        $izinliYuzde = $total > 0 ? ($yoklama['izinli_sayisi'] / $total * 100) : 0;
                                                        $yokYuzde = $total > 0 ? ($yoklama['yok_sayisi'] / $total * 100) : 0;
                                                        ?>
                                                        <div class="progress-bar bg-success" style="width: <?php echo $varYuzde; ?>%" title="Var: <?php echo round($varYuzde, 1); ?>%"></div>
                                                        <div class="progress-bar bg-info" style="width: <?php echo $gecYuzde; ?>%" title="Geç: <?php echo round($gecYuzde, 1); ?>%"></div>
                                                        <div class="progress-bar bg-warning" style="width: <?php echo $izinliYuzde; ?>%" title="İzinli: <?php echo round($izinliYuzde, 1); ?>%"></div>
                                                        <div class="progress-bar bg-danger" style="width: <?php echo $yokYuzde; ?>%" title="Yok: <?php echo round($yokYuzde, 1); ?>%"></div>
                                                    </div>
                                                    <small>
                                                        <span class="text-success"><?php echo $yoklama['var_sayisi']; ?> var</span>,
                                                        <span class="text-info"><?php echo $yoklama['gec_sayisi']; ?> geç</span>,
                                                        <span class="text-warning"><?php echo $yoklama['izinli_sayisi']; ?> izinli</span>,
                                                        <span class="text-danger"><?php echo $yoklama['yok_sayisi']; ?> yok</span>
                                                    </small>
                                                </td>
                                                <td>
                                                    <a href="yoklama_detay.php?id=<?php echo $yoklama['yoklama_id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i> Detay
                                                    </a>
                                                    <a href="yoklama.php?program_id=<?php echo $yoklama['program_id']; ?>&date=<?php echo $yoklama['ders_tarihi']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i> Seçilen kriterlere uygun yoklama kaydı bulunamadı.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bilgi Kartı -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-1"></i> Yoklama Hakkında Bilgi
                    </div>
                    <div class="card-body">
                        <p>Bu sayfada tüm yoklama kayıtlarını görüntüleyebilir, filtreleyebilir ve yönetebilirsiniz.</p>
                        <ul>
                            <li><strong>Detay:</strong> Yoklamanın detaylarını ve öğrenci bazlı bilgileri görüntüler.</li>
                            <li><strong>Düzenle:</strong> Yoklama için öğrenci durumlarını değiştirmenizi sağlar.</li>
                            <li><strong>Katılım:</strong> İlgili derse katılım oranlarını gösterir.</li>
                        </ul>
                        <p>Yeni bir yoklama almak için önce ders programından ilgili dersi seçmelisiniz.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>