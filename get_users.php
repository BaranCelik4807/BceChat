<?php
// Veritabanı bağlantı bilgilerini içe aktar
include 'db_connection.php';

// Veritabanından kullanıcı bilgilerini sorgula
$sql = "SELECT username, email FROM users";
$result = $conn->query($sql);

// Sorgu sonuçlarını kontrol et
if ($result->num_rows > 0) {
    // Veritabanından gelen her satırı işle
    while($row = $result->fetch_assoc()) {
        echo "Kullanıcı Adı: " . $row["username"]. " - E-posta: " . $row["email"]. "<br>";
    }
} else {
    echo "Veritabanında hiç kullanıcı bulunamadı.";
}

// Veritabanı bağlantısını kapat
$conn->close();
?>
