<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.16
 * Time: 17:36
 */
class Nextorder_Guesttoreg_Model_Cron{

//    protected $cron_status = false;

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
        $this->_orderAssign($orderInkreId, $customer->getId());
        $this->_setDefaultBillingAdress($billingID, $customer->getId());
    }

    protected function _setDefaultBillingAdress($billingID, $customerid){

        $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
        $billingAddress = array (
            'prefix'     => $addressForOrder->getData('prefix'),
            'firstname'  => $addressForOrder->getData('firstname'),
            'lastname'   => $addressForOrder->getData('lastname'),
            'street'     => array (
                '0' =>  $addressForOrder->getStreet(1),
                '1' => $addressForOrder->getStreet(2),
                '3' => $addressForOrder->getStreet(3)
            ),
            'city'       => $addressForOrder->getData('city'),
            'region'     => $addressForOrder->getData('region'),
            'region_id'  => $addressForOrder->getData('region_id'),
            'company'    => $addressForOrder->getData('company'),
            'postcode'   => $addressForOrder->getData('postcode'),
            'country_id' => $addressForOrder->getData('country_id'),
            'telephone'  => $addressForOrder->getData('telephone'),
            'fax'        => $addressForOrder->getData('fax'),
        );
        $customAddress   = Mage::getModel('customer/address');
        $customAddress->setData($billingAddress)
            ->setCustomerId($customerid) // this is the most important part
            ->setIsDefaultBilling('1')  // set as default for billing
            ->setIsDefaultShipping('1') // set as default for shipping
            ->setSaveInAddressBook('1');
        $customAddress->save();

    }

    protected function _orderAssign($orderInkreId, $customerid){

        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInkreId);
        $storeId = $order->getStoreId();
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $customer = Mage::getModel("customer/customer")->load($customerid);
        $customer->setWebsiteId($websiteId);
        $order->setCustomerId($customerid);
        $order->setCustomerIsGuest(0);
        $order->setCustomerGroupId($customer->getData('group_id'));
        $order->addStatusHistoryComment('Generiert von Gast Bestellung(Neue Kunden)');
        $order->save();
    }
}