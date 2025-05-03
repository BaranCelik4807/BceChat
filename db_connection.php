<?php
$servername = "sql207.infinityfree.com"; // MySQL sunucu adı
$username = "if0_36582151";              // MySQL kullanıcı adı
$password = "BDA6YM50DBY";               // MySQL şifre
$dbname = "if0_36582151_bceChat";        // MySQL veritabanı adı

// Bağlantıyı oluştur
$conn = new mysqli($servername, $username, $password, $dbname, 3306);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}
?>
