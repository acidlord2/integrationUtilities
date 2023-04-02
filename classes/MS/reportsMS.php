<?php
/**
 *
 * @class ReportsMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ReportsMS
{
	private static $logFilename = 'classes - MS - reportsMS.log';
	// find report with given entity and report name
    public static function findReportByName($entity, $reportName)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$logger->write (__LINE__ . ' findReportByName.entity - ' . $entity);
		$logger->write (__LINE__ . ' findReportByName.reportName - ' . $reportName);
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/' . $entity . MS_API_CUSTOMERREPORTS;
		$logger->write (__LINE__ . ' findReportByName.service_url - ' . $service_url);
		APIMS::getMSData($service_url, $reportsJson, $reports);
		$logger->write (__LINE__ . ' findReportByName.reports - ' . json_encode ($reports, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		if (isset ($reports['rows'][0]))
		{
			$reportKey = array_search ($reportName, array_column ($reports['rows'], 'name'));
			if ($reportKey === false)
				return false;
			else
				return $reports['rows'][$reportKey];
		}
		else
			return false;			
	}
	
    public static function printReport ($id, $entity, $reportMeta)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$logger->write (__LINE__ . ' printReport.id - ' . $id);
		$logger->write (__LINE__ . ' printReport.entity - ' . $entity);
		$logger->write (__LINE__ . ' printReport.reportMeta - ' . json_encode ($reportMeta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		$postData = array (
			'template' => array (
				'meta' => $reportMeta 
			),
			'extension' => 'pdf'
		);
		
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/' . $entity . '/' . $id . '/export';
		$logger->write (__LINE__ . ' printReport.service_url - ' . $service_url);
		$pdf = APIMS::postMSDataBlob ($service_url, $postData);
		$logger->write (__LINE__ . ' printReport.pdf - ' . $pdf);
		return $pdf;
	}
	
}

?>