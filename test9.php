<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');

//$dbClass = new \classes\Common\Db();
//$a = $dbClass->truncate('report_sales');
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
$spreadsheet = $reader->load($_SERVER['DOCUMENT_ROOT'] . '/report/Sales/template/template.xlsx');

$months = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
setlocale(LC_TIME, 'ru_RU', 'russian');
$date = DateTime::createFromFormat('Y-m-d', $_GET['date'])->setTimezone(new DateTimeZone('Europe/Moscow'));
$filename = 'Продажи за ' . $months[$date->format('m') - 1] . ' ' . $date->format('Y');

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save($_SERVER['DOCUMENT_ROOT'] . '/report/reports/' . $filename . '.xlsx');
//echo json_encode(array_slice($files, 1), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>