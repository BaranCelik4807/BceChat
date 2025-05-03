<?php
include 'db_connection.php';

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Get logged in user ID
$user_id = $_SESSION['user_id'];

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy the session
    session_destroy();
    // Redirect to login page
    header("Location: login.php");
    exit();
}


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

// Fetch unique friends from FriendshipInvitations and users table
$friends = [];
$sql = "SELECT DISTINCT users.username 
        FROM FriendshipInvitations 
        JOIN users ON (FriendshipInvitations.sender_user_id = users.id OR FriendshipInvitations.receiver_user_id = users.id)
        WHERE FriendshipInvitations.status = 'accepted' 
        AND (? IN (FriendshipInvitations.sender_user_id, FriendshipInvitations.receiver_user_id))";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $friends[] = $row['username'];
}

$stmt->close();
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
              body {
                overflow-x: hidden; /* Yatay scroll'u kaldır */
                margin: 0; /* Sayfa kenar boşluklarını kaldır */
                padding: 0; /* Sayfa içeriği boşluklarını kaldır */
            }
            .container {
                flex-direction: column;
            }
                .user-box {
            width: calc(100% - 250px); /* Ekran genişliğinden 250 piksel eksilt */
        }

             .chat {
        max-height: calc(100vh - 120px); /* Ekran yüksekliğinin 100 piksel eksikliğine kadar */
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
        /* User box tasarımı */
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

        

        .profile-img {
            width: 30px;
            height: 30px;
            border-radius: 50%; /* Yuvarlak profil resmi */
            margin-right: 10px; /* Kullanıcı adından önce boşluk bırak */
        }

    </style>
</head>
<body>
    <div class="container">
        <!-- Left Section: Chat icons -->
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
<!-- Logout button -->
<button class="logout-btn" id="logout-button">Çıkış Yap</button>
</div>
<!-- Middle Section: User list and search filter -->
<div class="main">
    <h2>ARKADAŞLAR</h2>
    <input type="text" placeholder="Ara...">
    <ul class="user-list">
            <!-- Dynamic list of friends will be added here -->
            <?php foreach ($friends as $friend): ?>
                <?php if ($friend !== $_SESSION['username']): ?> <!-- Oturum açmış kullanıcıyı listeden çıkar -->
                    <li class="user-box">
                        <img src="profile_pictures/<?php echo $friend; ?>.jpg" alt="Profile Picture" class="profile-img">
                        <span class="username"><?php echo $friend; ?></span>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>

        </ul>
</div>
<!-- Sağ bölüm: Mesajlaşma ekranı -->
        <div class="chat" style="display: none;">
            <div class="user-info">
                <a href="friends.php" class="back-btn">
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
<!-- Confirmation dialog for message deletion -->
<div class="confirm-delete" id="confirm-delete">
    <h3>Mesajı silmek istediğinize emin misiniz?</h3>
    <button id="confirm-yes">Evet</button>
    <button id="confirm-no">Hayır</button>
</div>


<script>

document.querySelectorAll('.user-box').forEach(function(userBox) {
    userBox.addEventListener('click', function() {
        var userId = this.dataset.userId; // Kullanıcının id'sini al
        var username = this.querySelector('.username').textContent;
        var chatHeader = document.querySelector('.chat .user-info h2');
        chatHeader.textContent = username;
        
        // Mobil cihazlarda orta bölümü gizle
        if(window.innerWidth <= 768) {
            document.querySelector('.main').style.display = 'none';
        }
        
        document.querySelector('.chat').style.display = 'block';

        // Eski mesajları yükle
        loadMessages(userId);
    });
});



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


    // Message deletion
    document.getElementById("message-container").addEventListener("click", function(event) {
        if (event.target.classList.contains("message")) {
            // Determine which message to delete
            var messageToDelete = event.target;

            // Show delete confirmation dialog
            var confirmDelete = document.getElementById("confirm-delete");
            confirmDelete.style.display = "block";

            // Add event listeners for yes and no buttons
            document.getElementById("confirm-yes").addEventListener("click", function() {
                // Delete message and hide dialog
                messageToDelete.remove();
                confirmDelete.style.display = "none";
            });

            document.getElementById("confirm-no").addEventListener("click", function() {
                // Cancel deletion and hide dialog
                confirmDelete.style.display = "none";
            });
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

