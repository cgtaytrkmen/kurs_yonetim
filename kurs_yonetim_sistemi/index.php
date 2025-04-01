<?php
session_start();
require_once 'config/db.php';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
// Form gönderilmişse
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    // Geçici olarak direkt giriş için aşağıdaki kodu ekleyin (sonra kaldırmayı unutmayın!)
    if ($username == "admin" && $password == "admin123") {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['full_name'] = 'Admin Kullanıcı';
        $_SESSION['role'] = 'Admin';
        header("Location: dashboard.php");
        exit;
    }

    if (empty($username) || empty($password)) {
        $error = "Kullanıcı adı ve şifre gereklidir.";
    } else {
        // Kullanıcı adına göre veritabanından kullanıcıyı sorgula
        $stmt = $conn->prepare("SELECT kullanici_id, kullanici_adi, sifre, ad, soyad, rol FROM kullanicilar WHERE kullanici_adi = ? AND durum = 'Aktif'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Şifre kontrolü (password_verify ile hash'lenmiş şifreyi kontrol et)
            if (password_verify($password, $user['sifre'])) {
                // Giriş başarılı, oturum bilgilerini ayarla
                $_SESSION['user_id'] = $user['kullanici_id'];
                $_SESSION['username'] = $user['kullanici_adi'];
                $_SESSION['full_name'] = $user['ad'] . ' ' . $user['soyad'];
                $_SESSION['role'] = $user['rol'];

                // Son giriş tarihini güncelle
                $update_stmt = $conn->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE kullanici_id = ?");
                $update_stmt->bind_param("i", $user['kullanici_id']);
                $update_stmt->execute();

                // Ana sayfaya yönlendir
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Geçersiz kullanıcı adı veya şifre.";
            }
        } else {
            $error = "Geçersiz kullanıcı adı veya şifre.";
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
    <title>Kurs Yönetim Sistemi - Giriş</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo i {
            font-size: 60px;
            color: #3498db;
        }

        .login-form .form-control {
            height: 50px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        .login-btn {
            height: 50px;
            background-color: #3498db;
            color: #fff;
            font-weight: bold;
            border: none;
        }

        .login-btn:hover {
            background-color: #2980b9;
        }

        .error-message {
            color: #e74c3c;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-graduation-cap"></i>
            <h2 class="mt-2">Kurs Yönetim Sistemi</h2>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="username" placeholder="Kullanıcı Adı" required>
                </div>
            </div>
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="Şifre" required>
                </div>
            </div>
            <button type="submit" class="btn login-btn w-100">GİRİŞ YAP</button>
        </form>
        <div class="text-center mt-3">
            <small class="text-muted">© <?php echo date('Y'); ?> Kurs Yönetim Sistemi</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>