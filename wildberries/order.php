<?php
/*
 * Wildberries Order Management
 * This file handles order-related operations for the Wildberries integration.
 * It includes functions to fetch shipments and manage orders.
 */
namespace Wildberries\Order;

Class OrderTransformation
{
    private $organization;
    private $log;
    private $orderWB;
    /**
     * Constructor initializes the API class and logger.
     *
     * @param string $productMS The product data from MS.
     */

    public function __construct($organization, $orderWB)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');

        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);

        $this->organization = $organization;
        $this->orderWB = $orderWB;
    }
    /**
     * Transforms a wildberries order to MS order format.
     *
     * @return array The transformed MS order.
     */
    public function transformWildberriesToMS()
    {
        $this->log->write(__LINE__ . ' '. __METHOD__ . ' Processing order: ' . $this->orderWB['id']);
       	$positions = array();
        $position = array();
        $position ['quantity'] = 1;
        $position ['reserve'] = 1;
        $position ['price'] = (int)($this->orderWB['salePrice'] ?? $this->orderWB['convertedPrice']);
        $position ['assortment'] = array(
            'meta' => $this->orderWB['productMS'][0]['meta']
        );

        $positions[] = $position;
	
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $this->orderWB['createdAt'], new \DateTimeZone('UTC'));
        $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $attributes = array(
            // тип оплаты
            array(
                'meta' => array (
                    'href' => MS_ATTR . MS_PAYMENTTYPE_ATTR,
                    'type' => 'attributemetadata',
                    'mediaType' => 'application/json'
                ),
                'value' => array(
                    'meta' => array(
                        'href' => MS_PAYMENTTYPE_SBERBANK_ONLINE,
                        'type' => 'customentity',
                        'mediaType' => 'application/json'
                    )
                )
            ),
            // время доставки
            array(
                'meta' => array (
                    'href' => MS_ATTR . MS_DELIVERYTIME_ATTR,
                    'type' => 'attributemetadata',
                    'mediaType' => 'application/json'
                ),
                'value' => array(
                    'meta' => array(
                        'href' => MS_DELIVERYTIME_9_21,
                        'type' => 'customentity',
                        'mediaType' => 'application/json'
                    )
                )
            ),
            // способ доставки
            array(
                'meta' => array (
                    'href' => MS_ATTR . MS_DELIVERY_ATTR,
                    'type' => 'attributemetadata',
                    'mediaType' => 'application/json'
                ),
                'value' => array(
                    'meta' => array(
                        'href' => MS_DELIVERY_VALUE_WB,
                        'type' => 'customentity',
                        'mediaType' => 'application/json'
                    )
                )
            )
        );

        $orderMS = array(
            'name' => 'WB' . (string)$this->orderWB['id'],
            'organization' => array (
                'meta' => array (
                    'href' => constant('MS_' . strtoupper($this->organization)),
                    'type' => 'organization',
                    'mediaType' => 'application/json'
                )
            ),
            'externalCode' => (string)$this->orderWB['id'],
            'moment' => $date->format('Y-m-d H:i:s'),
            'deliveryPlannedMoment' => $date->format('Y-m-d H:i:s'),
            'applicable' => true,
            'vatEnabled' => false,
            'vatIncluded' => false,
            'agent' => array(
                'meta' => array (
                    'href' => MS_WB_AGENT,
                    'type' => 'counterparty',
                    'mediaType' => 'application/json'
                )
            ),
            'state' => array(
                'meta' => array(
                    'href' => MS_MPNEW_STATE,
                    'type' => 'state',
                    'mediaType' => 'application/json'
                )
            ),
            'store' => array(
                'meta' => array(
                    'href' => 'https://api.moysklad.ru/api/remap/1.1/entity/store/dd7ce917-4f86-11e6-7a69-8f550000094d',
                    'type' => 'store',
                    'mediaType' => 'application/json'
                )
            ),
            'group' => array(
                'meta' => array(
                    'href' => 'https://api.moysklad.ru/api/remap/1.1/entity/group/dd4ce7fe-4f86-11e6-7a69-971100000043',
                    'type' => 'group',
                    'mediaType' => 'application/json'
                )
            ),
            'project' => array(
                'meta' => array(
                    'href' => constant('MS_PROJECT_WB_' . strtoupper($this->organization)),
                    'type' => 'project',
                    'mediaType' => 'application/json'
                )
            ),
            'positions' => $positions,
            'attributes' => $attributes
        );
        return $orderMS;
    }

    public function transformWildberriesStickerToMS($orderMS, $supplyOpen)
    {
        $this->log->write(__LINE__ . ' '. __METHOD__ . ' Processing sticker for order: ' . $this->orderWB['barcode']);
        $orderMS["attributes"][] = array(
			'meta' => array (
				'href' => MS_ATTR . MS_BARCODE_ATTR_ID,
				'type' => 'attributemetadata',
				'mediaType' => 'application/json'
			),
			'value' => (string)$this->orderWB['barcode']
		);
		$orderMS["attributes"][] = array(
			'meta' => array (
				'href' => MS_ATTR . MS_DELIVERYNUMBER_ATTR,
				'type' => 'attributemetadata',
				'mediaType' => 'application/json'
			),
			'value' => $this->orderWB['partA'] . '-' . $this->orderWB['partB']
		);
		$orderMS["attributes"][] = array(
			'meta' => array (
				'href' => MS_ATTR . MS_DELIVERYSERVICE_ATTR,
				'type' => 'attributemetadata',
				'mediaType' => 'application/json'
			),
			'value' => $supplyOpen['name']
		);
		$orderMS["attributes"][] = array(
			'meta' => array (
				'href' => MS_ATTR . MS_WB_FILE_ATTR,
				'type' => 'attributemetadata',
				'mediaType' => 'application/json'
			),
			'file' => array(
				'filename' => $this->orderWB['orderId'] . '.png',
				'content' => $this->orderWB['file']
			)
		);
        return $orderMS;
    }
}
