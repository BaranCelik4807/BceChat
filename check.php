<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];

    // E-posta ve kullanıcı adı veritabanında eşleşip eşleşmediğini kontrol etme
    $sql = "SELECT * FROM users WHERE email='$email' AND username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // E-posta ve kullanıcı adı eşleşiyor, reset.php sayfasına yönlendirme
        header("Location: reset_password.php");
        exit();
    } else {
        // E-posta ve kullanıcı adı eşleşmiyor, hata mesajı gösterme
        echo "E-posta ve kullanıcı adı eşleşmedi.";
    }
}
?>
