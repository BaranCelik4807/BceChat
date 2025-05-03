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

// Kullanıcıları ve ilgili profil resimlerini almak için SQL sorgusu
$sql = "SELECT u.id, u.username, 
               COALESCE(
                   (SELECT i.image_url 
                    FROM images i 
                    WHERE i.user_id = u.id AND i.status = 1 
                    ORDER BY i.image_id DESC 
                    LIMIT 1),
                   'path_to_default_profile_pic.jpg') AS image_url
        FROM users u
        ORDER BY u.id"; // Kullanıcıları ID'ye göre sırala

$result = $conn->query($sql);
$userData = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $userId = $row['id'];
        $username = htmlspecialchars($row['username']);
        $imageUrl = htmlspecialchars($row['image_url']);
        $userData[$userId] = [
            'username' => $username,
            'image_url' => $imageUrl
        ];
    }
} else {
    echo "0 results";
}
$conn->close(); // Veritabanı bağlantısını kapat
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Messenger</title>
    <style>
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
        max-height: calc(100vh - 400px); /* Ekran yüksekliğinin 200 piksel eksiliği kadar */
        overflow-y: auto; /* Dikey scrollbar göster */
        margin-bottom: 10px; /* Alt boşluk */
        flex-grow: 1; /* Mevcut boş alanı kapla */
        
    }

        .message {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
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
    body {
        overflow-x: hidden; /* Yatay scroll'u kaldır */
        margin: 0; /* Sayfa kenar boşluklarını kaldır */
        padding: 0; /* Sayfa içeriği boşluklarını kaldır */
    }
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

        .user-list {
            list-style-type: none; /* Liste işaretlerini kaldır */
            padding: 0;
        }

        .user-box {
            display: flex;
            align-items: center;
            background-color: #f3f3f3; /* Açık gri */
            width: 250px;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 3px;
        }

        .user-box img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .user-box .username {
            color: Black;
            flex-grow: 1;
        }

        .user-box .add-friend-btn {
            border: none;
        }

        .chat .call-btn {
        position: absolute;
        top: 35px;
        right: 90px;
        width: 50px;
        height: 50px;
            
        }

        .chat .live-btn {
            position: absolute;
            
            right: 120px;
            width: 50px;
            height: 50px;
            margin: 40px; /* Sağa ve üste biraz boşluk ekle */
        }

            .chat .back-btn {
                position: absolute;
                top: 20px;
                right: 22px;
            }
            .chat .user-info {
                background-color: #f3f3f3;
                padding: 10px;
                display: flex;
                align-items: center;

                border-radius: 5px;
                margin-bottom: 10px;
            }

            .chat .user-info img {
                width: 50px;
                height: 50px;
                border-radius: 50%;
            }



    </style>
</head>
<body>
    <!-- 3 düşey parça içeren ana konteyner -->
    <div class="container">
        <!-- Sol bölüm: Sohbetler ve ikonlar -->
        <div class="sidebar">
            <div class="chat-icons">
                <a href="index.php"><img src="images/sms.png" alt="SMS icon"></a>
                <a href="earth.php"><img src="images/earth.png" alt="earth icon"></a>
                <a href="friends.php"><img src="images/friends.png" alt="friends icon"></a>
                <a href="notification.php"><img src="images/notification.png" alt="notification icon"></a>
                <a id="hacker-button" href="#"><img src="images/hacker.png" alt="Hacker icon"></a>
                <a href="profile.php"><img src="images/profile.png" alt="Human icon"></a>
                <a href="settings.php"><img src="images/settings.png" alt="Settings icon"></a>
            </div>
            <!-- Çıkış yap butonu -->
            <button class="logout-btn" onclick="window.location.href='?logout=true'">Çıkış Yap</button>
        </div>
        <!-- Orta bölüm: Kullanıcılar listesi ve arama filtresi -->
        <div class="main">
            <h2>GLOBAL</h2>
            <input type="text" placeholder="Ara...">
            <ul class="user-list">
                <?php foreach ($userData as $userId => $user) : ?>
                    <?php if ($userId != $_SESSION['user_id']) : ?>
                        <li class="user-box">
                            <img src="<?php echo $user['image_url']; ?>" alt="Profile Picture">
                            <span class="username"><?php echo $user['username']; ?></span>
                            <!-- Arkadaş ekle butonu -->
                            <button class="add-friend-btn" data-user-id="<?php echo $userId; ?>">
                                <img src="images/add-friends.png" alt="Arkadaş Ekle">
                            </button>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <!-- Sağ bölüm: Mesajlaşma ekranı -->
        <div class="chat" style="display: none;">
            <div class="user-info">
                <a href="earth.php" class="back-btn">
                    <img src="images/back.png" style="height:30px;width:30px;" alt="Geri">
                </a>
                <img src="images/user-profile.jpg" alt="User profile picture">
                <h2 style="color:orange;">Kullanıcı Adı</h2>
                <img src="images/phone.png" alt="Ara" class="call-btn">
                <img src="images/video-camera.png" alt="Live" class="live-btn">
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

        var chatHeader = document.querySelector(".chat .user-info");
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

        document.querySelectorAll(".add-friend-btn").forEach(button => {
            button.addEventListener("click", function() {
                var friendId = this.dataset.userId; // Arkadaşın user_id'sini al
                addFriend(friendId, this); // Arkadaşı ekleme fonksiyonunu çağır, düğmeyi de ile
            });
        });

        function addFriend(friendId, button) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "add_friend.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        // Sunucudan gelen yanıtı göster
                        alert(xhr.responseText);
                        // Eğer işlem başarılıysa düğmenin resmini kaldır
                        if (xhr.responseText === "Arkadaş başarıyla eklendi!") {
                            button.querySelector('img').style.display = 'none';
                        }
                    } else {
                        // Sunucuya istek yapılamadığında veya başarısız olduğunda hata mesajı göster
                        alert("Arkadaş eklenirken bir hata oluştu.");
                    }
                }
            };
            xhr.send("friend_id=" + friendId); // Arkadaşın user_id'sini sunucuya gönder
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
var refreshInterval = 5000; // Örneğin, her 5 saniyede bir yenile

// Zamanlayıcıyı başlat
function startAutoRefresh() {
    setInterval(function() {
        var receiverId = document.querySelector('.chat').dataset.receiverId;
        if (receiverId) {
            loadMessages(receiverId);
        }
    }, refreshInterval);
}

// Masaüstü ve mobil cihazlarda otomatik yenilemeyi etkinleştir
function initAutoRefresh() {
    if (window.innerWidth <= 768) {
        startAutoRefresh();
    } else {
        // Eğer mobil değilse her zaman yenile
        startAutoRefresh();
    }
}

// Sayfa yüklendiğinde otomatik yenilemeyi başlat
window.addEventListener('load', initAutoRefresh);



    </script>
</body>
</html>
