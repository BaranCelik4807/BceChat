<?php
// Veritabanı bağlantı bilgilerini içe aktar
include 'db_connection.php';

// Veritabanından tüm kullanıcıları sorgula
$sql = "SELECT * FROM users";
$result = $conn->query($sql);

// Sorgu sonuçlarını kontrol et
if ($result->num_rows > 0) {
    // Veritabanından gelen her satırı işle
    while($row = $result->fetch_assoc()) {
        echo "Kullanıcı ID: " . $row["id"]. "<br>";
        echo "Kullanıcı Adı: " . $row["username"]. "<br>";
        echo "E-posta: " . $row["email"]. "<br>";
        // Diğer kullanıcı bilgilerini buraya ekleyebilirsiniz
        echo "<hr>"; // Her kullanıcı arasına bir çizgi ekle
    }
} else {
    echo "Veritabanında hiç kullanıcı bulunamadı.";
}

// Veritabanı bağlantısını kapat
$conn->close();
?>
