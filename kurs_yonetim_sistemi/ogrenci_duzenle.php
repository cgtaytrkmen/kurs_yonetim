<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Öğrenci ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    setMessage('error', 'Geçersiz öğrenci ID.');
    header("Location: ogrenciler.php");
    exit;
}

// Öğrenci bilgilerini al
$studentQuery = "SELECT * FROM ogrenciler WHERE ogrenci_id = $id";
$studentResult = $conn->query($studentQuery);

if ($studentResult->num_rows === 0) {
    setMessage('error', 'Öğrenci bulunamadı.');
    header("Location: ogrenciler.php");
    exit;
}

$student = $studentResult->fetch_assoc();

// Veli bilgilerini al
$parentQuery = "SELECT * FROM veliler WHERE ogrenci_id = $id";
$parentResult = $conn->query($parentQuery);
$parent = $parentResult->num_rows > 0 ? $parentResult->fetch_assoc() : null;

$error = '';
$success = '';

// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $ad = post('ad');
    $soyad = post('soyad');
    $dogum_tarihi = post('dogum_tarihi');
    $cinsiyet = post('cinsiyet');
    $telefon = post('telefon');
    $email = post('email');
    $adres = post('adres');
    $durum = post('durum', 'Aktif');

    // Veli bilgileri
    $veli_ad = post('veli_ad');
    $veli_soyad = post('veli_soyad');
    $veli_yakinlik = post('veli_yakinlik');
    $veli_telefon = post('veli_telefon');
    $veli_email = post('veli_email');
    $veli_adres = post('veli_adres');

    // Doğrulama
    if (empty($ad) || empty($soyad) || empty($dogum_tarihi) || empty($cinsiyet)) {
        $error = "Lütfen zorunlu alanları doldurun.";
    } else {
        // Öğrenci güncelleme işlemi
        $stmt = $conn->prepare("UPDATE ogrenciler SET ad = ?, soyad = ?, dogum_tarihi = ?, cinsiyet = ?, telefon = ?, email = ?, adres = ?, durum = ? WHERE ogrenci_id = ?");
        $stmt->bind_param("ssssssssi", $ad, $soyad, $dogum_tarihi, $cinsiyet, $telefon, $email, $adres, $durum, $id);

        if ($stmt->execute()) {
            $success = "Öğrenci bilgileri başarıyla güncellendi.";

            // Veli bilgilerini güncelle
            if (!empty($veli_ad) && !empty($veli_telefon)) {
                if ($parent) {
                    // Veli varsa güncelle
                    $veli_stmt = $conn->prepare("UPDATE veliler SET ad = ?, soyad = ?, yakinlik = ?, telefon = ?, email = ?, adres = ? WHERE veli_id = ?");
                    $veli_stmt->bind_param("ssssssi", $veli_ad, $veli_soyad, $veli_yakinlik, $veli_telefon, $veli_email, $veli_adres, $parent['veli_id']);
                } else {
                    // Veli yoksa yeni ekle
                    $veli_stmt = $conn->prepare("INSERT INTO veliler (ogrenci_id, ad, soyad, yakinlik, telefon, email, adres) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $veli_stmt->bind_param("issssss", $id, $veli_ad, $veli_soyad, $veli_yakinlik, $veli_telefon, $veli_email, $veli_adres);
                }

                if ($veli_stmt->execute()) {
                    $success .= " Veli bilgileri de güncellendi.";
                } else {
                    $error = "Öğrenci güncellendi, ancak veli bilgileri güncellenirken bir hata oluştu: " . $veli_stmt->error;
                }

                $veli_stmt->close();
            }

            // Başarılı güncelleme sonrası bilgileri yenile
            $studentResult = $conn->query($studentQuery);
            $student = $studentResult->fetch_assoc();

            if ($parent) {
                $parentResult = $conn->query($parentQuery);
                $parent = $parentResult->fetch_assoc();
            }
        } else {
            $error = "Öğrenci güncellenirken bir hata oluştu: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Düzenle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Öğrenci Düzenle</h2>
                    <div>
                        <a href="ogrenci_detay.php?id=<?php echo $id; ?>" class="btn btn-outline-primary me-2">
                            <i class="fas fa-eye me-1"></i> Detaylar
                        </a>
                        <a href="ogrenciler.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Listeye Dön
                        </a>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                    <?php echo errorMessage($error); ?>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <?php echo successMessage($success); ?>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-edit me-1"></i> Öğrenci Bilgileri Düzenle
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Kişisel Bilgiler</h5>

                                    <div class="mb-3">
                                        <label for="ad" class="form-label">Adı <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ad" name="ad" value="<?php echo $student['ad']; ?>" required>
                                        <div class="invalid-feedback">Adı alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="soyad" class="form-label">Soyadı <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="soyad" name="soyad" value="<?php echo $student['soyad']; ?>" required>
                                        <div class="invalid-feedback">Soyadı alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="dogum_tarihi" class="form-label">Doğum Tarihi <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="dogum_tarihi" name="dogum_tarihi" value="<?php echo $student['dogum_tarihi']; ?>" required>
                                        <div class="invalid-feedback">Doğum tarihi alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="cinsiyet" class="form-label">Cinsiyet <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cinsiyet" name="cinsiyet" required>
                                            <option value="">Seçiniz</option>
                                            <option value="Erkek" <?php echo ($student['cinsiyet'] == 'Erkek') ? 'selected' : ''; ?>>Erkek</option>
                                            <option value="Kadın" <?php echo ($student['cinsiyet'] == 'Kadın') ? 'selected' : ''; ?>>Kadın</option>
                                            <option value="Diğer" <?php echo ($student['cinsiyet'] == 'Diğer') ? 'selected' : ''; ?>>Diğer</option>
                                        </select>
                                        <div class="invalid-feedback">Cinsiyet seçimi zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="telefon" class="form-label">Telefon</label>
                                        <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo $student['telefon']; ?>" placeholder="05xxxxxxxxx">
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $student['email']; ?>">
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="adres" class="form-label">Adres</label>
                                        <textarea class="form-control" id="adres" name="adres" rows="3"><?php echo $student['adres']; ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="durum" class="form-label">Durum</label>
                                        <select class="form-select" id="durum" name="durum">
                                            <option value="Aktif" <?php echo ($student['durum'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="Pasif" <?php echo ($student['durum'] == 'Pasif') ? 'selected' : ''; ?>>Pasif</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="mb-3">Veli Bilgileri</h5>

                                    <div class="mb-3">
                                        <label for="veli_ad" class="form-label">Veli Adı</label>
                                        <input type="text" class="form-control" id="veli_ad" name="veli_ad" value="<?php echo $parent ? $parent['ad'] : ''; ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_soyad" class="form-label">Veli Soyadı</label>
                                        <input type="text" class="form-control" id="veli_soyad" name="veli_soyad" value="<?php echo $parent ? $parent['soyad'] : ''; ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_yakinlik" class="form-label">Yakınlık Derecesi</label>
                                        <select class="form-select" id="veli_yakinlik" name="veli_yakinlik">
                                            <option value="">Seçiniz</option>
                                            <option value="Anne" <?php echo ($parent && $parent['yakinlik'] == 'Anne') ? 'selected' : ''; ?>>Anne</option>
                                            <option value="Baba" <?php echo ($parent && $parent['yakinlik'] == 'Baba') ? 'selected' : ''; ?>>Baba</option>
                                            <option value="Ağabey" <?php echo ($parent && $parent['yakinlik'] == 'Ağabey') ? 'selected' : ''; ?>>Ağabey</option>
                                            <option value="Abla" <?php echo ($parent && $parent['yakinlik'] == 'Abla') ? 'selected' : ''; ?>>Abla</option>
                                            <option value="Amca" <?php echo ($parent && $parent['yakinlik'] == 'Amca') ? 'selected' : ''; ?>>Amca</option>
                                            <option value="Dayı" <?php echo ($parent && $parent['yakinlik'] == 'Dayı') ? 'selected' : ''; ?>>Dayı</option>
                                            <option value="Teyze" <?php echo ($parent && $parent['yakinlik'] == 'Teyze') ? 'selected' : ''; ?>>Teyze</option>
                                            <option value="Hala" <?php echo ($parent && $parent['yakinlik'] == 'Hala') ? 'selected' : ''; ?>>Hala</option>
                                            <option value="Dede" <?php echo ($parent && $parent['yakinlik'] == 'Dede') ? 'selected' : ''; ?>>Dede</option>
                                            <option value="Babaanne" <?php echo ($parent && $parent['yakinlik'] == 'Babaanne') ? 'selected' : ''; ?>>Babaanne</option>
                                            <option value="Anneanne" <?php echo ($parent && $parent['yakinlik'] == 'Anneanne') ? 'selected' : ''; ?>>Anneanne</option>
                                            <option value="Diğer" <?php echo ($parent && $parent['yakinlik'] == 'Diğer') ? 'selected' : ''; ?>>Diğer</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_telefon" class="form-label">Veli Telefon</label>
                                        <input type="tel" class="form-control" id="veli_telefon" name="veli_telefon" value="<?php echo $parent ? $parent['telefon'] : ''; ?>" placeholder="05xxxxxxxxx">
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_email" class="form-label">Veli E-posta</label>
                                        <input type="email" class="form-control" id="veli_email" name="veli_email" value="<?php echo $parent ? $parent['email'] : ''; ?>">
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_adres" class="form-label">Veli Adres</label>
                                        <textarea class="form-control" id="veli_adres" name="veli_adres" rows="3"><?php echo $parent ? $parent['adres'] : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-2">
                                    <i class="fas fa-undo me-1"></i> Değişiklikleri Geri Al
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>

</html>