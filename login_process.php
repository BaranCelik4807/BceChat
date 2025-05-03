<?php
session_start();
include 'db_connection.php';

// Kullanıcı adı ve şifreyi al
$username = $_POST['username'];
$password = $_POST['password'];

// Örnek olarak, kullanıcı adı ve şifre doğru ise bir oturum başlatıyoruz.
if ($username === 'kullanici' && $password === 'sifre') {
    $_SESSION['username'] = $username;
    header('Location: index.php'); // Ana sayfaya yönlendir
} else {
    echo "Kullanıcı adı veya şifre hatalı!";
}
?>
