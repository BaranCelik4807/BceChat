<?php
// Veritabanı bağlantısı
require_once 'db_connection.php';

// Kullanıcıdan gelen verileri al
$message = $_POST['message'];

// Veritabanına mesajı ekle
$sql = "INSERT INTO messages (sender, message) VALUES ('John', '$message')";
if ($conn->query($sql) === TRUE) {
    echo "Mesaj gönderildi.";
} else {
    echo "Hata: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
