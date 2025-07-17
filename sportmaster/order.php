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
     * @param string $order the order object for the API.
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
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Processing products: ' . json_encode($this->sportmasterOrder['products'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            foreach ($this->sportmasterOrder['products'] as &$product) {
                $productMS = $this->getProductByOfferId($product['offerId'], $productMSClass);
                $product['msProduct'] = $productMS; // Add the MS product to the product
                $positions[] = $this->createPosition($product, $productMSClass);
            }
            unset($product); // Always unset reference after foreach
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
        } else {
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' No products found in order');
            return false;
        };
        $this->alignTotalAmount($positions, $this->sportmasterOrder['totalCost']['amount']);
        
        $createdDate = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $this->sportmasterOrder['createDate']);
        $shipmentDate = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $this->sportmasterOrder['plannedDate']);

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
                    'href' => MS_DELIVERY_VALUE_SDEK,
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
        $orderMS['vatEnabled'] = true;
        $orderMS['vatIncluded'] = true;
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
        $this->sportmasterOrder['msOrder'] = $orderMS;
        return $orderMS;
    }

    /**
     * Fetches a product by its offer ID.
     *
     * @param string $offerId The offer ID of the product.
     * @param ProductsMS $productMSClass The ProductsMS class instance.
     * @return array The product data.
     */
    private function getProductByOfferId($offerId, $productMSClass)
    {
        $productMS = $productMSClass->findProductsByCode($offerId);
        if (isset($productMS[0])) {
            return $productMS[0];
        } else {
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Product not found for offerId: ' . $offerId);
            return $productMSClass->findProductsByCode('000-0000')[0];
        }
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
        $position = array();
        $position['quantity'] = isset($product['quantity']) ? $product['quantity'] : 1; // Default quantity
        $position['reserve'] = isset($product['quantity']) ? $product['quantity'] : 1; // Default reserve
        if (isset($product['msProduct']) && is_array($product['msProduct'])) {
            $position['price'] = $productMSClass->getPrice($product['msProduct'], MS_PRICE_SPORTMASTER);
            $position['vat'] = isset($product['msProduct']['effectiveVat']) ? $product['msProduct']['effectiveVat'] : 0; // Default VAT
            $position['assortment'] = array(
                'meta' => $product['msProduct']['meta']
            );
        } else {
            $logger->write(__LINE__ . ' '. __FUNCTION__ . ' No MS product found for product: ' . json_encode($product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return false;
        }
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

    /**
     * Transforms sportmaster order into package change request.
     * 
     * @param array $packages The packages to change.
     */
    public function transformToPackageChangeRequest()
    {
        $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Transforming to package change request: ' . json_encode($this->sportmasterOrder, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $packageChangeRequest = array();
        $productMSClass = new \ProductsMS();
        $exemplarIds = array();
        $totalWeight = 0;
        $totalLength = 0;
        $totalWidth = 0;
        $totalHeight = 0;
        foreach ($this->sportmasterOrder['products'] as $product){
            $totalWeight += $product['msProduct']['weight'] ?? 0;
            $totalLength += $productMSClass->getAttribute($product['msProduct'], MS_API_PRODUCT_LENGTH);
            $totalWidth += $productMSClass->getAttribute($product['msProduct'], MS_API_PRODUCT_WIDTH);
            $totalHeight += $productMSClass->getAttribute($product['msProduct'], MS_API_PRODUCT_HEIGHT);
            foreach ($product['exemplars'] as $exemplar) {
                $exemplarIds[] = $exemplar['id'];
            }
        }
        $package = array(
            'weightAndSizeCharacteristics' => array(
                'weight' => round($totalWeight * 1.01, 3), // add 1% to weight, round to 3 decimals
                'height' => round($totalHeight * 1.05), // add 5% to height
                'length' => round($totalLength * 1.05), // add 5% to length
                'width' => round($totalWidth * 1.05) // add 5% to width
            ),
            'exemplarIds' => $exemplarIds
        );
        $packageChangeRequest[] = $package;
        $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Package change request: ' . json_encode($packageChangeRequest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        return $packageChangeRequest;
    }

    /**
     * Returns the sprotmaster order object.
     * @return array The sportmaster order object.
     */
    public function getSportmasterOrder()
    {
        $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Returning sportmaster order: ' . json_encode($this->sportmasterOrder['id'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if (empty($this->sportmasterOrder)) {
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' No sportmaster order found');
            return false;
        }
        return $this->sportmasterOrder;
    }
    /**
     * Add label to the ms order.
     * @return msOrder The sportmaster order object with the label added.
     * This function is a placeholder and should be implemented to handle the label addition logic.
     */
    public function addLabelToMsOrder($label)
    {
        $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Adding label to ms order: ' . json_encode($label['fileName'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if (empty($this->sportmasterOrder)) {
            $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' No sportmaster order found');
            return false;
        }
        // decode the label file from base64
        $labelContent = base64_decode($label['fileContent']);
        $this->sportmasterOrder['msOrder']['attributes'][] = array(
            'meta' => array (
                'href' => MS_ATTR . MS_WB_FILE_ATTR,
                'type' => 'attributemetadata',
                'mediaType' => 'application/json'
            ),
            'file' => array(
                'filename' => $label['fileName'],
                'content' => $labelContent
            )
        );
        $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' sportmasterOrder: ' . json_encode($this->sportmasterOrder, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->log->write(__LINE__ . ' '. __FUNCTION__ . ' Label added to ms order: ' . json_encode($this->sportmasterOrder['msOrder'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        // Placeholder for label addition logic
        return $this->sportmasterOrder['msOrder'];
    }
}       
