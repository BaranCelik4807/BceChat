<?php
// Veritabanı bağlantısını sağlayın
require_once 'db_connection.php'; // Veritabanı bağlantı dosyanızın adı ve yoluna göre güncelleyin

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Yeni şifreyi alın
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Şifrelerin eşleştiğini kontrol edin
    if ($newPassword !== $confirmPassword) {
        echo "Şifreler uyuşmuyor. Lütfen aynı şifreyi girin.";
    } else {
        // Yeni şifreyi hashleyin
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Şifreyi veritabanında güncelle
        $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE user_id = 1"; // Kullanıcıyı tanımlayan bir yöntem kullanmanız gerekebilir
        $updateResult = mysqli_query($dbConnection, $updateQuery);

        if ($updateResult) {
            echo "Şifre başarıyla güncellendi.";
        } else {
            echo "Şifre güncellenirken bir hata oluştu.";
        }
    }
}
?>
