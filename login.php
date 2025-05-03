<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kullanıcı bilgilerini veritabanından alma
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_picture'] = 'images/profile_pictures/' . $user['profile_picture'];

            // Oturumu veritabanına kaydetme (isteğe bağlı)
            $session_id = session_id();
            $sql = "INSERT INTO user_sessions (user_id, session_id, login_time) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('is', $user['id'], $session_id);
            $stmt->execute();

            header("Location: index.php");
            exit();
        } else {
            $error = "Hatalı kullanıcı adı veya şifre.";
        }
    } else {
        $error = "Hatalı kullanıcı adı veya şifre.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 90vh;
            background-color: #f2f2f2;
        }
        .login-box {
            width: 160px;
            padding: 100px;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .login-box h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .login-box label {
            font-weight: bold;
        }
        .textbox {
            margin-bottom: 20px;
        }
        .textbox label {
            display: block;
            margin-bottom: 5px;
        }
        .textbox input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-box button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }
        .login-box button:hover {
            background-color: #0056b3;
        }
        .login-box p {
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="login-box">

            <h2>Giriş Yap</h2>
            <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
            <form method="POST">
                <div class="textbox">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="textbox">
                    <label for="password">Parola</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Giriş Yap</button>
            </form>
            <p>Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
            <p>Şifrenizi mi unuttunuz? <a href="forgot_password.php">Şifremi Sıfırla</a></p>

        </div>
    </div>
</body>
</html>
