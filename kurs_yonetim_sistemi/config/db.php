<?php
// Veritabanı bağlantı bilgileri
$servername = "localhost";
$username = "root"; // MySQL kullanıcı adınız
$password = ""; // MySQL şifreniz (varsayılan olarak boş)
$dbname = "kurs_yonetim_sistemi";

// Bağlantıyı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Türkçe karakter sorunu için
$conn->set_charset("utf8");
