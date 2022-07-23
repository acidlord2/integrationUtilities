<?php 
$files = scandir($_SERVER['DOCUMENT_ROOT'] . '/report/reports');
?>

<div align="center">
	<div class = "title">
		Готовые отчеты
	</div>
	<ul>
		<?php if (count($files) > 2) {foreach (array_slice($files, 2) as $file) { ?>
				<li><a href="reports/<?php echo $file; ?>" download><?php echo $file; ?></a></li>
		<?php }} else { ?>
			<li>Готовые отчеты отсутствуют</li>
		<?php } ?>
	</ul>
</div>

