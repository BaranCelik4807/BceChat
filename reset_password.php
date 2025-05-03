<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Değiştir</title>
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
            height: 100vh;
            background-color: #f2f2f2;
        }
        .login-box {
            width: 300px;
            padding: 40px;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .login-box h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .textbox {
            margin-bottom: 20px;
        }
        .textbox label {
            display: block;
            margin-bottom: 5px;
        }
        .textbox input {
            width: calc(100% - 22px); /* Adjusting for padding and border */
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
        .error-message {
            color: red;
            margin-top: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <h2>Şifreyi Sıfırla</h2>
            <div id="password_error_message" class="error-message"></div>
            <form method="POST" id="password_reset_form" action="change_password.php">
                <div class="textbox">
                    <label for="new_password">Yeni parola</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="textbox">
                    <label for="confirm_password">Parolayı doğrula</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit">Şifreyi Değiştir</button>
            </form>
        </div>
    </div>
</body>
</html>
