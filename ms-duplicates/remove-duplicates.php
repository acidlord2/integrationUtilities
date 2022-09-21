<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/settings.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/MS/ordersMS.php';

$orderClass = new OrdersMS();
$orders = $orderClass->findOrders('deliveryPlannedMoment%3E='. date('Y-m-d', strtotime('now')) . '%2000:00:00;');
$count = 0;
$duplicates = array();
foreach ($orders as $order)
{ 
    $dub = false;
    
    foreach ($duplicates as $duplicate)
    {
        if ($order['name'] == $duplicate['name'] && $order['agent'] == $duplicate['agent'] && $order['id'] != $duplicate['id'])
        {
            if ($duplicate['updated'] > DateTime::createFromFormat('Y-m-d H:i:s.v', $order['updated']))
            {
                $id = $order['id'];
            }
            else
            {
                $id = $duplicate['id'];
            }
            $dub = true;
        }
    }
    
    if ($dub)
    {
        $orderClass->deleteCustomerorder($id);
        $count++;
    }
    else
    {
        $key = array_search($order['id'], array_column($duplicates, 'id'));
        
        if ($key === false)
        {
            $duplicates[] = array (
                'id' => $order['id'],
                'name' => $order['name'],
                'updated' => DateTime::createFromFormat('Y-m-d H:i:s.v', $order['updated']),
                'agent' => $order['agent']
            );
        }
    }
    
}
echo 'Deleted ' . $count . ' orders';
?>