<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Yetkilendirme kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

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
        // Öğrenci ekleme işlemi
        $stmt = $conn->prepare("INSERT INTO ogrenciler (ad, soyad, dogum_tarihi, cinsiyet, telefon, email, adres, durum) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $ad, $soyad, $dogum_tarihi, $cinsiyet, $telefon, $email, $adres, $durum);

        if ($stmt->execute()) {
            $ogrenci_id = $conn->insert_id;
            $success = "Öğrenci başarıyla eklendi.";

            // Veli bilgilerini ekle (eğer veli adı ve telefonu belirtilmişse)
            if (!empty($veli_ad) && !empty($veli_telefon)) {
                $veli_stmt = $conn->prepare("INSERT INTO veliler (ogrenci_id, ad, soyad, yakinlik, telefon, email, adres) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $veli_stmt->bind_param("issssss", $ogrenci_id, $veli_ad, $veli_soyad, $veli_yakinlik, $veli_telefon, $veli_email, $veli_adres);

                if ($veli_stmt->execute()) {
                    $success .= " Veli bilgileri de kaydedildi.";
                } else {
                    $error = "Öğrenci eklendi, ancak veli bilgileri eklenirken bir hata oluştu: " . $veli_stmt->error;
                }

                $veli_stmt->close();
            }

            // Yeni öğrenci ekledikten sonra formu temizle
            if (empty($error)) {
                header("Location: ogrenci_detay.php?id=$ogrenci_id&status=added");
                exit;
            }
        } else {
            $error = "Öğrenci eklenirken bir hata oluştu: " . $stmt->error;
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
    <title>Yeni Öğrenci Ekle - Kurs Yönetim Sistemi</title>
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
                    <h2 class="mt-4">Yeni Öğrenci Ekle</h2>
                    <a href="ogrenciler.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i> Öğrencilere Dön
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                    <?php echo errorMessage($error); ?>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <?php echo successMessage($success); ?>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-plus me-1"></i> Öğrenci Bilgileri
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Kişisel Bilgiler</h5>

                                    <div class="mb-3">
                                        <label for="ad" class="form-label">Adı <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ad" name="ad" required>
                                        <div class="invalid-feedback">Adı alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="soyad" class="form-label">Soyadı <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="soyad" name="soyad" required>
                                        <div class="invalid-feedback">Soyadı alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="dogum_tarihi" class="form-label">Doğum Tarihi <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="dogum_tarihi" name="dogum_tarihi" required>
                                        <div class="invalid-feedback">Doğum tarihi alanı zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="cinsiyet" class="form-label">Cinsiyet <span class="text-danger">*</span></label>
                                        <select class="form-select" id="cinsiyet" name="cinsiyet" required>
                                            <option value="">Seçiniz</option>
                                            <option value="Erkek">Erkek</option>
                                            <option value="Kadın">Kadın</option>
                                            <option value="Diğer">Diğer</option>
                                        </select>
                                        <div class="invalid-feedback">Cinsiyet seçimi zorunludur.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="telefon" class="form-label">Telefon</label>
                                        <input type="tel" class="form-control" id="telefon" name="telefon" placeholder="05xxxxxxxxx">
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-posta</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="adres" class="form-label">Adres</label>
                                        <textarea class="form-control" id="adres" name="adres" rows="3"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="durum" class="form-label">Durum</label>
                                        <select class="form-select" id="durum" name="durum">
                                            <option value="Aktif" selected>Aktif</option>
                                            <option value="Pasif">Pasif</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="mb-3">Veli Bilgileri</h5>

                                    <div class="mb-3">
                                        <label for="veli_ad" class="form-label">Veli Adı</label>
                                        <input type="text" class="form-control" id="veli_ad" name="veli_ad">
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_soyad" class="form-label">Veli Soyadı</label>
                                        <input type="text" class="form-control" id="veli_soyad" name="veli_soyad">
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_yakinlik" class="form-label">Yakınlık Derecesi</label>
                                        <select class="form-select" id="veli_yakinlik" name="veli_yakinlik">
                                            <option value="">Seçiniz</option>
                                            <option value="Anne">Anne</option>
                                            <option value="Baba">Baba</option>
                                            <option value="Ağabey">Ağabey</option>
                                            <option value="Abla">Abla</option>
                                            <option value="Amca">Amca</option>
                                            <option value="Dayı">Dayı</option>
                                            <option value="Teyze">Teyze</option>
                                            <option value="Hala">Hala</option>
                                            <option value="Dede">Dede</option>
                                            <option value="Babaanne">Babaanne</option>
                                            <option value="Anneanne">Anneanne</option>
                                            <option value="Diğer">Diğer</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_telefon" class="form-label">Veli Telefon</label>
                                        <input type="tel" class="form-control" id="veli_telefon" name="veli_telefon" placeholder="05xxxxxxxxx">
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_email" class="form-label">Veli E-posta</label>
                                        <input type="email" class="form-control" id="veli_email" name="veli_email">
                                        <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="veli_adres" class="form-label">Veli Adres</label>
                                        <textarea class="form-control" id="veli_adres" name="veli_adres" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-2">
                                    <i class="fas fa-eraser me-1"></i> Temizle
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