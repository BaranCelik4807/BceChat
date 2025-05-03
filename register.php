<?php
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Kayıt başarılı. Giriş sayfasına yönlendiriliyorsunuz...</p>";
        header("refresh:3;url=login.php");
        exit();
    } else {
        $error = "Kayıt başarısız: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
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
            <h2>Kayıt Ol</h2>
            <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
            <form method="POST">
                <div class="textbox">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="textbox">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="textbox">
                    <label for="password">Parola</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Kayıt Ol</button>
            </form>
            <p>Hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
        </div>
    </div>
</body>
</html>
