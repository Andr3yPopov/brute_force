<?php
$servername = "localhost";
$username = "admin";
$password = "password";
$dbname = "site_db";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

function log_failed_attempt($conn, $ip) {
    $sql_check = "SELECT * FROM login_attempts WHERE ip = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $ip);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $attempts = $row['attempts'] + 1;
        $last_attempt = time();

        $sql_update = "UPDATE login_attempts SET attempts = ?, last_attempt = ? WHERE ip = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iis", $attempts, $last_attempt, $ip);
        $stmt_update->execute();
    } else {
        $attempts = 1;
        $last_attempt = time();

        $sql_insert = "INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sis", $ip, $attempts, $last_attempt);
        $stmt_insert->execute();
    }
}

function is_ip_blocked($conn, $ip) {
    $sql = "SELECT * FROM login_attempts WHERE ip = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $attempts = $row['attempts'];
        $last_attempt = $row['last_attempt'];

        if ($attempts > 10 && (time() - $last_attempt) < 900) {
            return true;
        }
    }
    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_ip = $_SERVER['REMOTE_ADDR'];

    if (is_ip_blocked($conn, $user_ip)) {
        die("Ваш IP заблокирован из-за слишком большого количества неудачных попыток входа. Попробуйте позже.");
    }

    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($password == $row["password"]) {
            session_start();
            $_SESSION["username"] = $row["username"];
            $_SESSION["role"] = $row["role"];
            header("Location: 1.php");
            exit;
        } else {
            log_failed_attempt($conn, $user_ip);
            $error_message = "Неверный логин или пароль.";
        }
    } else {
        log_failed_attempt($conn, $user_ip);
        $error_message = "Неверный логин или пароль.";
    }

    $stmt->close();
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
input[type=text], input[type=password] {
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
</style>
</head>
<body>
<div class="login-container">
<h2>Авторизация</h2>
<?php
if (isset($error_message)) {
echo '<div class="error-message">' . $error_message . '</div>';
}
?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<label for="username">Имя пользователя:</label>
<input type="text" id="username" name="username" required>
<label for="password">Пароль:</label>
<input type="password" id="password" name="password" required>
<button type="submit">Войти</button>
</form>
</div>
</body>
</html>