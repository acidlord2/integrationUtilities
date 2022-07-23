<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/feedbacks.php');
	$feedbacks = Feedbacks::getYandexFeedbacks('21533937', date('d-m-Y', strtotime ('-2 months')));
	echo json_encode ($feedbacks, JSON_UNESCAPED_SLASHES);
	$feedbacks = Feedbacks::updateFeedbacks($feedbacks);
	echo json_encode ($feedbacks, JSON_UNESCAPED_SLASHES);
	return;
?>

