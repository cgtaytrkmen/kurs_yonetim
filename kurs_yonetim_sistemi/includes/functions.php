<?php

/**
 * Kurs Yönetim Sistemi için yardımcı fonksiyonlar
 */

/**
 * Veritabanındaki bir tablodaki kayıt sayısını döndürür
 * 
 * @param mysqli $conn Veritabanı bağlantısı
 * @param string $table Tablo adı
 * @param string $where WHERE koşulu (isteğe bağlı)
 * @return int Kayıt sayısı
 */
function getCount($conn, $table, $where = '1')
{
    $sql = "SELECT COUNT(*) as total FROM $table WHERE $where";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

/**
 * Tarihi formatlar
 * 
 * @param string $date Tarih (Y-m-d formatında)
 * @return string Formatlanmış tarih (d.m.Y formatında)
 */
function formatDate($date)
{
    return date('d.m.Y', strtotime($date));
}

/**
 * Tarih ve saati formatlar
 * 
 * @param string $datetime Tarih ve saat (Y-m-d H:i:s formatında)
 * @return string Formatlanmış tarih ve saat (d.m.Y H:i formatında)
 */
function formatDateTime($datetime)
{
    return date('d.m.Y H:i', strtotime($datetime));
}

/**
 * Güvenli bir şekilde veritabanından kayıt alır
 * 
 * @param mysqli $conn Veritabanı bağlantısı
 * @param string $table Tablo adı
 * @param int $id Kayıt ID'si
 * @param string $idColumn ID sütunu adı (varsayılan: [tablo_adı]_id)
 * @return array|bool Kayıt verisi veya false (kayıt bulunamazsa)
 */
function getRecord($conn, $table, $id, $idColumn = null)
{
    if ($idColumn === null) {
        $idColumn = substr($table, 0, -1) . '_id'; // örn: ogrenciler -> ogrenci_id
    }

    $stmt = $conn->prepare("SELECT * FROM $table WHERE $idColumn = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }

    return false;
}

/**
 * POST verilerini güvenli bir şekilde alır
 * 
 * @param string $key POST anahtarı
 * @param mixed $default Varsayılan değer
 * @return mixed POST değeri veya varsayılan değer
 */
function post($key, $default = '')
{
    return isset($_POST[$key]) ? htmlspecialchars(trim($_POST[$key])) : $default;
}

/**
 * GET verilerini güvenli bir şekilde alır
 * 
 * @param string $key GET anahtarı
 * @param mixed $default Varsayılan değer
 * @return mixed GET değeri veya varsayılan değer
 */
function get($key, $default = '')
{
    return isset($_GET[$key]) ? htmlspecialchars(trim($_GET[$key])) : $default;
}

/**
 * Başarı mesajı oluşturur
 * 
 * @param string $message Mesaj
 * @return string HTML formatında mesaj
 */
function successMessage($message)
{
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-1"></i> ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>';
}

/**
 * Hata mesajı oluşturur
 * 
 * @param string $message Mesaj
 * @return string HTML formatında mesaj
 */
function errorMessage($message)
{
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>';
}

/**
 * Bilgi mesajı oluşturur
 * 
 * @param string $message Mesaj
 * @return string HTML formatında mesaj
 */
function infoMessage($message)
{
    return '<div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-1"></i> ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>';
}

/**
 * Mesajları oturum değişkenlerinde saklar
 * 
 * @param string $type Mesaj tipi (success, error, info)
 * @param string $message Mesaj
 */
function setMessage($type, $message)
{
    $_SESSION['message_type'] = $type;
    $_SESSION['message'] = $message;
}

/**
 * Oturum değişkenlerinde saklanan mesajları gösterir ve temizler
 * 
 * @return string|null HTML formatında mesaj veya null
 */
function showMessage()
{
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'];
        $message = $_SESSION['message'];

        // Oturum değişkenlerini temizle
        unset($_SESSION['message_type']);
        unset($_SESSION['message']);

        switch ($type) {
            case 'success':
                return successMessage($message);
            case 'error':
                return errorMessage($message);
            case 'info':
                return infoMessage($message);
            default:
                return null;
        }
    }

    return null;
}

/**
 * Kullanıcının yetkisine göre erişim kontrolü yapar
 * 
 * @param string|array $allowedRoles İzin verilen roller
 * @return bool Erişim izni
 */
function checkPermission($allowedRoles)
{
    if (!isset($_SESSION['role'])) {
        return false;
    }

    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    return in_array($_SESSION['role'], $allowedRoles);
}

/**
 * Belirli bir menü öğesinin aktif olup olmadığını kontrol eder
 * 
 * @param string $menuItem Menü öğesi
 * @return string Active class veya boş string
 */
function isActiveMenu($menuItem)
{
    $currentPage = basename($_SERVER['PHP_SELF']);

    if ($menuItem === 'dashboard' && $currentPage === 'dashboard.php') {
        return 'active';
    } elseif ($menuItem === 'ogrenciler' && ($currentPage === 'ogrenciler.php' || $currentPage === 'ogrenci_ekle.php' || $currentPage === 'ogrenci_duzenle.php' || $currentPage === 'ogrenci_detay.php')) {
        return 'active';
    } elseif ($menuItem === 'veliler' && ($currentPage === 'veliler.php' || $currentPage === 'veli_ekle.php' || $currentPage === 'veli_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'ogretmenler' && ($currentPage === 'ogretmenler.php' || $currentPage === 'ogretmen_ekle.php' || $currentPage === 'ogretmen_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'dersler' && ($currentPage === 'dersler.php' || $currentPage === 'ders_ekle.php' || $currentPage === 'ders_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'siniflar' && ($currentPage === 'siniflar.php' || $currentPage === 'sinif_ekle.php' || $currentPage === 'sinif_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'donemler' && ($currentPage === 'donemler.php' || $currentPage === 'donem_ekle.php' || $currentPage === 'donem_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'ders_programi' && ($currentPage === 'ders_programi.php' || $currentPage === 'program_ekle.php' || $currentPage === 'program_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'yoklama' && ($currentPage === 'yoklama.php' || $currentPage === 'yoklama_listesi.php')) {
        return 'active';
    } elseif ($menuItem === 'mufredat' && ($currentPage === 'mufredat.php' || $currentPage === 'mufredat_ekle.php' || $currentPage === 'mufredat_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'odemeler' && ($currentPage === 'odemeler.php' || $currentPage === 'odeme_ekle.php' || $currentPage === 'odeme_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'giderler' && ($currentPage === 'giderler.php' || $currentPage === 'gider_ekle.php' || $currentPage === 'gider_duzenle.php')) {
        return 'active';
    } elseif ($menuItem === 'raporlar' && $currentPage === 'raporlar.php') {
        return 'active';
    } elseif ($menuItem === 'ayarlar' && ($currentPage === 'ayarlar.php' || $currentPage === 'kullanicilar.php' || $currentPage === 'kullanici_ekle.php' || $currentPage === 'kullanici_duzenle.php')) {
        return 'active';
    }

    return '';
}

/**
 * Para formatını düzenler
 * 
 * @param float $amount Miktar
 * @return string Formatlanmış para
 */
function formatMoney($amount)
{
    return number_format($amount, 2, ',', '.') . ' ₺';
}

/**
 * Cinsiyet bilgisini formatlı şekilde getirir
 * 
 * @param string $gender Cinsiyet (Erkek, Kadın, Diğer)
 * @return string HTML formatında cinsiyet bilgisi
 */
function formatGender($gender)
{
    switch ($gender) {
        case 'Erkek':
            return '<span class="badge bg-primary"><i class="fas fa-male me-1"></i> Erkek</span>';
        case 'Kadın':
            return '<span class="badge bg-danger"><i class="fas fa-female me-1"></i> Kadın</span>';
        case 'Diğer':
            return '<span class="badge bg-secondary"><i class="fas fa-user me-1"></i> Diğer</span>';
        default:
            return '<span class="badge bg-secondary"><i class="fas fa-question me-1"></i> Belirtilmemiş</span>';
    }
}

/**
 * Durum bilgisini formatlı şekilde getirir
 * 
 * @param string $status Durum (Aktif, Pasif)
 * @return string HTML formatında durum bilgisi
 */
function formatStatus($status)
{
    switch ($status) {
        case 'Aktif':
            return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Aktif</span>';
        case 'Pasif':
            return '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Pasif</span>';
        default:
            return '<span class="badge bg-secondary"><i class="fas fa-question me-1"></i> Belirtilmemiş</span>';
    }
}

/**
 * Yoklama durumunu formatlı şekilde getirir
 * 
 * @param string $attendance Yoklama durumu (Var, Yok, İzinli, Geç)
 * @return string HTML formatında yoklama durumu
 */
function formatAttendance($attendance)
{
    switch ($attendance) {
        case 'Var':
            return '<span class="badge bg-success"><i class="fas fa-check me-1"></i> Var</span>';
        case 'Yok':
            return '<span class="badge bg-danger"><i class="fas fa-times me-1"></i> Yok</span>';
        case 'İzinli':
            return '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle me-1"></i> İzinli</span>';
        case 'Geç':
            return '<span class="badge bg-info"><i class="fas fa-clock me-1"></i> Geç</span>';
        default:
            return '<span class="badge bg-secondary"><i class="fas fa-question me-1"></i> Belirsiz</span>';
    }
}

/**
 * Veritabanı pdo bağlantısını getirir
 * 
 * @return PDO PDO bağlantısı
 */
function getPDO()
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "kurs_yonetim_sistemi";

    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Veritabanı bağlantısı başarısız: " . $e->getMessage());
    }
}

/**
 * Güvenli bir şekilde CSV dosyası oluşturur
 * 
 * @param string $filename Dosya adı
 * @param array $data Veri dizisi
 */
function exportCSV($filename, $data)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

    // Başlıkları yaz
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));

        // Verileri yaz
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit;
}
