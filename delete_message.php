<?php
include 'db_connection.php'; // Veritabanı bağlantısını sağladığınız dosyanın adını buraya ekleyin

// POST isteği ile gelen mesaj ID'sini alın
if(isset($_POST['message_id'])) {
    $messageId = $_POST['message_id'];

    // Veritabanında mesajı silme sorgusunu hazırlayın
    $sql = "DELETE FROM messages WHERE id = $messageId";

    if ($conn->query($sql) === TRUE) {
        // Başarılı bir şekilde silindiğinde geri bildirim gönderin
        echo "Mesaj başarıyla silindi!";
    } else {
        // Silme sırasında bir hata oluştuysa hata mesajı gönderin
        echo "Mesaj silinirken bir hata oluştu: " . $conn->error;
    }

    // Veritabanı bağlantısını kapatın
    $conn->close();
} else {
    // Eğer mesaj ID'si POST isteği ile gönderilmediyse, hata mesajı gönderin
    echo "Mesaj ID'si sağlanmadı!";
}
?>
