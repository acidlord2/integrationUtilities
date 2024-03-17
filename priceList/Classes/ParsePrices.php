<?php
/**
 *
 * @class ParsePrices
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ParsePrices
{
	//use PhpOffice\PhpSpreadsheet\Spreadsheet;
	//use PhpOffice\PhpSpreadsheet\IOFactory;

	private $fileInfo;
	private $data;
	private $log;
	
	private $priceTypes = ['4kl.', 'Ромашка', '10kids', 'Ulloza', 'Сбер/10kids', 'Сбер/MsKOREA', 'Юлло', 'Каори', 'Альянс'];
	public function getFileInfo ()
	{
		return $this->fileInfo;
	}

	public function getData ()
	{
		return $this->data;
	}

	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		$this->log = new Log ('priceList - Classes - ParsePrices.log');
	}

	public function parseFile($filename)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');	

		$reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
		$spreadsheet = $reader->load($filename);
		$workbook = $spreadsheet->getSheet($spreadsheet->getSheetCount() - 1);
		$this->log->write(__LINE__ . ' parseFile.workbooks - ' . json_encode($workbook, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		
		$this->fileInfo = $this->parseFileInfo ($workbook);
		$this->log->write(__LINE__ . ' parseFile.fileInfo - ' . json_encode($this->fileInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$this->data = $this->parseData($workbook);
	}
		
	public function parseFileInfo ($workbook)
	{
		$return = array();
		foreach ($workbook->getRowIterator() as $row)
		{
			foreach ($row->getCellIterator() as $cell)
			{
				//$this->logger->write ($cell->getCalculatedValue());
			    if (in_array($cell->getCalculatedValue(), $this->priceTypes))
					//$return['paymentDate'] = $workbook->getCell(self::getNextCol($cell->getColumn(), 1) . $row->getRowIndex())->getCalculatedValue();
			        $return[$cell->getCalculatedValue()] = $cell->getColumn();
			}
			if (count ($return))
				break;
		}
		return $return;
	}
	
	public function parseData ($workbook)
	{
		$return = array();
		$start = 'head';
		foreach ($workbook->getRowIterator() as $row)
		{
			//$this->logger->write ($row->getRowIndex());
			//$this->logger->write ($start);
			// поиск строки начисления
			if ($start == 'head')
			{
				foreach ($row->getCellIterator() as $cell)
				{
					if (strpos($cell->getCalculatedValue(), 'Бренд') !== false)
						break;
				}
				$start = 'data';
			}
			// обработка данных насчисления
			else if ($start = 'data')
			{
			    if ($workbook->getCell('F' . $row->getRowIndex())->getCalculatedValue() == null)
			        break;
			        
				$return[] = array (
					'brand' => $workbook->getCell('A' . ($row->getRowIndex()))->getCalculatedValue(),
				    'name' => $workbook->getCell('B' . ($row->getRowIndex()))->getCalculatedValue(),
				    'size' => $workbook->getCell('C' . ($row->getRowIndex()))->getCalculatedValue(),
				    'addon' => $workbook->getCell('D' . ($row->getRowIndex()))->getCalculatedValue(),
				    'barcode' => (string)trim($workbook->getCell('F' . ($row->getRowIndex()))->getCalculatedValue())
				);
				foreach($this->fileInfo as $price_type => $price_value)
				{
				    $return[array_key_last($return)][$price_type] = round($workbook->getCell($price_value . ($row->getRowIndex()))->getCalculatedValue());
				}
			}
		}
		return $return;
	}
}
?>
