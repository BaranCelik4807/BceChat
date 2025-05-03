<?php
// Oturum bilgilerini başlat
session_start();

// Oturum bilgilerini temizleyin
$_SESSION = array();

// Oturumu sonlandırın
session_destroy();

// login.php sayfasına yönlendirin
header("Location: login.php");
exit();
?>
