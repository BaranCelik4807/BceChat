<?php
include 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $receiver_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM signaling WHERE receiver_id = ? ORDER BY timestamp ASC");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    $conn->close();
    echo json_encode($messages);
}
?>
