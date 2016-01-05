<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.16
 * Time: 17:36
 */
class Nextorder_Guesttoreg_Model_Cron{

    protected $cron_status = false;

    public function _readOrders(){

        $base_path = Mage::getBaseDir('base');
        $orgin_string = file_get_contents($base_path."/media/new_customer/customer_generate.txt");
        $string_to_array = explode(',',$orgin_string);
        foreach($string_to_array as $orderInkreId){
            if(!empty($orderInkreId)){
                $this->_customerGenerate((int)$orderInkreId);
            }
        }
        file_put_contents($base_path."/media/new_customer/customer_generate.txt", "");
    }

    protected function _customerGenerate($orderInkreId){

        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInkreId);
        $billingID = $order->getBillingAddress()->getId();
        $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);

//        $websiteId = Mage::app()->getWebsite()->getId();
        $websiteId = Mage::getModel('core/store')->load($order->getStoreId())->getWebsiteId();
//        $store = Mage::app()->getStore()->getId();
        $customer = Mage::getModel("customer/customer");
        $customer->setWebsiteId($websiteId)
//            ->setStore($store)
            ->setFirstname($addressForOrder->getData("firstname"))
            ->setLastname($addressForOrder->getData("lastname"))
            ->setEmail($addressForOrder->getData("email"))
            ->setData('prefix', $addressForOrder->getData("prefix"))
            ->setData('gender', ($addressForOrder->getData("prefix") == 'Herr') ? 1 : 2)
            ->setPassword('testtest');

        $customer->save();
    }
}