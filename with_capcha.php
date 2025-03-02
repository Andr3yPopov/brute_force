<?php
session_start();

$servername = "localhost";
$username = "admin";
$password = "password";
$dbname = "site_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

function generateCaptcha() {
    $width = 200;
    $height = 100;
    $image = imagecreatetruecolor($width, $height);

    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $red = imagecolorallocate($image, 255, 0, 0);

    imagefilledrectangle($image, 0, 0, $width, $height, $white);

    $lineCount = rand(2, 5);
    $_SESSION['captcha_lines'] = $lineCount;

    for ($i = 0; $i < $lineCount; $i++) {
        $x1 = rand(0, $width);
        $y1 = rand(0, $height);
        $x2 = rand(0, $width);
        $y2 = rand(0, $height);
        imageline($image, $x1, $y1, $x2, $y2, $black);
    }

    for ($i = 0; $i < 100; $i++) {
        $x = rand(0, $width);
        $y = rand(0, $height);
        imagesetpixel($image, $x, $y, $red);
    }

    for ($i = 0; $i < 500; $i++) {
        $x = rand(0, $width);
        $y = rand(0, $height);
        imagesetpixel($image, $x, $y, $black);
    }

    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
}

if (isset($_GET['captcha'])) {
    generateCaptcha();
    exit;
}

$error_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $captchaInput = intval($_POST["captcha"]);

    if (!isset($_SESSION['captcha_lines']) || $captchaInput !== $_SESSION['captcha_lines']) {
        $error_message = "Неверное количество линий.";
    } else {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($password === $row["password"]) {
                $_SESSION["username"] = $row["username"];
                $_SESSION["role"] = $row["role"];
                header("Location: 1.php");
                exit;
            } else {
                $error_message = "Неверный логин или пароль.";
            }
        } else {
            $error_message = "Неверный логин или пароль.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Авторизация</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f1f1f1;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}
.login-container {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    width: 300px;
}
h2 {
    text-align: center;
    margin-bottom: 20px;
}
input[type=text], input[type=password], input[type=number] {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}
button {
    background-color: #4CAF50;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    width: 100%;
}
button:hover {
    background-color: #45a049;
}
.error-message {
    color: red;
    text-align: center;
}
.captcha-image {
    display: block;
    margin: 10px auto;
    width: 200px;
    height: 100px;
}
</style>
</head>
<body>
<div class="login-container">
<h2>Авторизация</h2>
<?php
if (!empty($error_message)) {
    echo '<div class="error-message">' . $error_message . '</div>';
}
?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
    <label for="username">Имя пользователя:</label>
    <input type="text" id="username" name="username" required>
    
    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required>
    
    <label for="captcha">Количество линий на изображении:</label>
    <img src="?captcha=true" alt="CAPTCHA" class="captcha-image">
    <input type="number" id="captcha" name="captcha" required>
    
    <button type="submit">Войти</button>
</form>
</div>
</body>
</html>