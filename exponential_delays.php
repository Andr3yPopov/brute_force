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

$user_ip = $_SERVER['REMOTE_ADDR'];

$sql_check_block = "SELECT * FROM login_attempts WHERE ip_address = ?";
$stmt_check_block = $conn->prepare($sql_check_block);
$stmt_check_block->bind_param("s", $user_ip);
$stmt_check_block->execute();
$result_check_block = $stmt_check_block->get_result();
$row_block = $result_check_block->fetch_assoc();

if ($row_block && strtotime($row_block['blocked_until']) > time()) {
    $error_message = "Неверный логин или пароль.";
} else {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
                // Успешная авторизация
                session_regenerate_id(true);
                $_SESSION["username"] = $row["username"];
                $_SESSION["role"] = $row["role"];

                $sql_reset_attempts = "DELETE FROM login_attempts WHERE ip_address = ?";
                $stmt_reset_attempts = $conn->prepare($sql_reset_attempts);
                $stmt_reset_attempts->bind_param("s", $user_ip);
                $stmt_reset_attempts->execute();

                header("Location: 1.php");
                exit;
            } else {
                // Неудачная попытка входа
                $error_message = "Неверный логин или пароль.";

                if ($row_block) {
                    $failed_attempts = $row_block['failed_attempts'] + 1;
                    $blocked_until = date('Y-m-d H:i:s', time() + pow(2, $failed_attempts) * 5);
                    $sql_update_attempts = "UPDATE login_attempts SET failed_attempts = ?, blocked_until = ? WHERE ip_address = ?";
                    $stmt_update_attempts = $conn->prepare($sql_update_attempts);
                    $stmt_update_attempts->bind_param("iss", $failed_attempts, $blocked_until, $user_ip);
                    $stmt_update_attempts->execute();
                } else {
                    $failed_attempts = 1;
                    $blocked_until = date('Y-m-d H:i:s', time() + 5); // Первая блокировка на 5 секунд
                    $sql_insert_attempts = "INSERT INTO login_attempts (ip_address, failed_attempts, blocked_until) VALUES (?, ?, ?)";
                    $stmt_insert_attempts = $conn->prepare($sql_insert_attempts);
                    $stmt_insert_attempts->bind_param("sis", $user_ip, $failed_attempts, $blocked_until);
                    $stmt_insert_attempts->execute();
                }
            }
        } else {
            $error_message = "Неверный логин или пароль.";

            if ($row_block) {
                $failed_attempts = $row_block['failed_attempts'] + 1;
                $blocked_until = date('Y-m-d H:i:s', time() + pow(2, $failed_attempts) * 5);
                $sql_update_attempts = "UPDATE login_attempts SET failed_attempts = ?, blocked_until = ? WHERE ip_address = ?";
                $stmt_update_attempts = $conn->prepare($sql_update_attempts);
                $stmt_update_attempts->bind_param("iss", $failed_attempts, $blocked_until, $user_ip);
                $stmt_update_attempts->execute();
            } else {
                $failed_attempts = 1;
                $blocked_until = date('Y-m-d H:i:s', time() + 5);
                $sql_insert_attempts = "INSERT INTO login_attempts (ip_address, failed_attempts, blocked_until) VALUES (?, ?, ?)";
                $stmt_insert_attempts = $conn->prepare($sql_insert_attempts);
                $stmt_insert_attempts->bind_param("sis", $user_ip, $failed_attempts, $blocked_until);
                $stmt_insert_attempts->execute();
            }
        }

        $stmt->close();
    }
}

$stmt_check_block->close();
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
    echo '<div class="error-message">' . htmlspecialchars($error_message) . '</div>';
}
?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <label for="username">Имя пользователя:</label>
    <input type="text" id="username" name="username" required>
    <label for="password">Пароль:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Войти</button>
</form>
</div>
</body>
</html>
