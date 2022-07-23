<?php
	session_start();
	session_unset();
	session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Разлогин</title>
</head>
<body>
<div id="page">
    <!-- [banner] -->
    <header id="banner">
        <hgroup>
            <h1>Вы разлогинены</h1>
        </hgroup>        
    </header>
	<button onclick = "window.location.href='/index.php'">Логин</button>
</div>
<!-- [/page] -->
</body>
</html>