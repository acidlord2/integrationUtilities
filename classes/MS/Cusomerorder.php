<?php
/**
 *
 * @class Order
 * @author Georgy Polyan <acidlord@yandex.ru>
 *
 */
namespace MS\Cusomerorder\v1;

class Order {
    // Basic properties
    public $id;
    public $name;
    public $externalCode;
    public $moment;
    public $sum;
    public $created;
    public $updated;
    public $printed;
    public $published;
    public $vatEnabled;
    public $deliveryPlannedMoment;
    public $payedSum;
    public $shippedSum;
    public $invoicedSum;
    public $reservedSum;

    // Attributes (horizontal)
    public $deliveryTime;
    public $deliveryMethod;
    public $paymentType;
    public $placesCount;
    public $barcode;
    public $cancelledMarketplace;

    // Positions
    public $positions = [];

    public function __construct($json = null, $positionsJson = null) {
        if ($json) {
            if (is_string($json)) {
                $data = json_decode($json, true);
            } else {
                $data = $json;
            }
            $this->id = $data['id'] ?? null;
            $this->name = $data['name'] ?? null;
            $this->externalCode = $data['externalCode'] ?? null;
            $this->moment = $data['moment'] ?? null;
            $this->sum = $data['sum'] ?? null;
            $this->created = $data['created'] ?? null;
            $this->updated = $data['updated'] ?? null;
            $this->printed = $data['printed'] ?? null;
            $this->published = $data['published'] ?? null;
            $this->vatEnabled = $data['vatEnabled'] ?? null;
            $this->deliveryPlannedMoment = $data['deliveryPlannedMoment'] ?? null;
            $this->payedSum = $data['payedSum'] ?? null;
            $this->shippedSum = $data['shippedSum'] ?? null;
            $this->invoicedSum = $data['invoicedSum'] ?? null;
            $this->reservedSum = $data['reservedSum'] ?? null;

            // Attributes
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                foreach ($data['attributes'] as $attr) {
                    switch ($attr['name']) {
                        case 'Время доставки':
                            $this->deliveryTime = $attr['value']['name'] ?? null;
                            break;
                        case 'Способ доставки':
                            $this->deliveryMethod = $attr['value']['name'] ?? null;
                            break;
                        case 'Тип оплаты':
                            $this->paymentType = $attr['value']['name'] ?? null;
                            break;
                        case 'Количество мест':
                            $this->placesCount = $attr['value'] ?? null;
                            break;
                        case 'Штрихкод':
                            $this->barcode = $attr['value'] ?? null;
                            break;
                        case 'Отменен Маркетплейс':
                            $this->cancelledMarketplace = $attr['value'] ?? null;
                            break;
                    }
                }
            }
        } else {
            // Empty constructor
            $this->id = null;
            $this->name = null;
            $this->externalCode = null;
            $this->moment = null;
            $this->sum = null;
            $this->created = null;
            $this->updated = null;
            $this->printed = null;
            $this->published = null;
            $this->vatEnabled = null;
            $this->deliveryPlannedMoment = null;
            $this->payedSum = null;
            $this->shippedSum = null;
            $this->invoicedSum = null;
            $this->reservedSum = null;
            $this->deliveryTime = null;
            $this->deliveryMethod = null;
            $this->paymentType = null;
            $this->placesCount = null;
            $this->barcode = null;
            $this->cancelledMarketplace = null;
        }
        // Positions
        if ($positionsJson) {
            require_once __DIR__ . '/CustomerOrderPositions.php';
            $positionsObj = new CustomerOrderPositions($positionsJson);
            $this->positions = $positionsObj->positions;
        }
    }
}

