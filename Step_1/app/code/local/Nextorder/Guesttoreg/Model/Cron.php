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
        $collection = Mage::getResourceModel('sales/order_collection')
//            ->addAttributeToFilter('increment_id', array('in' => '1380-16-105'));
            ->addFieldToSelect('*')
////            ->addAttributeToFilter('increment_id', array('nin' => $this->getSusOrder()))
            ->addAttributeToFilter('increment_id', array('like' => '12%-16-105'))
//            ->addAttributeToFilter('increment_id', array('nlike' => '%-15-%'))
            ->addFieldToFilter('customer_group_id', 0);

        foreach ($collection as $order) {

            $orderInkreID = $order->getIncrementId();
            $billingID = $order->getBillingAddress()->getId();
            $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
            $GuestLastName = $addressForOrder->getData("lastname");
            $GuestFirstName = $addressForOrder->getData("firstname");
            $GuestPrefix = $order->getData('customer_gender');
            $GuestEmail = $addressForOrder->getData("email");
//            Mage::log($orderInkreID." ".$GuestLastName." ".$GuestFirstName." ".$GuestPrefix." ".$GuestEmail, null, 'xulin.log');
            $result = $this->getPreMatched($GuestLastName, $GuestFirstName, $GuestPrefix, $GuestEmail);
            if($result['status'] != "emailmatched"){
                if(count($result['customerids']) == 0){
                    Mage::log("New Customer: ".$orderInkreID, null, 'xulin.log');
                    $this->_customerGenerate($orderInkreID);
                    $this->duplicateForEmail[] = $GuestEmail;
                }else{
                    Mage::log("Continue Compare: ".$orderInkreID, null, 'xulin.log');
                    if(!is_dir($base_path."/var/new_customer")) {
                        mkdir($base_path . "/var/new_customer", 0777);
                    }
                    file_put_contents($base_path."/var/new_customer/customer_verdacht.txt", $orderInkreID."@".implode(",",$result['customerids'])."&",FILE_APPEND);
                    $urString = str_replace(PHP_EOL,'',file_get_contents($base_path."/var/new_customer/customer_verdacht.txt"));
                    file_put_contents($base_path."/var/new_customer/customer_verdacht.txt", $urString);
                }
            }else{
                Mage::log("Full Matched: ".$orderInkreID, null, 'xulin.log');
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

            $this->_orderAssignByEmail($orderInkreId, $addressForOrder->getData("email"));
        }else{

            }
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

        return Mage::log("New Customer: ". $customer->getId(), null, 'xulin.log');
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

        return true;
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
//    public function _readOrders(){
//
//        $base_path = Mage::getBaseDir('base');
//        $orgin_string = file_get_contents($base_path."/var/new_customer/customer_generate.txt");
//        $string_to_array = explode(',',$orgin_string);
////        array_pop($string_to_array);
//        unset($string_to_array[count($string_to_array)-1]);
//        foreach($string_to_array as $orderInkreId){
//
//                $result = $this->_customerGenerate($orderInkreId, $this->duplicateForEmail);
//                $this->duplicateForEmail[] = $result[1];
//        }
//        file_put_contents($base_path."/var/new_customer/customer_generate.txt", "");
//        return "Neue Kunden sind generiert worden.";
//    }

//    public function _generateExcel($dataToExcel){
//
//        /** Error reporting */
//        error_reporting(E_ALL);
//        ini_set('display_errors', TRUE);
//        ini_set('display_startup_errors', TRUE);
//        date_default_timezone_set('Europe/Berlin');
//        define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
//
//        $objPHPExcel = new PHPExcel();
//// Set document properties
//        $adminUser = Mage::getSingleton('admin/session')->getUser()->getUsername();
//        $objPHPExcel->getProperties()->setCreator($adminUser)
//            ->setLastModifiedBy($adminUser)
//            ->setTitle("New Customers Generate at ".date("Y.m.d"))
//            ->setSubject("New Customers Generate at ".date("Y.m.d"))
//            ->setDescription("Cron generates New Customers and assigns order to Customers at ".date("Y.m.d"))
//            ->setKeywords("New Customers")
//            ->setCategory("New Customers");
//// Add some data
//        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'Customer ID')
//            ->setCellValue('B1', 'Assigned Increment Order ID');
//        $index = 2;
//        foreach($dataToExcel as $row){
//            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$index, $row['customerid'])
//                ->setCellValue('B'.$index, $row['orderinkreid']);
//            $index++;
//        }
//// Save Excel 2007 file
//        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
//        $objWriter->save(str_replace('.php', '.xlsx', __FILE__));
//        rename(str_replace('.php', '.xlsx', __FILE__), Mage::getBaseDir("base")."/var/new_customer/New_Customers_".date("Y.m.d").".xlsx");
//
//        return Mage::getBaseDir("base")."/var/new_customer/New_Customers_".date("Y.m.d").".xlsx";
//    }
}
