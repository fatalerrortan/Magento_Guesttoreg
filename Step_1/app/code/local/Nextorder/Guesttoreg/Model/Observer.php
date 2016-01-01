<?php

    class Nextorder_Guesttoreg_Model_Observer{

        public function _afterOrderPlaced(Varien_Event_Observer $event){

            $roleId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            if($roleId == 0){
                $order=$event->getEvent()->getOrder();
//                $order=$event->getOrder();
                $billingID = $order->getBillingAddress()->getId();
                $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
                $GuestLastName = $addressForOrder->getData("lastname");
                $GuestFirstName = $addressForOrder->getData("firstname");
                $orderInkreID = $order->getIncrementId();
                $GuestGender = $this->getDataFromCollection($orderInkreID, "customer_gender");
                $GuestTel = $addressForOrder->getData("telephone");
                $GuestEmail = $this->getDataFromCollection($orderInkreID, "customer_email");
                $GuestPLZ = $addressForOrder->getData("postcode");
                $GuestStreet = $addressForOrder->getStreet(1).$addressForOrder->getStreet(2).$addressForOrder->getStreet(3);
                $GuestCity = $addressForOrder->getData("city");
//                Stufe 1
               $result = $this->match_Validate($GuestFirstName, $GuestLastName, $GuestGender, $GuestTel, $GuestEmail, $GuestPLZ, $GuestStreet, $GuestCity);
//                order assign
                if($result['status'] == 'match'){
                    $matched_Customer_Id = (int)$result['customerid'];
//                    Mage::helper('guesttoreg/data')->_orderAssign($order,$matched_Customer_Id);
//                    $this->orderAssign($matched_Customer_Id, $orderInkreID);
//                    Mage::getModel('sales/order')->loadByIncrementId($orderInkreID)
                        $order->setCustomerId($matched_Customer_Id)
                        ->setCustomerIsGuest(0)->save();
//                    Mage::log("Result: WTF!!!!".$order_new->getCustomerId(), null, 'xulin.log');;
//                    $test = Mage::getModel('sales/order')->loadByIncrementId($orderInkreID);
                    Mage::log("Result: WTF!!!!".$orderInkreID."   Matched Customer:  ".$matched_Customer_Id."  CustID in Order: ".$order->getCustomerId(), null, 'xulin.log');
                }else{
                    if($result['status'] == 'new'){
                        Mage::log("Result: New Customer! ".$orderInkreID, null, 'xulin.log');
                    }else{
                        Mage::log("Result: Hold not Sure! ".$orderInkreID, null, 'xulin.log');
                    }
                }
            }
        }

//    public function orderAssign($customerId_assign, $orderIncreId_assign){
//
////        $customer = Mage::getModel('customer/customer')->load($customerId_assign);
//        $order_assign = Mage::getModel('sales/order')->loadByIncrementId($orderIncreId_assign);
//        $order_assign->setCustomerId($customerId_assign)->save();
//
//
//        return  Mage::log("Assign to CUstomer!!!", null, 'xulin.log');
//    }

    public function getDataFromCollection($incrementId,$ref){

        $orderCollection = Mage::getModel("sales/order")->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_is_guest',1)
            ->addFieldToFilter('increment_id', $incrementId);
        foreach( $orderCollection  as $eachorder){
            return $eachorder->getData($ref);
        }
    }

    public function match_Validate($firstname, $lastname, $gender, $tel, $email, $plz, $street, $city){

        $sound_firstname = soundex($firstname);
        $sound_lastname = soundex($lastname);


        $shop_customers = Mage::getModel('customer/customer')->getCollection();
        $index_customers = count($shop_customers);
        for($i = 1;$i <= $index_customers; $i++){

            $customer = Mage::getModel('customer/customer')->load($i);
            $billingAddress = $customer->getPrimaryBillingAddress();

            if(empty($billingAddress)){
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
                  ){
//!!!suppose same Gender Code and go ahead for Match Member
                        if($tel == $billingAddress->getData("telephone")){
                            if($email == $customer->getData("email")){
                                if($plz == $billingAddress->getData("postcode")){
                                    $sound_billingStreet = soundex(strtolower(str_replace(' ','',$billingAddress->getStreet(1).$billingAddress->getStreet(2).$billingAddress->getStreet(3))));
                                    $sound_street = soundex(strtolower(str_replace(' ','',$street)));
                                    if($sound_street == $sound_billingStreet){
                                        $sound_city = soundex(strtolower(str_replace(' ','', $city)));
                                        $sound_billingCity = soundex(strtolower(str_replace(' ','', $billingAddress->getData("city"))));
                                        if($sound_city == $sound_billingCity){

                                            return array('status' => 'match', 'customerid' => $i);
                                        }else{return 3;}
                                    }else{return 3;}
                                }else{return 3;}
                            }else{return 3;}
                        }else{return 3;}
                }else{
                    if(
                        ($sound_firstname != soundex($billingAddress->getFirstname()))
                        &&
                        ($sound_lastname != soundex($billingAddress->getLastname()))
//                      &&
//                      ($gender != $billingAddress->getData('gender'))
                    ){return array('status' => 'new');}
                    else{
                         return array('status' => 'hold');
                    }
                }
            }
        }
    }

}
?>