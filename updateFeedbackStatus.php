<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/feedbacks.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
	$logger = new Log ('feedbacks.log');
	$logger -> write ($_GET['feedback']);
	//$logger -> write (json_encode ($prices));
	Feedbacks::updateFeedbackStatus ($_GET['feedback']);
	return;
?>

