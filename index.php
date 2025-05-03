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

    // Mesajın daha önce işlenip işlenmediğini kontrol etmek için işlem işaretleyicisi (flag)
    $message_processed = false;

    // Mesajın daha önce işlenip işlenmediğini kontrol et
    if (!$message_processed) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $current_user_id, $recipient_user_id, $message);
        if ($stmt->execute()) {
            // İşlem tamamlandığında işaretle
            $message_processed = true;
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }

    // PRG Deseni: Yönlendirme yap
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
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
        /* Genel stil */
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


        /* Mobil cihazlar için ek CSS */
        @media (max-width: 768px) {
            body {
            overflow-x: hidden; /* Yatay scroll'u kaldır */
            margin: 0; /* Sayfa kenar boşluklarını kaldır */
            padding: 0; /* Sayfa içeriği boşluklarını kaldır */
        }

            .chat {
                max-height: calc(100vh - 120px); /* Ekran yüksekliğinin 100 piksel eksikliğine kadar */
            }
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
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
            .user-box {
            width: calc(100% - 250px); /* Ekran genişliğinden 250 piksel eksilt */
        }

            
        }
        .user-list {
            list-style-type: none; /* Liste işaretlerini kaldır */
            padding: 0;
        }

        /* Yeni user-box sınıfı */
        .user-box {
            background-color: #f0f0f0;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .user-box span {
            text-decoration: none;
            color: black;
        }


        .profile-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
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
    <div class="container">
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
            <button class="logout-btn" onclick="logout()">Çıkış Yap</button>
        </div>
        <div class="main">
            <h2>Sohbetler</h2>
            <input type="text" placeholder="Ara...">
            <ul class="user-list">
                <?php
                // Kullanıcı kutularını döngüyle oluştururken, etkinlik ekleyin
                foreach ($chats as $chat) {
                    echo "<li onclick='openChat(" . $chat['chat_user_id'] . ")'>
                            <div class='user-box' data-user-id='" . $chat['chat_user_id'] . "'>
                                <img class='profile-img' src='user_profile_pic.jpg' alt='Profile Picture'>
                                <span>" . htmlspecialchars($chat['chat_username']) . "</span>
                            </div>
                        </li>";
                }
                ?>
            </ul>

        </div>
        <!-- Sağ bölüm: Mesajlaşma ekranı -->
        <div class="chat" style="display: none;">
            <div class="user-info">
                <a href="earth.php" class="back-btn">
                    <img src="images/back.png" style="height:30px;width:30px;" alt="Geri">
                </a>
                <img src="images/user-profile.jpg" alt="User profile picture">
                <h2 style="color:orange;"><?php
                 if (isset($_GET['chat_user_id'])) {
                     $chat_user_id = $_GET['chat_user_id'];
                     $sql = "SELECT username FROM users WHERE id = ?";
                     $stmt = $conn->prepare($sql);
                     $stmt->bind_param("i", $chat_user_id);
                     $stmt->execute();
                     $stmt->store_result();
                     if ($stmt->num_rows > 0) {
                         $stmt->bind_result($username);
                         $stmt->fetch();
                         echo htmlspecialchars($username);
                     } else {
                         echo "Kullanıcı Adı";
                     }
                 } else {
                     echo "Kullanıcı Adı";
                 }
                 ?></h2>
                <img src="images/phone.png" alt="Ara" class="call-btn">
                <img src="images/video-camera.png" alt="Live" class="live-btn">
            </div>
<div class="messages" id="message-container">
<?php
             if (isset($_GET['chat_user_id'])) {
                 $chat_user_id = $_GET['chat_user_id'];
                 $sql = "SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC";
                 $stmt = $conn->prepare($sql);
                 $stmt->bind_param("iiii", $current_user_id, $chat_user_id, $chat_user_id, $current_user_id);
                 $stmt->execute();
                 $result = $stmt->get_result();
                 if ($result->num_rows > 0) {
                     while ($row = $result->fetch_assoc()) {
                         $message_class = $row['sender_id'] == $current_user_id ? 'my-message' : 'other-message';
                         echo "<div class='message $message_class'>" . htmlspecialchars($row['message']) . "</div>";
                     }
                 } else {
                     echo "No messages found";
                 }
             }
             ?>
</div>
<form method="POST" action="">
<input type="hidden" name="receiver_id" value="<?php echo $chat_user_id; ?>">
<input type="text" id="message-input" name="message" placeholder="Mesajınızı buraya yazın...">
<button type="submit" id="send-button">Gönder</button>
</form>
</div>
</div>
<div class="confirm-delete" id="confirm-delete">
<h3>Mesajı silmek istediğinize emin misiniz?</h3>
<button id="confirm-yes">Evet</button>
<button id="confirm-no">Hayır</button>
</div>
<script>

// Mesaj gönderme işlevi
function sendMessage() {
    var messageInput = document.getElementById("message-input");
    var messageContainer = document.getElementById("message-container");
    var receiverId = document.querySelector('.chat').dataset.receiverId;

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
                } else {
                    // Hata durumunda kullanıcıya bilgi ver
                    console.error("Mesaj gönderilirken bir hata oluştu. Durum kodu:", xhr.status);
                    console.error("Hata mesajı:", xhr.statusText);
                }
            }
        };

        // POST verisi hazırla ve isteği gönder
        xhr.send('message=' + encodeURIComponent(messageContent) + '&receiver_id=' + encodeURIComponent(receiverId));
    }
}
// Gönder butonuna tıklama işlevi
document.getElementById("send-button").addEventListener("click", function(event) {
    event.preventDefault(); // Sayfanın yenilenmesini önle
    sendMessage(); // Mesaj gönderme işlevini çağır
    clearMessageInput(); // Mesaj giriş kutusunu temizle
});
// Mesaj giriş kutusunu temizleme işlevi
function clearMessageInput() {
    document.getElementById("message-input").value = ""; // Mesaj giriş kutusunu temizle
}

// 2. openChat fonksiyonu
// Kullanıcı adı veya profil resmi tıklama işlevi
document.querySelectorAll('.user-box').forEach(function(userBox) {
    userBox.addEventListener('click', function() {
        var chatUserId = this.getAttribute('data-user-id');
        openChat(chatUserId);
    });
});

// 2. openChat fonksiyonu
function openChat(chatUserId) {
    var userBox = document.querySelector('.user-box[data-user-id="' + chatUserId + '"]');
    var username = userBox.querySelector('span').textContent; // 'span' içeriğini al
    var profileImgSrc = userBox.querySelector('.profile-img').src;

    var chatContainer = document.querySelector('.chat');
    var headerH2 = chatContainer.querySelector('.user-info h2');
    var headerImg = chatContainer.querySelector('.user-info img');

    chatContainer.style.display = 'block';
    headerH2.textContent = username;
    headerImg.src = profileImgSrc;
    chatContainer.dataset.receiverId = chatUserId;

    loadMessages(chatUserId);

    if (window.innerWidth <= 768) {
        document.querySelector('.main').style.display = 'none';
    }
}



// 3. loadMessages fonksiyonu
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

                    if (message.sender_id == <?php echo $_SESSION['user_id']; ?>) {
                        messageElement.style.color = "#ff0000";
                    } else {
                        messageElement.style.color = "#0000ff";
                    }

                    messageContainer.appendChild(messageElement);
                });

                messageContainer.scrollTop = messageContainer.scrollHeight;
            } else {
                alert("Mesajlar yüklenirken bir hata oluştu.");
            }
        }
    };

    xhr.send();
}



document.getElementById("message-container").addEventListener("click", function(event) {
    if (event.target.classList.contains("message")) {
        var messageToDelete = event.target;
        var confirmDelete = document.getElementById("confirm-delete");
        confirmDelete.style.display = "block";

        document.getElementById("confirm-yes").addEventListener("click", function() {
            messageToDelete.remove();
            confirmDelete.style.display = "none";
        });

        document.getElementById("confirm-no").addEventListener("click", function() {
            confirmDelete.style.display = "none";
        });
    }
});

document.querySelector(".back-btn").addEventListener("click", function(event) {
    event.preventDefault();

    if (window.innerWidth <= 768) {
        document.querySelector('.main').style.display = 'block';
        document.querySelector('.chat').style.display = 'none';
    }
});

// 5. Çıkış yapma fonksiyonu
function logout() {
    if (confirm('Çıkış yapmak istediğinize emin misiniz?')) {
        window.location.href = 'index.php?logout=true';
    }
}

// 6. Hacker butonu event listener'ı
document.getElementById("hacker-button").addEventListener("click", function() {
    var overlay = document.createElement("div");
    overlay.className= "overlay";
overlay.innerHTML = "<div class='overlay-content'>Şu anda anonim moddasınız.</div>";
document.body.appendChild(overlay);
    var closeButton = document.createElement("button");
    closeButton.textContent = "Tamam";
    closeButton.className = "close-button";
    var closeIcon = document.createElement("img");
    closeIcon.src = "images/close.png";
    closeIcon.className = "close-icon";

    var closeContainer = document.createElement("div");
    closeContainer.className = "close-container";
    closeContainer.appendChild(closeIcon);
    closeContainer.appendChild(closeButton);

    document.body.appendChild(closeContainer);

    closeButton.addEventListener("click", function() {
        overlay.remove();
        closeContainer.remove();
    });
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

