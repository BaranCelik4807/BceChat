<?php
include 'db_connection.php';

// Kullanıcı bilgilerini al
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];

// Örnek olarak, kullanıcı bilgilerini veritabanına ekleme
$sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";

if ($conn->query($sql) === TRUE) {
    echo "Kullanıcı başarıyla oluşturuldu";
} else {
    echo "Hata: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
