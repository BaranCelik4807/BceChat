<?php
include 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = 'images/profile_pictures/';
    $imageFileType = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    $allowedExtensions = array("jpg", "jpeg", "png");
    if (!in_array($imageFileType, $allowedExtensions)) {
        echo json_encode(["status" => "error", "message" => "Sadece JPG, JPEG ve PNG dosyaları yüklenebilir."]);
        exit();
    }

    $profilePicture = uniqid() . '.' . $imageFileType;
    $uploadFile = $uploadDir . $profilePicture;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
        chmod($uploadFile, 0644);

        $userId = $_SESSION['user_id'];
        $imageStatus = 1;

        // Set the status of the previous profile picture to 0
        $sqlUpdateOldImages = "UPDATE images SET status = 0 WHERE user_id = ? AND status = 1";
        $stmtUpdateOldImages = $conn->prepare($sqlUpdateOldImages);
        if ($stmtUpdateOldImages) {
            $stmtUpdateOldImages->bind_param("i", $userId);
            $stmtUpdateOldImages->execute();
            $stmtUpdateOldImages->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Eski profil resminin durumu güncellenirken bir hata oluştu."]);
            exit();
        }

        // Insert the new profile picture
        $sqlInsertImage = "INSERT INTO images (user_id, image_url, status) VALUES (?, ?, ?)";
        $stmtInsertImage = $conn->prepare($sqlInsertImage);
        if ($stmtInsertImage) {
            $stmtInsertImage->bind_param("isi", $userId, $profilePicture, $imageStatus);
            if ($stmtInsertImage->execute()) {
                $_SESSION['profile_picture'] = $uploadDir . $profilePicture;

                $sqlUpdateUser = "UPDATE users SET profile_picture = ? WHERE id = ?";
                $stmtUpdateUser = $conn->prepare($sqlUpdateUser);
                if ($stmtUpdateUser) {
                    $stmtUpdateUser->bind_param("si", $profilePicture, $userId);
                    $stmtUpdateUser->execute();
                    $stmtUpdateUser->close();
                }

                //echo json_encode(["status" => "success", "newProfilePicture" => $uploadDir . $profilePicture]);
                
            } else {
                echo json_encode(["status" => "error", "message" => "Resim veritabanına kaydedilirken bir hata oluştu."]);
            }
            $stmtInsertImage->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Resim veritabanına kaydedilirken bir hata oluştu."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Resim yüklenirken bir hata oluştu."]);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Messenger</title>
    <style>
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
        .chat-icons img {
            width: 40px;
            height: 40px;
            display: block;
            margin-bottom: 10px;
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
            position: absolute;
            bottom: 20px;
            right: 20px;
            cursor: pointer;
        }
        .profile-section {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .profile-section img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .profile-section h2 {
            margin: 0;
        }
        .file-upload-form {
            display: flex;
            align-items: center;
        }
        .file-upload-form input[type="file"] {
            display: none;
        }
        .file-upload-form button {
            margin-left: 10px;
            padding: 8px 16px;
            background-color: #007bff;
            color: #fff;
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

    
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="chat-icons">
                <a href="index.php"><img src="images/sms.png" alt="SMS icon"></a>
                <a href="earth.php"><img src="images/earth.png" alt="Earth icon"></a>
                <a href="friends.php"><img src="images/friends.png" alt="Friends icon"></a>
                <a href="notification.php"><img src="images/notification.png" alt="Notification icon"></a>
                <a id="hacker-button" href="#"><img src="images/hacker.png" alt="Hacker icon"></a>
                <a href="profile.php"><img src="images/profile.png" alt="Profile icon"></a>
                <a href="settings.php"><img src="images/settings.png" alt="Settings icon"></a>
            </div>
            <button class="logout-btn" onclick="location.href='?logout=true'">Çıkış Yap</button>
        </div>
        <div class="main">
            <div class="profile-section">
                <img src="<?php echo $_SESSION['profile_picture']; ?>" alt="Profil Resmi">
                <h2><?php echo $_SESSION['username']; ?></h2>
            </div>
            <form class="file-upload-form" method="POST" enctype="multipart/form-data">
                <input type="file" id="fileInput" name="file" accept="image/*">
                <button type="button" onclick="document.getElementById('fileInput').click();">Profil Resmini Güncelle</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('fileInput').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>
