<?php
include 'db_connection.php';

// Oturum bilgilerini başlat
session_start();

// Oturum açmış kullanıcının gerçek ID'sini al
$user_id = $_SESSION['user_id'];

// Mesajları almak için SQL sorgusu
$sql = "SELECT m.*, sender.username AS sender_username, receiver.username AS receiver_username 
        FROM messages m 
        LEFT JOIN users sender ON m.sender_id = sender.id 
        LEFT JOIN users receiver ON m.receiver_id = receiver.id 
        WHERE m.sender_id = ? OR m.receiver_id = ? 
        ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$stmt->close();
$conn->close();

// Alınan mesajları kullanıcıya göstermek için döngü
foreach ($messages as $message) {
    $sender_username = $message['sender_username'];
    $receiver_username = $message['receiver_username'];
    $message_content = $message['message'];
    // Mesajları ekrana yazdırma veya işleme devam etme...
}
?>
