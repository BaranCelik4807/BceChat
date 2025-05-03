<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

// Oturum bilgilerini başlat
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

// Giriş yapmış kullanıcı ID'sini belirleyin
$current_user_id = $_SESSION['user_id'];

// Mesaj gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['receiver_id'])) {
    $recipient_user_id = $_POST['receiver_id'];
    $message = $_POST['message'];
    $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $current_user_id, $recipient_user_id, $message);
    if ($stmt->execute()) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Kullanıcıların sohbet ettiği kişilerin listesini çekme
$sql = "SELECT DISTINCT 
            CASE 
                WHEN sender_id = ? THEN receiver_id 
                WHEN receiver_id = ? THEN sender_id 
            END AS chat_user_id,
            users.username AS chat_username 
        FROM messages 
        JOIN users ON users.id = CASE 
                                    WHEN sender_id = ? THEN receiver_id 
                                    WHEN receiver_id = ? THEN sender_id 
                                END
        WHERE sender_id = ? OR receiver_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiii", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$chats = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $chats[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Messenger</title>
    <style>
        /* CSS codes */
        .chat-icons img {
            width: 40px;
            height: 40px;
            display: block;
            margin-bottom: 10px;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            flex: 0 0 100px;
            background-color: #f3f3f3;
            padding: 20px;
            position: relative;
        }

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

        .main {
            flex: 0 0 250px;
            padding: 20px;
        }

        .chat {
            flex: 1;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .chat input[type="text"] {
            width: calc(100% - 150px);
            margin-bottom: 10px;
            padding: 10px;
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
            position: absolute;
            bottom: 30px;
            right: 20px;
        }

        .messages {
            max-height: 250px;
            overflow-y: auto;
            margin-bottom: 10px;
        }

        .message {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            cursor: pointer;
        }

        .my-message {
            background-color: #e0f7fa;
        }

        .other-message {
            background-color: #d1c4e9;
        }

        .confirm-delete {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            z-index: 999;
        }

        .confirm-delete button {
            padding: 5px 10px;
            margin-right: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                flex: none;
                width: 100%;
                height: auto;
                padding: 10px 0;
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
                margin-top: 10px;
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

        /* Çıkış yap onay penceresi */
        .confirm-logout {
            display: none;
            /* Başlangıçta gizle */
            position: fixed;
            /* Pencereyi ekranın ortasında göstermek için */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            z-index: 999;
            /* Diğer elementlerin üzerine çıksın */
        }

        .overlay {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 10px;
            z-index: 999;
            color: white;
        }

        .overlay-content {
            text-align: center;
        }

        .close-container {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        .close-icon {
            width: 30px;
            height: 30px;
            cursor: pointer;
            margin-right: 5px;
        }

        .user-list {
            list-style-type: none;
            /* Liste işaretlerini kaldır */
            padding: 0;
        }

        .user-box {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center; /* Profil resmi ve kullanıcı adını dikey hizala */
        }

        .user-box:hover {
            background-color: #e0e0e0;
        }
        .user-box a {
    text-decoration: none; /* Alt çizgiyi kaldır */
    color: black; /* Yazı rengini siyah yap */
}


        .profile-img {
            width: 30px;
            height: 30px;
            border-radius: 50%; /* Yuvarlak profil resmi */
            margin-right: 10px; /* Kullanıcı adından önce boşluk bırak */
        }
      chat.back-btn{
            width: 30px;
            height: 30px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="chat-icons">
                <!-- Profil resmi eklenecek -->
                <a href="index.php"><img src="images/sms.png" alt="SMS icon"></a>
                <a href="earth.php"><img src="images/earth.png" alt="earth icon"></a>
                <a href="friends.php"><img src="images/friends.png" alt="friends icon"></a>
                <a href="notification.php"><img src="images/notification.png" alt="notification icon"></a>
                <a id="hacker-button" href="#"><img src="images/hacker.png" alt="Hacker">
                <a href="profile.php"><img src="images/profile.png" alt="Human icon"></a>
                <a href="settings.php"><img src="images/settings.png" alt="Settings icon"></a>
            </div>
            <button class="logout-btn" onclick="logout()">Çıkış Yap</button>
        </div>
        <div class="main">
            <h2>Sohbetler</h2>
            <input type="text" placeholder="Ara...">
            <ul class="user-list">
                <?php
                foreach ($chats as $chat) {
                    echo "<li><div class='user-box' onclick='openChat(" . $chat['chat_user_id'] . ")'><img class='profile-img' src='user_profile_pic.jpg' alt='Profile Picture'><a href='?chat_user_id=" . $chat['chat_user_id'] . "' style='text-decoration: none; color: black;'>" . htmlspecialchars($chat['chat_username']) . "</a></div></li>
";
                }
                ?>
            </ul>
        </div>
        <!-- Sağ bölüm: Mesajlaşma ekranı -->
        <div class="chat" style="display: none;">
            <div class="header">
            
                <img src="" alt="User profile picture">
                <h2></h2>
            </div>
            <div class="messages" id="message-container">
                <!-- Mesajlar buraya dinamik olarak eklenecek -->
            </div>
            <input type="text" id="message-input" placeholder="Mesajınızı buraya yazın...">
            <button id="send-button">Gönder</button>

        </div>
         <!-- Silme onay penceresi -->
    <div class="confirm-delete" id="confirm-delete">
        <h3>Mesajı silmek istediğinize emin misiniz?</h3>
        <button id="confirm-yes">Evet</button>
        <button id="confirm-no">Hayır</button>
    </div>

    <script>
        // Mesaj gönderme fonksiyonu
        function sendMessage() {
            var messageInput = document.getElementById("message-input");
            var messageContainer = document.getElementById("message-container");
            var receiverId = document.querySelector(".chat").dataset.receiverId;

            if (messageInput.value.trim() !== "") {
                var messageContent = messageInput.value.trim(); // Mesaj içeriğini al

                // XMLHttpRequest nesnesi oluştur
                var xhr = new XMLHttpRequest();
                
                // Veritabanına mesajı ekleme işlemi için POST isteği gönder
                xhr.open('POST', 'send_message.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                // İsteği göndermeden önce hazırlık yap
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == XMLHttpRequest.DONE) {
                        if (xhr.status == 200) {
                            // Mesajları güncelle
                            loadMessages(receiverId);
                            // Giriş kutusunu temizle
                            messageInput.value = "";
                        } else {
                            // Hata durumunda kullanıcıya bilgi ver
                            alert("Mesaj gönderilirken bir hata oluştu.");
                        }
                    }
                };

                // POST verisi hazırla ve isteği gönder
                xhr.send('message=' + messageContent + '&receiver_id=' + receiverId);
            }
        }

   function loadMessages(receiverId) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_messages.php?receiver_id=' + receiverId, true);

    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
            if (xhr.status == 200) {
                var messages = JSON.parse(xhr.responseText);
                var messageContainer = document.getElementById("message-container");
                messageContainer.innerHTML = "";

                messages.forEach(function(message) {
                    var messageElement = document.createElement("div");
                    messageElement.className = "message";
                    messageElement.textContent = message.message;

                    // Mesajı gönderen kullanıcının ID'si ile oturum açan kullanıcının ID'sini karşılaştır
                    if (message.sender_id == <?php echo $_SESSION['user_id']; ?>) {
                        // Oturum açan kullanıcı mesajı göndermişse, metin rengini kırmızı yap
                        messageElement.style.color = "#ff0000";
                    } else {
                        // Alıcı kullanıcı mesajı göndermişse, metin rengini mavi yap
                        messageElement.style.color = "#0000ff";
                    }

                    messageContainer.appendChild(messageElement);
                });

                // En alttaki mesaja kaydır
                messageContainer.scrollTop = messageContainer.scrollHeight;
            } else {
                alert("Mesajlar yüklenirken bir hata oluştu.");
            }
        }
    };

    xhr.send();
}



      document.querySelector(".user-list").addEventListener("click", function(event) {
    var clickedElement = event.target;
    if (clickedElement.classList.contains("user-box") || clickedElement.parentNode.classList.contains("user-box")) {
        var userBox = clickedElement.closest(".user-box");

        var username = userBox.querySelector(".username").textContent;
        var profilePic = userBox.querySelector("img").src;
        var receiverId = userBox.querySelector(".add-friend-btn").dataset.userId;

        var chatHeader = document.querySelector(".chat .header");
        chatHeader.querySelector("h2").textContent = username;
        chatHeader.querySelector("img").src = profilePic;

        var chat = document.querySelector('.chat');
        chat.style.display = 'block';
        chat.dataset.receiverId = receiverId;

        loadMessages(receiverId);

        // Mobil cihazlarda orta bölümü gizle
        if (window.innerWidth <= 768) {
            document.querySelector('.main').style.display = 'none';
        }
    }
});


        document.getElementById("send-button").addEventListener("click", sendMessage);
        document.getElementById("message-input").addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                sendMessage();
            }
        });

        function logout() {
            if (confirm('Çıkış yapmak istediğinize emin misiniz?')) {
                window.location.href = 'index.php?logout=true';
            }
        }

     function openChat(userId) {
    // Sohbet ekranını göster
    var chat = document.querySelector('.chat');
    chat.style.display = 'block';

    // Sohbetin alıcı kullanıcısını ayarla
    chat.dataset.receiverId = userId;

    // Sohbet başlığını ve resmini güncelle
    var chatHeader = chat.querySelector(".header");
    chatHeader.querySelector("h2").textContent = document.querySelector('.user-box[data-user-id="' + userId + '"] .username').textContent;
    chatHeader.querySelector("img").src = document.querySelector('.user-box[data-user-id="' + userId + '"] img').src;

    // Mesajları yükle
    loadMessages(userId);

    // Mobil cihazlarda orta bölümü gizle
    if (window.innerWidth <= 768) {
        document.querySelector('.main').style.display = 'none';
    }
}

        document.getElementById("hacker-button").addEventListener("click", function() {
            // Ekranın ortasında bir overlay oluştur
            var overlay = document.createElement("div");
            overlay.className = "overlay";
            overlay.innerHTML = "<div class='overlay-content'>Şu anda anonim moddasınız.</div>";
            document.body.appendChild(overlay);

            // Sağ üst köşeye resim ve buton ekle
            var closeButton = document.createElement("button");
            closeButton.textContent = "Tamam";
            closeButton.className = "close-button";
            var closeIcon = document.createElement("img");
            closeIcon.src = "images/close.png"; // Kapatma işareti resmi
            closeIcon.className = "close-icon";

            var closeContainer = document.createElement("div");
            closeContainer.className = "close-container";
            closeContainer.appendChild(closeIcon);
            closeContainer.appendChild(closeButton);

            document.body.appendChild(closeContainer);

            // Kapatma butonuna tıklanınca overlay ve kapatma öğelerini kaldır
            closeButton.addEventListener("click", function() {
                overlay.remove();
                closeContainer.remove();
            });
        });
        
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
        // Sol üst köşedeki geri butonuna tıklandığında
document.querySelector(".back-btn").addEventListener("click", function(event) {
    event.preventDefault(); // Sayfanın yeniden yüklenmesini önle

    // Mobil cihazlarda orta bölümü göster, chat ekranını gizle
    if (window.innerWidth <= 768) {
        document.querySelector('.main').style.display = 'block';
        document.querySelector('.chat').style.display = 'none';
    }
});

// Mesajları yenileme aralığı (milisaniye cinsinden)
var refreshInterval = 100; // Örneğin, her 5 saniyede bir yenile

// Zamanlayıcıyı başlat
function startAutoRefresh() {
    setInterval(function() {
        var receiverId = document.querySelector('.chat').dataset.receiverId;
        loadMessages(receiverId);
    }, refreshInterval);
}

// Masaüstü ve mobil cihazlarda otomatik yenilemeyi etkinleştir
if (window.innerWidth <= 768) {
    startAutoRefresh();
} else {
    // Eğer mobil değilse her zaman yenile
    startAutoRefresh();
}

    </script>
</body>
</html>
