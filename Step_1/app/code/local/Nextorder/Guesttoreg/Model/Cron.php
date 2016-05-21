<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.16
 * Time: 17:36
 */
//require_once dirname(__FILE__)."/Classes/PHPExcel.php";
class Nextorder_Guesttoreg_Model_Cron{

//    protected $cron_status = false;
    public $duplicateForEmail = array();
    public function _getCustomerOrders(){

        $base_path = Mage::getBaseDir('base');
        $config_param = Mage::getStoreConfig('section_reg/group_reg/field_reg_start', Mage::app()->getStore());
        if(empty($config_param)){
            $collection = Mage::getResourceModel('sales/order_collection')
//            ->addAttributeToFilter('increment_id', array('in' => '1380-16-105'));
                ->addFieldToSelect('*')
                ->addAttributeToFilter('increment_id', array('like' => '%-%-%'))
                ->addFieldToFilter('customer_group_id', 0);
        }else{
            $collection = Mage::getResourceModel('sales/order_collection')
//            ->addAttributeToFilter('increment_id', array('in' => '1380-16-105'));
                ->addFieldToSelect('*')
                ->addAttributeToFilter('increment_id', array('like' => $config_param))
                ->addFieldToFilter('customer_group_id', 0);
        }

        foreach ($collection as $order) {

            $orderInkreID = $order->getIncrementId();
            $billingID = $order->getBillingAddress()->getId();
            $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
            $GuestLastName = $addressForOrder->getData("lastname");
            $GuestFirstName = $addressForOrder->getData("firstname");
            $GuestPrefix = $order->getData('customer_gender');
            $GuestEmail = $addressForOrder->getData("email");
            $result = $this->getPreMatched($GuestLastName, $GuestFirstName, $GuestPrefix, $GuestEmail);
            if ($result['status'] != "emailmatched") {
                if (count($result['customerids']) == 0) {
                    Mage::log("New Customer: " . $orderInkreID, null, 'xulin.log');
                    $this->_customerGenerate($orderInkreID);
                    $this->duplicateForEmail[] = $GuestEmail;
                } else {
                    Mage::log("Continue Compare: " . $orderInkreID, null, 'xulin.log');
                    if (!is_dir($base_path . "/var/new_customer")) {
                        mkdir($base_path . "/var/new_customer", 0777);
                    }//       return $orderInkreID;

                    file_put_contents($base_path . "/var/new_customer/customer_verdacht.txt", $orderInkreID . "@" . implode(",", $result['customerids']) . "&", FILE_APPEND);
                    $urString = str_replace(PHP_EOL, '', file_get_contents($base_path . "/var/new_customer/customer_verdacht.txt"));
                    file_put_contents($base_path . "/var/new_customer/customer_verdacht.txt", $urString);
                }
            } else {
                Mage::log("Full Matched: " . $orderInkreID, null, 'xulin.log');
                $this->_orderAssignByEmail($orderInkreID, $GuestEmail);
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
                Mage::log("Email Matched", null, 'xulin.log');
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
            $i++;
        }
        Mage::log($matchedOrders, null, 'xulin.log');
        Mage::log("times: ".$i, null, 'xulin.log');
        return array("status"=>"searching", "customerids"=>$matchedOrders);
    }

    protected function _customerGenerate($orderInkreId){

        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInkreId);
        $billingID = $order->getBillingAddress()->getId();
        $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);

        if(in_array($addressForOrder->getData("email"), $this->duplicateForEmail)){

           return $this->_orderAssignByEmail($orderInkreId, $addressForOrder->getData("email"));
        }else {

            $websiteId = Mage::getModel('core/store')->load($order->getStoreId())->getWebsiteId();
            $customer = Mage::getModel("customer/customer");
            $customer->setWebsiteId($websiteId)
                ->setFirstname($addressForOrder->getData("firstname"))
                ->setLastname($addressForOrder->getData("lastname"))
                ->setEmail($addressForOrder->getData("email"))
                ->setData('prefix', $addressForOrder->getData("prefix"))
                ->setData('gender', ($addressForOrder->getData("prefix") == 'Herr') ? 1 : 2)
                ->setData('telephone', $addressForOrder->getData('telephone'))
                ->setPassword('testtest');
            $customer->save();
            $this->_orderAssign($orderInkreId, $customer->getId());
            $this->_setDefaultBillingAdress($billingID, $customer->getId());
            Mage::log("New Customer: " . $customer->getId(), null, 'xulin.log');

            return $customer->getId();
        }

    }//       return $orderInkreID;

    protected function _setDefaultBillingAdress($billingID, $customerid){
        
        $addressForOrder = Mage::getModel('sales/order_>l
address')->load($billingID);
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
            ->setCustomerId($customerid)
            ->setIsDefaultBilling('1')  // set as default f93743or billing
            ->setIsDefaultShipping('1') // set as default for shipping
            ->setSaveInAddressBook('1');
        $customAddress->save();

        return true;
    }

    protected function _orderAssignByEmail($orderInkreId, $email){

        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInkreId);
        $storeId = $order->getStoreId();
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->loadByEmail($email);
//        $customer->setWebsiteId($websiteId);
        $order->setCustomerId($customer->getId());
        $order->setCustomerIsGuest(0);
        $order->setCustomerGroupId($customer->getData('group_id'));
        $order->addStatusHistoryComment('Generiert von Gast Bestellung');
        $order->save();
//        Mage::log("Order: ".$orderInkreId." und Email: ".$email, null, 'xulin.log');
        return $customer->getId();
    }

    protected function _orderAssign($orderInkreId, $customerid){

        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInkreId);
        $storeId = $order->getStoreId();
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->load($customerid);
        $order->setCustomerId($customerid);
        $order->setCustomerIsGuest(0);
        $order->setCustomerGroupId($customer->getData('group_id'));
        $order->addStatusHistoryComment('Generiert von Gast Bestellung');
        $order->save();

        return true;
    }

    public function _singleOrder($single_order){

    $base_path = Mage::getBaseDir('base');
    $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('*')
            ->addAttributeToFilter('increment_id', array('like' => $single_order));

        foreach ($collection as $order) {
            
            $orderInkreID = $order->getIncrementId();
            $billingID = $order->getBillingAddress()->getId();
            $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
            $GuestLastName = $addressForOrder->getData("lastname");
            $GuestFirstName = $addressForOrder->getData("firstname");
            $GuestPrefix = $order->getData('customer_gender');
            $GuestEmail = $addressForOrder->getData("email");
            $result = $this->getPreMatched($GuestLastName, $GuestFirstName, $GuestPrefix, $GuestEmail);

            return $result;
        }
    }
}
