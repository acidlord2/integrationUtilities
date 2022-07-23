<div class = "header">
	<div class = "back">
		<button onclick = "window.location.href='/index.php'">Главное меню</button>
	</div>
	<div class = "logout">
		<span><?php echo $_SESSION["user"]; ?>
		<button onclick = "window.location.href='/login/logout.php'">Выйти</button>
	</div>
</div>
<script>
	if (window.location.href.indexOf("index") > 0)
		document.getElementsByClassName("back")[0].style.display = "none";
</script>