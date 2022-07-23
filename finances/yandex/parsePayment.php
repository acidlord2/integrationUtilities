<?php
/**
 *
 * @class ParsePayment
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ParsePayment
{
	//use PhpOffice\PhpSpreadsheet\Spreadsheet;
	//use PhpOffice\PhpSpreadsheet\IOFactory;

	private $fileInfo;
	private $chargeData;
	private $stornoData;
	private $log;
	
	public function getFileInfo ()
	{
		return $this->fileInfo;
	}

	public function getChargeData ()
	{
		return $this->chargeData;
	}

	public function getStornoData ()
	{
		return $this->stornoData;
	}

	public function getTotals ()
	{
		$ordersCharged = array_unique(array_column ($this->chargeData, 'orderNumber'));
		$ordersStornoed = array_unique(array_column ($this->stornoData, 'orderNumber'));
		$return = array (
			'totalOrdersCharged' => count ($ordersCharged),
			'totalSumCharged' => array_sum(array_column ($this->chargeData, 'amount')),
			'totalOrdersStornoed' => count ($ordersStornoed),
			'totalSumStornoed' => array_sum(array_column ($this->stornoData, 'amount'))
		);
		return $return;
	}
	
	public function __construct()
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');

		$this->log = new Log ('finances - yandex - parsePayment.log');
	}

	public function parseFile($filename)
	{
		require_once($_SERVER['DOCUMENT_ROOT'] . '/PhpSpreadsheet/vendor/autoload.php');	

		$reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
		$spreadsheet = $reader->load($filename);
		$workbooks = $spreadsheet->getAllSheets();
		$this->log->write(__LINE__ . ' parseFile.workbooks - ' . json_encode($workbooks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		
		$this->fileInfo = $this->parseFileInfo ($workbooks[0]);
		$this->log->write(__LINE__ . ' parseFile.fileInfo - ' . json_encode($this->fileInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$this->chargeData = $this->parseChargeData($workbooks[1]);
		$this->stornoData = $this->parseStornoData($workbooks[1]);
	}
	
	public static function getNextCol ($currentRow, $shift)
	{
		$targetRowArray = array();
		$count = 0;
		$currentRowArray = array_reverse (str_split ($currentRow));
		foreach ($currentRowArray as $key => $char)
		{
			$code = ord ($char) - 64;
			$count = $count + $code * pow (26, $key);
		}
		
		$count += $shift;
		
		while ($count != 0)
		{
			$targetRowArray[] = chr (($count % 26 ? $count % 26 : 26) + 64);
			$count = $count % 26 ? intdiv ($count, 26) : intdiv ($count, 26) - 1;
		}
		return implode (array_reverse($targetRowArray));		
	}
	
	public function parseFileInfo ($workbook)
	{
		$return = array();
		foreach ($workbook->getRowIterator() as $row)
		{
			foreach ($row->getCellIterator() as $cell)
			{
				//$this->logger->write ($cell->getCalculatedValue());
				if (strpos($cell->getValue(), 'Дата платежного поручения') !== false)
					//$return['paymentDate'] = $workbook->getCell(self::getNextCol($cell->getColumn(), 1) . $row->getRowIndex())->getCalculatedValue();
					$return['paymentDate'] = explode(':', $workbook->getCell($cell->getColumn() . $row->getRowIndex())->getValue())[1];
				if (strpos($cell->getValue(), 'Номер платежного поручения') !== false)
					//$return['paymentNumber'] = $workbook->getCell(self::getNextCol($cell->getColumn(), 1) . $row->getRowIndex())->getCalculatedValue();
					$return['paymentNumber'] = explode(':', $workbook->getCell($cell->getColumn() . $row->getRowIndex())->getValue())[1];
			}
			if (isset ($return['paymentDate']) && isset ($return['paymentNumber']))
				return $return;
		}
		return $return;
	}
	
	public function parseChargeData ($workbook)
	{
		$return = array();
		$start = 'none';
		foreach ($workbook->getRowIterator() as $row)
		{
			//$this->logger->write ($row->getRowIndex());
			//$this->logger->write ($start);
			// поиск строки начисления
			if ($start == 'none')
				foreach ($row->getCellIterator() as $cell)
				{
				    if (strpos($cell->getCalculatedValue(), 'Информация о начислениях') !== false)
				    //if (strpos($cell->getCalculatedValue(), 'Начисления') !== false)
				    {
						$start = 'head';
						break;
					}
				}
			// обработка заголовка начисления
			else if ($start == 'head')
			{
				foreach ($row->getCellIterator() as $cell)
				{
					if (strpos($cell->getCalculatedValue(), 'Номер заказа') !== false)
						$orderCol = $cell->getColumn();
					if (strpos($cell->getCalculatedValue(), 'Ваш SKU') !== false)
						$skuCol = $cell->getColumn();
					if (strpos($cell->getCalculatedValue(), 'Сумма транзакции') !== false)
						$sumCol = $cell->getColumn();
					if (strpos($cell->getCalculatedValue(), 'Источник транзакции') !== false)
						$paymentTypeCol = $cell->getColumn();
				}
				$start = 'data';
			}
			// обработка данных насчисления
			else if ($start = 'data')
			{
				preg_match ('/^\d{8,9}$/', $workbook->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(), $matches);
				
				if (!$matches && $workbook->getCell($paymentTypeCol . ($row->getRowIndex()))->getCalculatedValue() == null)
					break;

				if (!$matches)
					continue;
				
				switch ($workbook->getCell($paymentTypeCol . ($row->getRowIndex()))->getCalculatedValue()) {
					case "Платёж покупателя":
						$paymentType = 1;
						break;
					case "Платёж за скидку маркетплейса":
						$paymentType = 2;
						break;
					case "Платёж за скидку по бонусам СберСпасибо":
						$paymentType = 3;
						break;
					case "Платёж за скидку по баллам Яндекс.Плюса":
					    $paymentType = 4;
					    break;
					case "Платёж за скидку по баллам Яндекс Плюса":
					    $paymentType = 4;
					    break;
					default:
						$paymentType = 0;
				}
				
				$return[] = array (
					'orderNumber' => $workbook->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(),
					'incomingNumber' => trim((string)$this->fileInfo['paymentNumber']),
					'incomingDate' => DateTime::createFromFormat('d.m.Y', trim($this->fileInfo['paymentDate']))->format('Y-m-d'),
					'date' => DateTime::createFromFormat('d.m.Y', trim($this->fileInfo['paymentDate']))->format('Y-m-d'),
					'amount' => (float)$workbook->getCell($sumCol . $row->getRowIndex())->getCalculatedValue(),
					'paymentType' => $workbook->getCell($skuCol . $row->getRowIndex())->getCalculatedValue() . '-' . $paymentType,
					'paymentDescription' => $workbook->getCell($paymentTypeCol . ($row->getRowIndex()))->getCalculatedValue()
				);
			}
		}
		return $return;
	}

	public function parseStornoData ($workbook)
	{
		$return = array();
		$start = 'none';
		foreach ($workbook->getRowIterator() as $row)
		{
			// поиск строки сторно
			if ($start == 'none')
				foreach ($row->getCellIterator() as $cell)
				{
					if (strpos($cell->getCalculatedValue(), 'Информация о возвратах и компенсациях покупателям') !== false)
					{
						$start = 'head';
						break;
					}
				}
			// обработка заголовка начисления
			else if ($start == 'head')
			{
				foreach ($row->getCellIterator() as $cell)
				{
					if (strpos($cell->getCalculatedValue(), 'Номер заказа') !== false)
						$orderCol = $cell->getColumn();
					if (strpos($cell->getCalculatedValue(), 'Ваш SKU') !== false)
						$skuCol = $cell->getColumn();
					if (strpos($cell->getCalculatedValue(), 'Сумма транзакции') !== false)
						$sumCol = $cell->getColumn();
					if (strpos($cell->getCalculatedValue(), 'Источник транзакции') !== false)
						$paymentTypeCol = $cell->getColumn();
				}
				$start = 'data';
			}
			// обработка данных насчисления
			else if ($start = 'data')
			{
				preg_match ('/^\d{8,9}$/', $workbook->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(), $matches);
				
				if (!$matches)
					break;
				
				switch ($workbook->getCell($paymentTypeCol . ($row->getRowIndex()))->getCalculatedValue()) {
					case "Возврат платежа покупателя":
						$paymentType = 1;
						break;
					case "Возврат платежа за скидку маркетплейса":
						$paymentType = 2;
						break;
					case "Возврат платежа за скидку по бонусам СберСпасибо":
						$paymentType = 3;
						break;
					case "Возврат платежа за скидку по баллам Яндекс.Плюса":
						$paymentType = 4;
						break;
					default:
						$paymentType = 0;
				}
				
				$return[] = array (
					'orderNumber' => $workbook->getCell($orderCol . ($row->getRowIndex()))->getCalculatedValue(),
				    'incomingNumber' => trim((string)$this->fileInfo['paymentNumber']),
				    'incomingDate' => DateTime::createFromFormat('d.m.Y', trim($this->fileInfo['paymentDate']))->format('Y-m-d'),
				    'date' => DateTime::createFromFormat('d.m.Y', trim($this->fileInfo['paymentDate']))->format('Y-m-d'),
					'amount' => (float)$workbook->getCell($sumCol . $row->getRowIndex())->getCalculatedValue(),
					'paymentType' => $workbook->getCell($skuCol . $row->getRowIndex())->getCalculatedValue() . '-' . $paymentType,
					'paymentDescription' => $workbook->getCell($paymentTypeCol . ($row->getRowIndex()))->getCalculatedValue()
				);
			}
		}
		return $return;
	}
}
?>
