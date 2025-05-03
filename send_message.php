<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Oturum açılmamış";
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];
$timestamp = date('Y-m-d H:i:s');

$sql = "INSERT INTO messages (sender_id, receiver_id, message, timestamp) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $sender_id, $receiver_id, $message, $timestamp);

if ($stmt->execute()) {
    echo "Mesaj başarıyla gönderildi";
} else {
    echo "Mesaj gönderilirken bir hata oluştu: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
