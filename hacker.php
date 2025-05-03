<?php
include 'db_connection.php';
session_start();

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Çıkış işlemi
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Messenger</title>
    <style>
        /* CSS kodları buraya */
        /* Simgelerin boyutunu 40px x 40px olarak ayarla */
.chat-icons img {
    width: 40px;
    height: 40px;
    display: block;
    margin-bottom: 10px;
}

/* Ana konteyner */
.container {
    display: flex;
    height: 100vh;
}

/* Sol bölüm */
.sidebar {
    flex: 0 0 100px; /* Sabit genişlik */
    background-color: #f3f3f3;
    padding: 20px;
    position: relative; /* Göreceli konumlandırma */
}

/* Çıkış yap butonu */
.logout-btn {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background-color: #ff6347;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Orta bölüm */
.main {
    flex: 0 0 250px; /* Sabit genişlik */
    padding: 20px;
}

/* Sağ bölüm */
.chat {
    flex: 1; /* Esnek genişlik, diğer bölümler kadar yer kapla */
    background-color: #fff;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Gölgelendirme efekti */
    position: relative; /* Göreceli konumlandırma */
}

/* Mesaj gönderme alanı */
.chat input[type="text"] {
    width: calc(100% - 150px); /* Genişlik, kenarlarda 10 piksel boşluk bırak */
    margin-bottom: 10px; /* Alt boşluk */
    padding: 10px; /* İç boşluk */
    position: absolute;
    bottom: 20px;
    left: 20px;
}

.chat button {
    padding: 12px 20px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    position: absolute; /* Göreceli konumlandırma */
    bottom: 30px; /* En alta sabitle */
    right: 20px; /* Sağa hizala */
}

/* Mesajlar */
.messages {
    max-height: 250px; /* Maksimum yükseklik */
    overflow-y: auto; /* Dikey kaydırma çubuğunu görüntüle */
    margin-bottom: 10px;
    color: red;
}

.message {
    border: 1px solid #ccc;
    padding: 10px;
    margin-bottom: 5px;
    border-radius: 5px;
    cursor: pointer; /* Silme için imleci değiştir */
    align-self: flex-end; /* Sağa yasla */
}

/* Silme onay penceresi */
.confirm-delete {
    display: none; /* Başlangıçta gizle */
    position: fixed; /* Pencereyi ekranın ortasında göstermek için */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: #fff;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    z-index: 999; /* Diğer elementlerin üzerine çıksın */
}

/* Evet ve Hayır butonları */
.confirm-delete button {
    padding: 5px 10px;
    margin-right: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Mobil tasarım için medya sorgusu */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        flex: none;
        width: 100%;
        height: auto;
        padding: 10px 0; /* Yalnızca yukarı ve aşağı boşluk bırak */
        box-sizing: border-box;
    }

    .sidebar .chat-icons {
        display: flex;
        flex-direction: row;
        justify-content: space-around;
        width: 100%;
    }

    .sidebar .chat-icons img {
        margin-bottom: 0;
    }

    .logout-btn {
        position: static;
        transform: none;
        margin-top: 10px; /* Üst boşluk */
    }

    .main {
        flex: 1;
        order: 1;
        width: 100%;
    }

    .chat {
        display: none;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="chat-icons">
                <a href="index.php"><img src="images/sms.png" alt="SMS icon"></a>
                <a href="earth.php"><img src="images/earth.png" alt="earth icon"></a>
                <a href="friends.php"><img src="images/friends.png" alt="friends icon"></a>
                <a href="notification.php"><img src="images/notification.png" alt="notification icon"></a>
                <a href="hacker.php"><img src="images/hacker.png" alt="Hat icon"></a>
                <a href="profile.php"><img src="images/profile.png" alt="Human icon"></a>
                <a href="settings.php"><img src="images/settings.png" alt="Settings icon"></a>
            </div>
            <button class="logout-btn" onclick="window.location.href='login.php?logout=true'">Çıkış Yap</button>
        </div>
        <div class="main">
            <h2>Sohbetler</h2>
            <input type="text" placeholder="Ara...">
            <ul class="user-list">
                <!-- Kullanıcılar buraya dinamik olarak eklenecek -->
            </ul>
        </div>
        <div class="chat">
            <div class="header">
                <img src="user_profile_pic.jpg" alt="User profile picture">
                <h2>Kullanıcı Adı</h2>
            </div>
            <div class="messages" id="message-container">
                <!-- Mesajlar buraya dinamik olarak eklenecek -->
            </div>
            <input type="text" id="message-input" placeholder="Mesajınızı buraya yazın...">
            <button id="send-button">Gönder</button>
        </div>
    </div>
    <div class="confirm-delete" id="confirm-delete">
        <h3>Mesajı silmek istediğinize emin misiniz?</h3>
        <button id="confirm-yes">Evet</button>
        <button id="confirm-no">Hayır</button>
    </div>

    <script>
        // JavaScript kodları buraya
        // Gönder butonuna ve mesaj giriş kutusuna etkinlik dinleyicileri ekle
document.getElementById("send-button").addEventListener("click", sendMessage);
document.getElementById("message-input").addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        sendMessage();
    }
});

// Mesaj gönderme fonksiyonu
function sendMessage() {
    var messageInput = document.getElementById("message-input");
    var messageContainer = document.getElementById("message-container");

    if (messageInput.value.trim() !== "") {
        var message = document.createElement("div");
        message.className = "message";
        message.textContent = messageInput.value;
        messageContainer.appendChild(message);

        // Temizle
        messageInput.value = "";
    }
}

// Mesaj silme işlemi
document.getElementById("message-container").addEventListener("click", function(event) {
    if (event.target.classList.contains("message")) {
        // Silinecek mesajı belirle
        var messageToDelete = event.target;

        // Silme onay penceresini göster
        var confirmDelete = document.getElementById("confirm-delete");
        confirmDelete.style.display = "block";

        // Evet ve Hayır butonlarına etkinlik dinleyicileri ekle
        document.getElementById("confirm-yes").addEventListener("click", function() {
            // Mesajı sil ve pencereyi gizle
            messageToDelete.remove();
            confirmDelete.style.display = "none";
        });

        document.getElementById("confirm-no").addEventListener("click", function() {
            // Silmeyi iptal et ve pencereyi gizle
            confirmDelete.style.display = "none";
        });
    }
});

    </script>
</body>
</html>
