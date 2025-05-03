<?php
// Veritabanı bağlantısı
include 'db_connection.php';

// Oturumu başlat
session_start();

// Kullanıcı girişi yapılmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    // Giriş yapılmamışsa, giriş sayfasına yönlendir
    header("Location: login.php");
    exit();
}

// Oturumu sonlandırma işlemini kontrol et
if (isset($_GET['logout'])) {
    // Oturumu sonlandır
    session_destroy();
    // Giriş sayfasına yönlendir
    header("Location: login.php");
    exit();
}

// Arkadaşlık isteklerini işleme alma (kabul/reddet)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action == 'accept') {
        // Arkadaşlık isteğini kabul et
        $sql_update = "UPDATE FriendshipInvitations SET status = 'accepted' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $request_id);
        $stmt_update->execute();
        $stmt_update->close();

        // İsteği gönderen ve alıcı kullanıcı ID'lerini al
        $sql_request = "SELECT sender_user_id, receiver_user_id FROM FriendshipInvitations WHERE id = ?";
        $stmt_request = $conn->prepare($sql_request);
        $stmt_request->bind_param("i", $request_id);
        $stmt_request->execute();
        $stmt_request->bind_result($sender_user_id, $receiver_user_id);
        $stmt_request->fetch();
        $stmt_request->close();

        // Arkadaşlığı Friends tablosuna ekle
        $sql_insert_friendship = "INSERT INTO Friends (user_id1, user_id2) VALUES (?, ?)";
        $stmt_insert_friendship = $conn->prepare($sql_insert_friendship);
        $stmt_insert_friendship->bind_param("ii", $sender_user_id, $receiver_user_id);
        $stmt_insert_friendship->execute();
        $stmt_insert_friendship->close();
    } elseif ($action == 'reject') {
        // Arkadaşlık isteğini reddet
        $sql_update = "UPDATE FriendshipInvitations SET status = 'rejected' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $request_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
}

// Mevcut kullanıcı için bekleyen arkadaşlık davetlerini sorgula
$sql_invitations = "SELECT fi.id, fi.sender_user_id, u.username FROM FriendshipInvitations fi
                    INNER JOIN users u ON fi.sender_user_id = u.id
                    WHERE fi.receiver_user_id = ? AND fi.status = 'pending'";
$stmt_invitations = $conn->prepare($sql_invitations);
$stmt_invitations->bind_param("i", $_SESSION['user_id']);
$stmt_invitations->execute();
$result_invitations = $stmt_invitations->get_result();
$stmt_invitations->close();

// Bekleyen istek sayısını hesapla
$num_invitations = $result_invitations->num_rows;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Messenger</title>
    <style>
        /* Genel stiller */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        /* Yan menü stil */
        .sidebar {
            flex: 0 0 100px;
            background-color: #f3f3f3;
            padding: 20px;
            position: relative;
        }

        .chat-icons {
            display: flex;
            flex-direction: column;
        }

        .chat-icons a {
            margin-bottom: 10px;
            display: block;
            text-align: center;
        }

        .chat-icons img {
            width: 40px;
            height: 40px;
            display: block;
            margin: 0 auto;
        }

        /* Çıkış butonu stil */
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

        /* Ana içerik stil */
        .main {
            flex: 1;
            padding: 20px;
        }

        .main h2 {
            margin-bottom: 20px;
        }

        /* Bildirim simgesi için stil */
        .badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 5px;
            font-size: 12px;
        }

        /* Mobil cihazlar için medya sorgusu */
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
                flex: none;
                width: 100%;
                height: auto;
                padding: 10px 0;
                box-sizing: border-box;
                text-align: center;
            }

            .sidebar .chat-icons {
                flex-direction: row;
                justify-content: space-around;
            }

            .sidebar .chat-icons a {
                margin-bottom: 0;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Yan Menü -->
        <div class="sidebar">
            <div class="chat-icons">
                <!-- Yan menü simgeleri -->
                <a href="index.php"><img src="images/sms.png" alt="SMS simgesi"></a>
                <a href="earth.php"><img src="images/earth.png" alt="Dünya simgesi"></a>
                <a href="friends.php"><img src="images/friends.png" alt="Arkadaşlar simgesi"></a>
                <a href="notification.php" style="position: relative;">
                    <img src="images/notification.png" alt="Bildirim simgesi">
                    <?php if ($num_invitations > 0): ?>
                        <span class="badge"><?php echo $num_invitations; ?></span>
                    <?php endif; ?>
                </a>
                <a id="hacker-button" href="#"><img src="images/hacker.png" alt="Hacker simgesi"></a>
                <a href="profile.php"><img src="images/profile.png" alt="İnsan simgesi"></a>
                <a href="settings.php"><img src="images/settings.png" alt="Ayarlar simgesi"></a>
            </div>
            <!-- Çıkış butonu -->
            <button class="logout-btn" onclick="window.location.href='login.php'">Çıkış Yap</button>
        </div>

        <!-- Ana İçerik -->
        <div class="main">
            <h2>Arkadaşlık İstekleri</h2>
            <?php if ($result_invitations->num_rows > 0): ?>
                <ul>
                    <?php while ($row_invitation = $result_invitations->fetch_assoc()): ?>
                        <li>
                            GÖNDEREN: <?php echo $row_invitation['username']; ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="request_id" value="<?php echo $row_invitation['id']; ?>">
                                <button type="submit" name="action" value="accept">
                                    <img src="images/accept.png" alt="Onayla" width="25" height="25">
                                </button>
                                <button type="submit" name="action" value="reject">
                                    <img src="images/rejected.png" alt="Reddet" width="25" height="25">
                                </button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>Bekleyen Arkadaşlık isteğiniz bulunmamaktadır..</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
