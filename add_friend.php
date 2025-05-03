<?php
include 'db_connection.php';
session_start();

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Unauthorized");
}

// POST verilerini al
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['friend_id'])) {
    $sender_user_id = $_SESSION['user_id'];
    $receiver_user_id = $_POST['friend_id'];

    // Arkadaşlık isteği için veritabanına ekleme yap
    $sql = "INSERT INTO FriendshipInvitations (sender_user_id, receiver_user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $sender_user_id, $receiver_user_id);

    if ($stmt->execute()) {
        echo "Arkadaşlık isteği başarıyla gönderildi!";
    } else {
        echo "Arkadaşlık isteği gönderilirken bir hata oluştu.";
    }

    $stmt->close();
    $conn->close();
} else {
    header("HTTP/1.1 400 Bad Request");
    exit("Bad Request");
}
?>
