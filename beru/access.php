<html>
	<script>console.log (window.location);</script>
	<?php
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php');
		//echo json_encode ($_SERVER);
		if (isset($_GET['access_token']))
		{
			Settings::setSettingsValues(array ('beru_token' => $_GET['access_token']));
			echo $_GET['access_token'] . '<br>';
		}
		if (isset($_GET['expires_in']))
		{
			$result_date = strtotime(sprintf('+%d seconds', $_GET['expires_in']));
			Settings::setSettingsValues(array ('beru_expired' => date('Y-m-d H:i:s', $result_date)));
			echo $_GET['expires_in'] . '<br>';
		}
		echo "Токен успешно обновлен";
	?>

</html>
