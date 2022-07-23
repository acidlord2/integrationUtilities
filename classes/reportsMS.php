<?php
/**
 *
 * @class ReportsMS
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
class ReportsMS
{
	private static $logFilename = 'classes - reportsMS.log';
	// find report with given entity and report name
    public static function findReportByName($entity, $reportName)
    {
		require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/apiMS.php');
		require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/log.php');
		$logger = new Log (self::$logFilename);
		
		$logger->write ('findReportByName.entity - ' . $entity);
		$logger->write ('findReportByName.reportName - ' . $reportName);
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/' . $entity . MS_API_CUSTOMERREPORTS;
		$logger->write ('findReportByName.service_url - ' . $service_url);
		APIMS::getMSData($service_url, $reportsJson, $reports);
		$logger->write ('findReportByName.reports - ' . json_encode ($reports, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
		
		$logger->write ('printReport.id - ' . $id);
		$logger->write ('printReport.entity - ' . $entity);
		$logger->write ('printReport.reportMeta - ' . json_encode ($reportMeta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		$postData = array (
			'template' => array (
				'meta' => $reportMeta 
			),
			'extension' => 'pdf'
		);
		
		$logger->write ('printReport.postData - ' . json_encode ($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$service_url = MS_API_BASE_URL . MS_API_VERSION_1_2 . '/entity/' . $entity . '/' . $id . '/export';
		$logger->write ('printReport.service_url - ' . $service_url);
		$pdf = APIMS::postMSDataBlob ($service_url, $postData);
		$logger->write ('printReport.pdf - ' . $pdf);
		return $pdf;
	}
	
}

?>