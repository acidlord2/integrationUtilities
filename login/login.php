<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/users.php');
	$login = null;
	$password = null;

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		if(!empty($_POST["username"]) && !empty($_POST["password"])) {
			$login = $_POST["username"];
			$password = hash ('sha512', $_POST["password"]);

			if(Users::autentificateUser($login, $password)) {
				session_start();
				$_SESSION["authenticated"] = 'true';
				$_SESSION["user"] = $login;
				header('Location: ' . HTTP_SERVER . (isset($_GET['url']) ? $_GET['url'] : 'index'));
			}
			else {
				header('Location: ' . HTTP_SERVER . 'login/login.php?mess="Wrong login or password"');
			}
			
		} else {
			header('Location: ' . HTTP_SERVER . 'login/login.php?mess="Login and password can not be null"');
		}
	} else {
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Логин</title>
</head>
<body>
<div id="page">
    <!-- [banner] -->
    <header id="banner">
        <hgroup>
            <h1>Логин</h1>
        </hgroup>        
    </header>
    <!-- [content] -->
    <section id="content">
		<?php if (isset($_GET['mess'])) { ?>
			<div class = "loginBlock">
				<span><?php echo $_GET['mess']; ?></span>
			</div>
		<?php } ?>
        <form id="login" method="post">
            <label for="username">Логин:</label>
            <input id="username" name="username" type="text" required>
            <label for="password">Пароль:</label>
            <input id="password" name="password" type="password" required>                    
            <br />
            <input type="submit" value="Login">
        </form>
    </section>
    <!-- [/content] -->
</div>
<!-- [/page] -->
</body>
</html>
<?php } ?>
