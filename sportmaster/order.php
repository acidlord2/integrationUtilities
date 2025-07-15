<?php
/*
 * Sportmaster Order Management
 * This file handles order-related operations for the Sportmaster integration.
 * It includes functions to fetch shipments and manage orders.
 */
namespace Sportmaster\Order;

Class OrderTransformation
{
    private $log;
    private $sportmasterOrder;
    /**
     * Constructor initializes the API class and logger.
     *
     * @param string $clientId The client ID for the API.
     */

    public function __construct($order)
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Sportmaster/Api-v1.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/Common/Log.php');
        require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/MS/productsMS.php');

        $logName = ltrim(str_replace(['/', '\\'], ' - ', str_replace($_SERVER['DOCUMENT_ROOT'], '', __FILE__)), " -");
        $logName .= '.log';
        $this->log = new \Classes\Common\Log($logName);

        $this->sportmasterOrder = $order;
    }
    /**
     * Transforms a Sportmaster order to match the MS requirements.
     *
     * @param array $order The order data from Sportmaster.
     * @return array The transformed order data.
     */
    public function transformSportmasterToMS()
    {
        $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Processing order: ' . json_encode($this->sportmasterOrder, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $orderMS = array();
        $attributes = array();

        $productMSClass = new \ProductsMS();
	    $positions = array();
        if (isset($this->sportmasterOrder['products']) && is_array($this->sportmasterOrder['products']) && count($this->sportmasterOrder['products']) > 0) {
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Processing products: ' . json_encode(order['products'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            foreach ($this->sportmasterOrder['products'] as $product) {
                $positions[] = $this->createPosition($product, $productMSClass);
            }
        } elseif (isset($this->sportmasterOrder['packages']) && is_array($this->sportmasterOrder['packages']) && count($this->sportmasterOrder['packages']) > 0) {
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Processing packages: ' . json_encode($this->sportmasterOrder['packages'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            foreach ($this->sportmasterOrder['packages'] as $package) {
                if (isset($package['packageProducts']) && is_array($package['packageProducts']) && count($package['packageProducts']) > 0) {
                    foreach ($package['packageProducts'] as $product) {
                        $positions[] = $this->createPosition($product, $productMSClass);
                        $attributes[] = array(
                            'meta' => array (
                                'href' => MS_ATTR . MS_BARCODE2_ATTR,
                                'type' => 'attributemetadata',
                                'mediaType' => 'application/json'
                            ),
                            'value' => $package['barcode'] ?? ''
                        );
                    }
                } else {
                    $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' No products found in package ' . json_encode($package, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                }
            }
            $positions[] = $this->createPosition($this->sportmasterOrder['product'], $productMSClass);
        } else {
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' No products found in order');
            return false;
        };
        $this->alignTotalAmount($positions, $this->sportmasterOrder['totalCost']['amount']);
		
	    $createdDate = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $this->sportmasterOrder['createDate']);
        $shipmentDate = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $this->sportmasterOrder['plannedDate']);

        // тип оплаты
        $attributes[] = array(
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
        );
        // время доставки
        $attributes[] = array(
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
        );
        // способ доставки
        $attributes[] = array(
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
        );
        $orderMS['name'] = $this->sportmasterOrder['orderNumber'];
        $orderMS['organization'] = array(
            'meta' => array(
                'href' => MS_ULLO,
                'type' => 'organization',
                'mediaType' => 'application/json'
            )
        );
        $orderMS['externalCode'] = $this->sportmasterOrder['id'];
		$orderMS['moment'] = $createdDate->format('Y-m-d H:i:s');
		$orderMS['deliveryPlannedMoment'] = $shipmentDate->format('Y-m-d H:i:s');
        $orderMS['applicable'] = true;
        $orderMS['vatEnabled'] = false;
        $orderMS['vatIncluded'] = false;
        $orderMS['agent'] = array(
            'meta' => array(
                'href' => MS_SPORTMASTER_AGENT,
                'type' => 'counterparty',
                'mediaType' => 'application/json'
            )
        );
        $orderMS['state'] = array(
            'meta' => array(
                'href' => MS_MPNEW_STATE,
                'type' => 'state',
                'mediaType' => 'application/json'
            )
        );
        $orderMS['store'] = array(
            'meta' => array(
                'href' => MS_STORE,
                'type' => 'store',
                'mediaType' => 'application/json'
            )
        );
        $orderMS['group'] = array(
            'meta' => array(
                'href' => MS_GROUP,
                'type' => 'group',
                'mediaType' => 'application/json'
            )
        );
        $orderMS['project'] = array(
            'meta' => array(
                'href' => MS_PROJECT_SPORTMASTER,
                'type' => 'project',
                'mediaType' => 'application/json'
            )
        );
        $orderMS['positions'] = $positions;
        $orderMS['attributes'] = $attributes;

        $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Transformed order: ' . json_encode($orderMS, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $orderMS;
    }

    /**
     * Creates a position for the order.
     *
     * @param array $product The product data.
     * @param ProductsMS $productMSClass The ProductsMS class instance.
     * @return array The position data.
     */
    private function createPosition($product, $productMSClass)
    {
        $productMS = $productMSClass->findProductsByCode($product['code']);
        $productMS = isset($productMS[0]) ? $productMS[0] : $productMSClass->findProductsByCode('000-000')[0];
        $position['quantity'] = $product['quantity'] ?? 1; // Default quantity
        $position['reserve'] = $product['quantity'] ?? 1; // Default reserve
        $position['price'] = $productMSClass->getPrice($productMS, MS_PRICE_SPORTMASTER);
        $position['vat'] = $product['effectiveVat'] ?? 0; // Default VAT
        $position['assortment'] = array(
            'meta' => $productMS['meta']
        );
        return $position;
    }

    /**
     * Aligns the total amount in RUB of the order with the positions.
     *
     * @param array $positions The positions of the order.
     * @param float $amount The total amount of the order in RUB.
     * @return void
     */
    private function alignTotalAmount(&$positions, $amount)
    {
        $total = 0;
        foreach ($positions as $position) {
            $total += $position['price'] * $position['quantity'];
        }
        if ($total != $amount * 100) {
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Total amount mismatch: expected ' . $amount . ', calculated ' . $total);
            // Adjust the last position to match the total amount
            $lastPositionIndex = count($positions) - 1;
            $positions[$lastPositionIndex]['price'] += ($amount * 100 - $total) / $positions[$lastPositionIndex]['quantity'];
        }
    }
}       