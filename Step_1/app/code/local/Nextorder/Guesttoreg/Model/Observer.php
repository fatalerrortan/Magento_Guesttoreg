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
            $GuestPrefix = (int)$order->getData('customer_gender');
//            Mage::log($GuestPrefix , null, 'xulin.log');
            $GuestEmail = $addressForOrder->getData("email");
//            if($GuestPrefix == "Herr"){
//                $GuestPrefixCode = 1;
//            }else{$GuestPrefixCode = 2;}

            $result = $this->getPreMatched($GuestLastName, $GuestFirstName, $GuestPrefix, $GuestEmail);
            $base_path = Mage::getBaseDir('base');

            if($result['status'] != "emailmatched"){
                if(count($result['customerids']) == 0){
                    if(!is_dir($base_path."/media/new_customer")) {
                        mkdir($base_path . "/media/new_customer", 0777);
                    }
                    file_put_contents($base_path."/media/new_customer/customer_generate.txt", $orderInkreID.",",FILE_APPEND);
                    $urString = str_replace(PHP_EOL,'',file_get_contents($base_path."/media/new_customer/customer_generate.txt"));
                    file_put_contents($base_path."/media/new_customer/customer_generate.txt", $urString);
                    return Mage::log("Result: New Customer! ". $orderInkreID, null, 'xulin.log');
                }else{
                    $dataToAssign = $this->getFullMatched($result['customerids'], $orderInkreID, $addressForOrder);
                    if($dataToAssign['status'] == 'matched'){
                        $order_saved = Mage::getModel('sales/order')->loadByIncrementId($orderInkreID);
                        $this->_assignOrder($order_saved, $dataToAssign['customerid']);
                    }else{
//                    return Mage::log("Result: To Hold! ". $orderInkreID, null, 'xulin.log');
                        if(!is_dir($base_path."/media/new_customer")) {
                            mkdir($base_path . "/media/new_customer", 0777);
                        }
                        file_put_contents($base_path."/media/new_customer/customer_verdacht.txt", $orderInkreID."@".implode(",",$result['customerids'])."&",FILE_APPEND);
                        $urString = str_replace(PHP_EOL,'',file_get_contents($base_path."/media/new_customer/customer_verdacht.txt"));
                        file_put_contents($base_path."/media/new_customer/customer_generate.txt", $urString);
                        return Mage::log("Result: Order to Hold! ". $orderInkreID, null, 'xulin.log');
                    }
                }
            }else{
                $order_saved = Mage::getModel('sales/order')->loadByIncrementId($orderInkreID);
                $this->_assignOrder($order_saved, (int)$result['customerid']);
                return Mage::log("Result: matched by Email! ". $orderInkreID, null, 'xulin.log');
            }
        }
    }

    public function getPreMatched($lastname, $firstname, $gender, $email){

        $matchedOrders = array();
        $sound_lastname = soundex($lastname);
        $sound_firstname = soundex($firstname);
//        Mage::log("searching Match for ". $sound_firstname."  ".$sound_lastname."   ".$gender , null, 'xulin.log');
        $customers = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname')
            ->addAttributeToSelect('gender')
            ->addAttributeToSelect('email');
        $i = 0;
        foreach($customers as $customer){

            if($email == $customer->getEmail()){
                return array("status"=>"emailmatched", "customerid"=>$customer->getId());
            }
            if(
                ($sound_firstname == soundex($customer->getFirstname()))
                &&
                ($sound_lastname == soundex($customer->getLastname()))
                &&
//                    Herr 1, Frau 2
                ($gender == $customer->getGender())
            ){
                $matchedOrders[] = $customer->getId();
            }
//            else{Mage::log("failed to Match ". soundex($customer->getFirstname())."  ".soundex($customer->getLastname())."   ".$customer->getGender(), null, 'xulin.log');}
        $i++;
        }
        Mage::log($matchedOrders, null, 'xulin.log');
        Mage::log("times: ".$i, null, 'xulin.log');
        return array("status"=>"searching", "customerids"=>$matchedOrders);
    }

    public function getFullMatched($preMatcheds, $orderInkreID, $addressForOrder){

        $GuestTel = $addressForOrder->getData("telephone");
        $formatTel = $this->getFormatTel($GuestTel);
//        $GuestEmail = $addressForOrder->getData("email");
        $GuestPLZ = $addressForOrder->getData("postcode");
        $GuestStreet = $addressForOrder->getStreet(1).$addressForOrder->getStreet(2).$addressForOrder->getStreet(3);
        $GuestCity = $addressForOrder->getData("city");
        $indexForHold = 0;
        foreach($preMatcheds as $preMatched){
            $basicCustomerData = Mage::getModel('customer/customer')->load($preMatched);
            $billingAddress = $basicCustomerData->getPrimaryBillingAddress();
            if(
                ($billingAddress == false)
            ||
                ($basicCustomerData->getFirstname() != $billingAddress->getFirstname())
            ||
                ($basicCustomerData->getLastname() != $billingAddress->getLastname())
            ){
                Mage::log("no same Default Address", null, 'xulin.log');
//                Mage::log($basicCustomerData->getFirstname(), null, 'xulin.log');
//                Mage::log($basicCustomerData->getLastname(), null, 'xulin.log');
//                Mage::log($billingAddress->getFirstname(), null, 'xulin.log');
//                Mage::log($billingAddress->getLastname(), null, 'xulin.log');
//                Mage::log($basicCustomerData->getFirstname()." ".$basicCustomerData->getLastname()." ".$billingAddress->getFirstname()." ".$billingAddress->getLastname(), null, 'xulin.log');
                continue;
            }
            else{
                if($formatTel != $this->getFormatTel($billingAddress->getData("telephone"))){
                Mage::log("Result: NO Matched Customer! ". $formatTel. " and ".$this->getFormatTel($billingAddress->getData("telephone")), null, 'xulin.log');
                continue;
                }else{
                    if($GuestPLZ != $billingAddress->getData("postcode")){
                        Mage::log("Result: NO Matched Customer! ". $GuestPLZ, null, 'xulin.log');
                        continue;
                    }else{
                        $GuestStreet_sound = soundex(strtolower(str_replace(' ','',$GuestStreet)));
                        $sound_billingStreet = soundex(strtolower(str_replace(' ','',$billingAddress->getStreet(1).$billingAddress->getStreet(2).$billingAddress->getStreet(3))));
                        if($GuestStreet_sound != $sound_billingStreet){
                            Mage::log("Result: NO Matched Customer! ". $GuestStreet, null, 'xulin.log');
                            continue;
                        }else{
                            $sound_city = soundex(strtolower(str_replace(' ','', $GuestCity)));
                            $sound_billingCity = soundex(strtolower(str_replace(' ','', $billingAddress->getData("city"))));
                            if($sound_city != $sound_billingCity){
                                Mage::log("Result: NO Matched Customer! ". $GuestCity, null, 'xulin.log');
                                continue;
                            }else{
                                Mage::log("Result: Matched Customer! ". $orderInkreID, null, 'xulin.log');
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