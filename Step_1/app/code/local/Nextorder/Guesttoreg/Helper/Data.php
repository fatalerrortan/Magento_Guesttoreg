<?php

class Nextorder_Guesttoreg_Helper_Data extends Mage_Core_Helper_Abstract{


    public function _orderAssign($order, $customerID){

//        $order_new = Mage::getModel('sales/order')->loadByIncrementId('100000063');
        $order->setCustomerId(1)->save();

        return Mage::log("Result: WTF!!!!".$order->getId()."_".$customerID, null, 'xulin.log');;
    }
}
?>