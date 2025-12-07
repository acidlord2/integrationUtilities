<?php
// Ensure no output before session_start or header
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/users.php');
$login = null;
$password = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!empty($_POST["username"]) && !empty($_POST["password"])) {
        $login = $_POST["username"];
        $password = hash ('sha512', $_POST["password"]);

        if(Users::autentificateUser($login, $password)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["authenticated"] = 'true';
            $_SESSION["user"] = $login;
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $base_url = $protocol . $host . '/';
            
            header('Location: ' . $base_url . (isset($_GET['url']) ? $_GET['url'] : 'index'));
            exit();
        }
        else {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $base_url = $protocol . $host . '/';
            
            header('Location: ' . $base_url . 'login/login.php?mess=Wrong+login+or+password');
            exit();
        }
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . $host . '/';
        
        header('Location: ' . $base_url . 'login/login.php?mess=Login+and+password+can+not+be+null');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Логин</title>
    <link rel="stylesheet" type="text/css" href="/css/styles.css?v=4" />
</head>
<body>
<div class="login-card">
    <h1 style="margin-bottom: 18px; color: #F7971D; font-size: 2rem; letter-spacing: 1px;">Логин</h1>
    <?php if (isset($_GET['mess'])) { ?>
        <div class="loginBlock">
            <span><?php echo $_GET['mess']; ?></span>
        </div>
    <?php } ?>
    <form id="login" method="post">
        <label for="username">Логин:</label>
        <input id="username" name="username" type="text" required>
        <label for="password">Пароль:</label>
        <input id="password" name="password" type="password" required>
        <input type="submit" value="Login">
    </form>
</div>
</body>
</html>
