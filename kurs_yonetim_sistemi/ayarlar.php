<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Yetki kontrolü (sadece Admin erişebilir)
if (!checkPermission(['Admin'])) {
    setMessage('error', 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
    header("Location: dashboard.php");
    exit;
}

// Kullanıcı listesi
$query = "SELECT * FROM kullanicilar ORDER BY kullanici_adi";
$result = $conn->query($query);

// Kullanıcı silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Admin hesabını silmeye çalışıyor mu?
    if ($id === 1) {
        setMessage('error', 'Ana admin hesabı silinemez.');
    } else {
        // Kendisini silmeye çalışıyor mu?
        if ($id === (int)$_SESSION['user_id']) {
            setMessage('error', 'Kendi hesabınızı silemezsiniz.');
        } else {
            $deleteQuery = "DELETE FROM kullanicilar WHERE kullanici_id = $id";

            if ($conn->query($deleteQuery)) {
                setMessage('success', 'Kullanıcı başarıyla silindi.');
            } else {
                setMessage('error', 'Kullanıcı silinirken bir hata oluştu: ' . $conn->error);
            }
        }
    }

    // Sayfayı yenile
    header("Location: ayarlar.php");
    exit;
}

// Aktif/Pasif durumunu değiştirme
if (isset($_GET['toggle']) && isset($_GET['status'])) {
    $id = (int)$_GET['toggle'];
    $status = $_GET['status'] === 'Aktif' ? 'Pasif' : 'Aktif';

    // Admin hesabını devre dışı bırakmaya çalışıyor mu?
    if ($id === 1 && $status === 'Pasif') {
        setMessage('error', 'Ana admin hesabı pasif yapılamaz.');
    } else {
        // Kendisini devre dışı bırakmaya çalışıyor mu?
        if ($id === (int)$_SESSION['user_id'] && $status === 'Pasif') {
            setMessage('error', 'Kendi hesabınızı pasif yapamazsınız.');
        } else {
            $updateQuery = "UPDATE kullanicilar SET durum = '$status' WHERE kullanici_id = $id";

            if ($conn->query($updateQuery)) {
                setMessage('success', 'Kullanıcı durumu başarıyla güncellendi.');
            } else {
                setMessage('error', 'Kullanıcı durumu güncellenirken bir hata oluştu: ' . $conn->error);
            }
        }
    }

    // Sayfayı yenile
    header("Location: ayarlar.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Sistem Ayarları</h2>
                    <a href="kullanici_ekle.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i> Yeni Kullanıcı
                    </a>
                </div>

                <?php echo showMessage(); ?>

                <!-- Kullanıcı Yönetimi -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-users-cog me-1"></i> Kullanıcı Yönetimi
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Kullanıcı Adı</th>
                                        <th>Ad Soyad</th>
                                        <th>E-posta</th>
                                        <th>Rol</th>
                                        <th>Son Giriş</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $row['kullanici_id']; ?></td>
                                                <td><?php echo $row['kullanici_adi']; ?></td>
                                                <td><?php echo $row['ad'] . ' ' . $row['soyad']; ?></td>
                                                <td><?php echo $row['email']; ?></td>
                                                <td><?php echo $row['rol']; ?></td>
                                                <td><?php echo $row['son_giris'] ? formatDateTime($row['son_giris']) : '-'; ?></td>
                                                <td><?php echo formatStatus($row['durum']); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="kullanici_duzenle.php?id=<?php echo $row['kullanici_id']; ?>" class="btn btn-primary" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <?php if ($row['kullanici_id'] !== 1 && $row['kullanici_id'] !== $_SESSION['user_id']): ?>
                                                            <a href="ayarlar.php?toggle=<?php echo $row['kullanici_id']; ?>&status=<?php echo $row['durum']; ?>" class="btn btn-warning" title="Durumu Değiştir">
                                                                <i class="fas fa-exchange-alt"></i>
                                                            </a>

                                                            <a href="#" class="btn btn-danger" title="Sil" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?') ? window.location.href='ayarlar.php?delete=<?php echo $row['kullanici_id']; ?>' : false">
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
                    </div>
                </div>

                <!-- Sistem Bilgileri -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-1"></i> Sistem Bilgileri
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="card-title">Kurs Yönetim Sistemi</h5>
                                <p class="card-text">Versiyon: 1.0.0</p>
                                <p class="card-text">PHP Versiyonu: <?php echo phpversion(); ?></p>
                                <p class="card-text">MySQL Versiyonu: <?php echo $conn->server_info; ?></p>
                                <p class="card-text">Sunucu: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="card-title">Veritabanı Özeti</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Öğrenci
                                        <span class="badge bg-primary rounded-pill"><?php echo getCount($conn, 'ogrenciler'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Öğretmen
                                        <span class="badge bg-primary rounded-pill"><?php echo getCount($conn, 'ogretmenler'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Ders
                                        <span class="badge bg-primary rounded-pill"><?php echo getCount($conn, 'dersler'); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Yoklama
                                        <span class="badge bg-primary rounded-pill"><?php echo getCount($conn, 'yoklamalar'); ?></span>
                                    </li>
                                </ul>
                            </div>
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