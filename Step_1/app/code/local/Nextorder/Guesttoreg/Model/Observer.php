<?php

    class Nextorder_Guesttoreg_Model_Observer{

        public function _afterOrderPlaced(Varien_Event_Observer $event){

            $roleId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            if($roleId == 0){
                $order=$event->getEvent()->getOrder();
                $billingID = $order->getBillingAddress()->getId();
                $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
                $GuestLastName = $addressForOrder->getData("lastname");
                $GuestFirstName = $addressForOrder->getData("firstname");
                $GuestGender = $this->getDataFromCollection($order->getIncrementId(), "customer_gender");
                $GuestTel = $addressForOrder->getData("telephone");
                $GuestEmail = $this->getDataFromCollection($order->getIncrementId(), "customer_email");
                $GuestPLZ = $addressForOrder->getData("postcode");
                $GuestStreet = strtolower(str_replace(' ','',$addressForOrder->getStreet(1).$addressForOrder->getStreet(2).$addressForOrder->getStreet(3)));
//                Mage::log($GuestGender, null, 'xulin.log');
//                Stufe 1
               $result = $this->match_Validate($GuestFirstName, $GuestLastName, $GuestGender, $GuestTel, $GuestEmail, $GuestPLZ, $GuestStreet);
                Mage::log("Result: " .$result, null, 'xulin.log');
            }

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

    public function match_Validate($firstname, $lastname, $gender, $tel, $email, $plz, $street){

        $sound_firstname = soundex($firstname);
        $sound_lastname = soundex($lastname);
        $sound_street = soundex($street);
        Mage::log($sound_street." ".$street, null, 'xulin.log');

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
                                    $sound_billingStreet = strtolower(str_replace(' ','',$billingAddress->getStreet(1).$billingAddress->getStreet(2).$billingAddress->getStreet(3)));
                                    if($sound_street == $sound_billingStreet){
                                        Mage::log($sound_billingStreet, null, 'xulin.log');
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
                    ){return 0;}
                    else{
                         return 3;
                    }
                }
            }
        }
    }

}
?>