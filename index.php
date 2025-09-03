<?php
	require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/auth.php');
	
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	//require_once('classes/log.php')
	//var $log = new Log("log.txt");
//	log.write('aaa');
	
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/users.php');

	if ($userRoles = Users::getUserRoles($_SESSION["user"])) {
?>

<html>
   <head>
	   <title>Помощь по складу</title>
	   <meta http-equiv="content-type" content="text/html; charset=utf-8">
	   <link rel = "stylesheet" type = "text/css"  href = "/css/styles.css?v=4" />
   </head>
   <body>
	   <?php require_once($_SERVER['DOCUMENT_ROOT'] . '/header.php'); ?>
	   <div class="main-menu-card">
		   <div id="header">
			   <div class="main-title">
				   Главное меню
			   </div>
		   </div>
		   <div class="button-wraper">
			   <?php
			   // Collect all user role IDs
			   $userRoleIds = array();
			   while ($userRole = mysqli_fetch_assoc($userRoles)) {
				   $userRoleIds[] = $userRole['role_id'];
			   }

			   // Output buttons in strict order
			   if (in_array(3, $userRoleIds)) {
			   ?>
				   <button class="main-menu-button" onclick="window.location.href='integration/integration.php'">
					   Интеграции
				   </button>
			   <?php }
			   if (in_array(4, $userRoleIds)) {
			   ?>
				   <button class="main-menu-button" onclick="window.location.href='priceList/priceList'">
					   Изменение цен
				   </button>
			   <?php }
			   if (in_array(5, $userRoleIds)) {
			   ?>
				   <button class="main-menu-button" onclick="window.location.href='returns/returns.php'">
					   Возвраты
				   </button>
			   <?php }
			   if (in_array(7, $userRoleIds)) {
			   ?>
				   <button class="main-menu-button" onclick="window.location.href='print/printList'">
					   Печать заказов
				   </button>
			   <?php }
			   if (in_array(1, $userRoleIds)) {
			   ?>
				   <button class="main-menu-button" onclick="window.location.href='shipping2/shiplist'">
					   Отгрузка
				   </button>
			   <?php }
			   if (in_array(1, $userRoleIds) || in_array(3, $userRoleIds)) {
			   ?>
				   <button class="main-menu-button" onclick="window.location.href='finances/finance'">
					   Разноска финансов
				   </button>
			   <?php }
			   if (in_array(3, $userRoleIds)) {
			   ?>
				   <button class="main-menu-button" onclick="window.location.href='compare/compare.php'">
					   Сравнение данных
				   </button>
			   <?php }
			   ?>
		   </div>
	   </div>
   </body>
</html>

<?php
	}
?>