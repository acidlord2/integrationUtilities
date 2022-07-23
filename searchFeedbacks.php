<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/feedbacks.php');
	//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
?>
<tr id = "teble_header">
	<th>Номер заказа</th>
	<th>id oтзыва</th>
	<th>Статус</th>
	<th>Заказчик</th>
	<th>Магазин</th>
	<th>Оценка</th>
	<th>Рекомендованный</th>
	<th>Верифицированный</th>
	<th>Удален?</th>
	<th>Сменить статус</th>
</tr>
<?php
	foreach (Feedbacks::searchFeedbacks($_GET['orders']) as $feedback)
	{ ?>
		<tr>
			<td> <?php echo $feedback['shopOrderId']; ?></td>
			<td> <?php echo isset ($feedback['error']) ? $feedback['error'] : $feedback['feedback_id']; ?></td>
			<td> <?php echo isset ($feedback['error']) ? $feedback['error'] : ($feedback['status'] ? 'Использован' : 'Не использован'); ?></td>
			<td> <?php echo (isset($feedback['name']) ? $feedback['name'] : ''); ?></td>
			<td> <?php echo (isset($feedback['shop']) ? $feedback['shop'] : ''); ?></td>
			<td> <?php echo (isset($feedback['grade']) ? $feedback['grade'] : ''); ?></td>
			<td> <?php echo (isset($feedback['recommend']) ? ($feedback['recommend'] ? utf8_encode("&#10004;") : utf8_encode("&#10008;")) : ''); ?></td>
			<td> <?php echo (isset($feedback['verified']) ? ($feedback['verified'] ? utf8_encode("&#10004;") : utf8_encode("&#10008;")) : ''); ?></td>
			<td> <?php echo (isset($feedback['deleted']) ? ($feedback['deleted'] ? utf8_encode("&#10004;") : utf8_encode("&#10008;")) : ''); ?></td>
			<td>
				<?php if (!isset ($feedback['error'])) { ?>
					<button type="button" id = "<?php echo $feedback['feedback_id']; ?>" onclick="updateFeedbackStatus('<?php echo $feedback['feedback_id']; ?>')">Сменить статус</button>
				<?php } ?>
			</td>
		</tr>
<?php	}
?>

