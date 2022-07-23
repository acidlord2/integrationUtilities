<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');

//$dbClass = new \classes\Common\Db();
//$a = $dbClass->truncate('report_sales');
require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
$pattern = '/{([a-zA-Z]+)}/';

$reader = IOFactory::createReader("Xlsx");
$spreadsheet = $reader->load('template.xlsx');

foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row)
{
    foreach ($row->getCellIterator() as $cell)
    {
        $matches = preg_match($pattern, $spreadsheet->getActiveSheet()->getCell($cell->getColumn() . $row->getRowIndex())->getValue());
        if ($matches)
        {
            $spreadsheet->getActiveSheet()->insertNewRowBefore($row->getRowIndex(), 1);
            $spreadsheet->getActiveSheet()->duplicateStyle(
                $spreadsheet->getActiveSheet()->getStyle($cell->getColumn() . ($row->getRowIndex() + 1)),
                $cell->getColumn() . $row->getRowIndex()
            );
        }
        echo __LINE__ . ' ' . $spreadsheet->getActiveSheet()->getCell($cell->getColumn() . $row->getRowIndex())->getValue();
        echo __LINE__ . ' ' . $row->getRowIndex();
        echo __LINE__ . ' ' . $cell->getColumn();
    }
    if ($row->getRowIndex() > 20)
        break;
}
$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$writer->save("test8.xlsx");

//echo json_encode($matches);
?>