<?php

class Nextorder_Guesttoreg_Model_Observer{

    public function _afterOrderSaved(Varien_Event_Observer $event){

        $roleId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        if($roleId == 0) {
            $order = $event->getEvent()->getOrder();
            $orderInkreID = $order->getIncrementId();
            $billingID = $order->getBillingAddress()->getId();
            $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
            $GuestLastName = $addressForOrder->getData("lastname");
            $GuestFirstName = $addressForOrder->getData("firstname");
            $GuestGender = $this->getDataFromCollection($orderInkreID, "customer_gender");

            $result = $this->getPreMatched($GuestLastName, $GuestFirstName, $GuestGender);

            if(count($result) == 0){
                return Mage::log("Result: New Customer! ". $orderInkreID, null, 'xulin.log');
            }
            else{
                $dataToAssign = $this->getFullMatched($result, $orderInkreID, $addressForOrder);
                if($dataToAssign['status'] == 'matched'){
                    $order_saved = Mage::getModel('sales/order')->loadByIncrementId($orderInkreID);
                    $this->_assignOrder($order_saved, $dataToAssign['customerid']);
                }else{
                    return Mage::log("Result: To Hold! ". $orderInkreID, null, 'xulin.log');
                }
            }
//            Mage::log("Result: New Customer! ". $orderInkreID, null, 'xulin.log');
//            $order_saved = Mage::getModel('sales/order')->loadByIncrementId($orderInkreID);
//            $this->_assignOrder($order_saved);
        }
    }

    public function getPreMatched($lastname, $firstname, $gender){

        $matchedOrders = array();
        $sound_lastname = soundex($lastname);
        $sound_firstname = soundex($firstname);

//        Mage::log("Result: Vorname: ".$sound_firstname." Nachname: ".$sound_lastname, null, 'xulin.log');

        $shop_customers_collection = Mage::getModel('customer/customer')->getCollection();
        $index_customers = count($shop_customers_collection);
        for($i = 1;$i <= $index_customers; $i++){
            $customer = Mage::getModel('customer/customer')->load($i);
            $billingAddress = $customer->getPrimaryBillingAddress();
            if($billingAddress == false){
//                Mage::log("No Billinf Ad. ". $customer->getId(), null, 'xulin.log');
                continue;
            }else {
                if(
                    ($sound_firstname == soundex($billingAddress->getFirstname()))
                    &&
                    ($sound_lastname == soundex($billingAddress->getLastname()))
//!!!Waiting for Amasty_addressattr!!!
//                    &&
//                    ($gender == $billingAddress->getData('gender'))
//!!!Waiting for Amasty_addressattr!!!
                ){$matchedOrders[] = $i;}
            }
        }
//        Mage::log($matchedOrders, null, 'xulin.log');
        return $matchedOrders;
    }

    public function getFullMatched($preMatcheds, $orderInkreID, $addressForOrder){

        $GuestTel = $addressForOrder->getData("telephone");
        $formatTel = $this->getFormatTel($GuestTel);
        $GuestEmail = $this->getDataFromCollection($orderInkreID, "customer_email");
        $GuestPLZ = $addressForOrder->getData("postcode");
        $GuestStreet = $addressForOrder->getStreet(1).$addressForOrder->getStreet(2).$addressForOrder->getStreet(3);
        $GuestCity = $addressForOrder->getData("city");
        $indexForHold = 0;
        foreach($preMatcheds as $preMatched){
            $billingAddress = Mage::getModel('customer/customer')->load($preMatched)->getPrimaryBillingAddress();
            if($formatTel != $this->getFormatTel($billingAddress->getData("telephone"))){
//                Mage::log("Result: NO Matched Customer! ". $formatTel. " and ".$this->getFormatTel($billingAddress->getData("telephone")), null, 'xulin.log');
                continue;
            }else{
                if($GuestEmail != Mage::getModel('customer/customer')->load($preMatched)->getData("email")){
//                    Mage::log("Result: NO Matched Customer! ". $GuestEmail, null, 'xulin.log');
                    continue;
                }else{
                    if($GuestPLZ != $billingAddress->getData("postcode")){
//                        Mage::log("Result: NO Matched Customer! ". $GuestPLZ, null, 'xulin.log');
                        continue;
                    }else{
                        $GuestStreet_sound = soundex(strtolower(str_replace(' ','',$GuestStreet)));
                        $sound_billingStreet = soundex(strtolower(str_replace(' ','',$billingAddress->getStreet(1).$billingAddress->getStreet(2).$billingAddress->getStreet(3))));
                        if($GuestStreet_sound != $sound_billingStreet){
//                            Mage::log("Result: NO Matched Customer! ". $GuestStreet, null, 'xulin.log');
                            continue;
                        }else{
                            $sound_city = soundex(strtolower(str_replace(' ','', $GuestCity)));
                            $sound_billingCity = soundex(strtolower(str_replace(' ','', $billingAddress->getData("city"))));
                            if($sound_city != $sound_billingCity){
//                                Mage::log("Result: NO Matched Customer! ". $GuestCity, null, 'xulin.log');
                                continue;
                            }else{
//                                Mage::log("Result: Matched Customer! ". $orderInkreID, null, 'xulin.log');
                                return array('status'=>'matched','customerid'=>$preMatched);
                            }
                        }
                    }
                }
            }
        }
        return array('status'=>'hold');
    }

    public function getDataFromCollection($incrementId,$ref){

        $orderCollection = Mage::getModel("sales/order")->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_is_guest',1)
            ->addFieldToFilter('increment_id', $incrementId);
        foreach( $orderCollection  as $eachorder){
            return $eachorder->getData($ref);
        }
    }

    public function getFormatTel($GuestTel){

        if(substr($GuestTel,0,1) == "+"){
            return str_replace(substr($GuestTel,0,3),0,$GuestTel);
        }else{
            if(substr($GuestTel,0,2) == 00){
                return str_replace(substr($GuestTel,0,4),0,$GuestTel);
            }else{
                return $GuestTel;
            }
        }
    }

    private function _assignOrder($order, $customerid){

        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $storeId = $order->getStoreId();
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $customer = Mage::getModel("customer/customer")->load($customerid);
        $customer->setWebsiteId($websiteId);

        $order->setCustomerId($customer->getId());
        $order->setCustomerIsGuest(0);
        $order->setCustomerGroupId($customer->getData('group_id'));
        $order->addStatusHistoryComment('Generiert von Gast Bestellung');
        $order->save();
    }
}
?>