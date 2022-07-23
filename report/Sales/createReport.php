<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

function getNextCol ($currentCol, $shift)
{
    $targetRowArray = array();
    $count = 0;
    $currentColArray = array_reverse (str_split ($currentCol));
    foreach ($currentColArray as $key => $char)
    {
        $code = ord ($char) - 64;
        $count = $count + $code * pow (26, $key);
    }
    
    $count += $shift;
    
    while ($count != 0)
    {
        $targetColArray[] = chr (($count % 26 ? $count % 26 : 26) + 64);
        $count = $count % 26 ? intdiv ($count, 26) : intdiv ($count, 26) - 1;
    }
    return implode (array_reverse($targetColArray));
}

function getData ($data)
{
    $return = [];
    foreach ($data as $dataItem)
    {
        $projectKey = '';
        switch ($dataItem['project'])
        {
            case MS_PROJECT_2HRS_ID:
                $projectKey = 'yandex';
                break;
            case MS_PROJECT_YANDEX_DBS_ID:
                $projectKey = 'yandex';
                break;
            case MS_PROJECT_YANDEX_ULLO_ID:
                $projectKey = 'yandex';
                break;
            case MS_PROJECT_MARKET_ID:
                $projectKey = 'yandex';
                break;
            case MS_PROJECT_XWAY_ID:
                $projectKey = 'xway';
                break;
            case MS_PROJECT_GOODS_ID:
                $projectKey = 'sber';
                break;
            case MS_PROJECT_OZON_ID:
                $projectKey = 'ozon';
                break;
            case MS_PROJECT_OZON_DBS_ID:
                $projectKey = 'ozon';
                break;
            case MS_PROJECT_OZON_ULLO_DBS_ID:
                $projectKey = 'ozon';
                break;
            case MS_PROJECT_WB_ID:
                $projectKey = 'wb';
                break;
            case MS_PROJECT_4CLEANING_ID:
                $projectKey = 'site';
                break;
            case MS_PROJECT_10KIDS_ID:
                $projectKey = 'site';
                break;
            case MS_PROJECT_MSKOREA_ID:
                $projectKey = 'site';
                break;
            case '':
                $projectKey = 'no';
                break;
        }
        
        if ($dataItem['product_code'] == '00001') {
            continue;
        }
        
        $productKey = array_search($dataItem['product_code'], array_column($return, 'productCode'));
        if ($productKey === false)
        {
            $return[] = array(
                'productCode' => $dataItem['product_code'],
                'product' => $dataItem['product'],
                $projectKey . 'S' => (int)$dataItem['sum'],
                $projectKey . 'Q' => (int)$dataItem['quantity']
            );
        }
        else
        {
            $return[$productKey][$projectKey . 'S'] = isset($return[$productKey][$projectKey . 'S']) ? $return[$productKey][$projectKey . 'S'] + (int)$dataItem['sum'] : (int)$dataItem['sum'];
            $return[$productKey][$projectKey . 'Q'] = isset($return[$productKey][$projectKey . 'Q']) ? $return[$productKey][$projectKey . 'Q'] + (int)$dataItem['quantity'] : (int)$dataItem['quantity'];
        }
    }
    return $return;
}

$pattern = '/{([a-zA-Z]+)}/';

$table = 'report_sales';

$log = new \Classes\Common\Log('reports - Sales - createReport.log');

use Classes\Common\Db;


// select data
$dbClass = new Classes\Common\Db();
$sql = 'select * from ' . $table . ' order by product, project';
$data = $dbClass->execQueryArray($sql);
//echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$log->write(__LINE__ . ' data - ' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$reportData = getData($data);
$log->write(__LINE__ . ' reportData - ' . json_encode($reportData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

$reader = IOFactory::createReader("Xlsx");
$spreadsheet = $reader->load($_SERVER['DOCUMENT_ROOT'] . '/report/Sales/template/template.xlsx');
$spreadsheet->getActiveSheet()->setTitle($spreadsheet->getActiveSheet()->getTitle() . ' 2021');

foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row)
{
    $hasPattern = false;
    foreach ($row->getCellIterator() as $cell)
    {
        $matches = preg_match($pattern, $spreadsheet->getActiveSheet()->getCell($cell->getColumn() . $row->getRowIndex())->getValue());
        if ($matches)
        {
            $hasPattern = true;
            break;
        }
    }
    
    if ($hasPattern)
    {
        $dataValues = array();
        $rowsCount = 0;
        $dataValues[$row->getRowIndex()] = array();
        foreach ($reportData as $record)
        {
            $rowsCount++;
            $dataValues[$row->getRowIndex()][$rowsCount] = array();
            foreach ($row->getCellIterator() as $cell)
            {
                $rowNumber = $row->getRowIndex();
                $colNumber = $cell->getColumn();
                //$log->write(__LINE__ . ' row - ' . json_encode($row->getRowIndex(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                //$log->write(__LINE__ . ' column - ' . json_encode($cell->getColumn(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                $value = $spreadsheet->getActiveSheet()->getCell($colNumber . $rowNumber)->getValue();
                $log->write(__LINE__ . ' value - ' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                //$spreadsheet->getActiveSheet()->duplicateStyle(
                //    $spreadsheet->getActiveSheet()->getStyle($cell->getColumn() . ($row->getRowIndex() + 1)),
                //    $cell->getColumn() . $row->getRowIndex()
                //);
                //$log->write(__LINE__ . ' spreadsheet1 - ' . json_encode($spreadsheet, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                //$log->write(__LINE__ . ' spreadsheet2 - ' . json_encode($spreadsheet, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                //$text2 = $spreadsheet->getActiveSheet()->getCell($colNumber . ($rowNumber + 1))->getValue();
                //$log->write(__LINE__ . ' text2 - ' . json_encode($text2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                //$log->write(__LINE__ . ' getValue - ' . json_encode($spreadsheet->getActiveSheet()->getCell($cell->getColumn() . ($row->getRowIndex() + 1))->getValue(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                $cellValue = preg_replace_callback(
                    $pattern,
                    function ($m)
                    use ($record)
                    {
                        return isset ($record[$m[1]]) ? $record[$m[1]] : '';
                    },
                    $value
                );
                $log->write(__LINE__ . ' cellValue - ' . json_encode($cellValue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                $dataValues[$rowNumber][$rowsCount][$colNumber] = $cellValue;
                //$spreadsheet->getActiveSheet()->getCell($colNumber . $rowNumber)->setValue($cellValue);
            }            
        }
        
        $log->write(__LINE__ . ' dataValues - ' . json_encode($dataValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        //count($dataValues)
    }
}

$shift = 0;
foreach($dataValues as $rows=>$rowData) {
    $spreadsheet->getActiveSheet()->insertNewRowBefore((int)$rows + 1 + $shift, count($rowData));
    foreach($rowData as $rowNumber=>$cells)
    {
        foreach ($cells as $colNumber=>$cellData)
        {
            $spreadsheet->getActiveSheet()->getCell($colNumber . ((int)$rows + (int)$rowNumber + $shift))->setValue($cellData);
        }
    }
    $spreadsheet->getActiveSheet()->removeRow($rows + $shift);
    $shift += count($rowData) - 1;
}

$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
$months = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];
$date = DateTime::createFromFormat('Y-m-d', $_GET['date'])->setTimezone(new DateTimeZone('Europe/Moscow'));
$filename = 'Продажи за ' . $months[$date->format('m') - 1] . ' ' . $date->format('Y');
$writer->save($_SERVER['DOCUMENT_ROOT'] . '/report/reports/' . $filename . '.xlsx');

//echo json_encode($spreadsheet->getSheetNames(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

//$dbClass = new \Classes\Common\Db();
?>

