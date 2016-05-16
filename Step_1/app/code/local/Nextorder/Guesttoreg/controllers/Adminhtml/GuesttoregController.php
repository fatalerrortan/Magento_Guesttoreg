<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10.04.16
 * Time: 16:00
 */
class Nextorder_Guesttoreg_Adminhtml_GuesttoregController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction(){

        $this->_title($this->__('Sales'))->_title($this->__('Nextorder Gast Bestellung'));
        $this->loadLayout();
//        $this->_setActiveMenu('sales/sales');
        $this->_addContent($this->getLayout()->createBlock('guesttoreg/adminhtml_sales_guestorder'));
        $this->renderLayout();
    }

    public function gridAction(){

        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('guesttoreg/adminhtml_sales_guestorder_grid')->toHtml()
        );
    }

    public function orderadminAction(){

        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('guesttoreg/adminhtml_sales_orderassign'));
        $this->renderLayout();
    }

    public function assignAction(){

        $params = $this->getRequest()->getParams();
//        Mage::log($params, null, 'xulin.log');

        if (empty($params['customerid'])) {
            Mage::getSingleton('core/session')->addError('Bitte bestimmen Sie eine Option!!!!!!!!!!!!!!!!!!!!!!');
           return Mage::app()->getResponse()->setRedirect(str_replace('index.php/', 'index.php/admin', Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
//        return Mage::getSingleton('core/session')->addError('Bitte bestimmen Sie eine Option!');
        }
//        if ($params['customerid'] == 'vor') {
//
////                $result = Mage::getModel('guesttoreg/cron')->_singleOrder($params['increId']);
//                $result = $this->_singleOrder($params['increId']);
////                    return Mage::app()->getResponse()->setRedirect(str_replace('index.php/','index.php/admin',Mage::helper("adminhtml")->getUrl("admin/guesttoreg/orderadmin?orderid=".$params['increId'])));
//            Mage::getSingleton('core/session')->addSuccess( $result);
//
//            return Mage::app()->getResponse()->setRedirect(str_replace('index.php/', 'index.php/admin', Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
////            Mage::log("just work", null, 'xulin.log');
//
//        }
            $base_path = Mage::getBaseDir('base');
            if ($params['customerid'] == 'new') {
                $customerID = $this->_customerGenerate($params['increId']);
                if($params['items'] == 'true'){$this->_removeDataFromSus($base_path, $params['indexForRemove']);}
                Mage::getSingleton('core/session')->addSuccess("Neuer Kunde(" . $customerID . ") ist bereit generiert und die Bestellung(" . $params['increId'] . ") an ihn zugeordnet.");
               return Mage::app()->getResponse()->setRedirect(str_replace('index.php/', 'index.php/admin', Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
            } else {
                $this->_orderAssign($params['increId'], $params['customerid']);
                $this->_removeDataFromSus($base_path, $params['indexForRemove']);
                Mage::getSingleton('core/session')->addSuccess('Sie haben die Bestellung an KundenID(' . $params['customerid'] . ') zugeordnet!');
               return Mage::app()->getResponse()->setRedirect(str_replace('index.php/', 'index.php/admin', Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
            }
    }


    private function _removeDataFromSus($baseUrl, $removeIndex)
    {
        $rmDataFromSus = file_get_contents($baseUrl . "/var/new_customer/customer_verdacht.txt");
        file_put_contents($baseUrl . "/var/new_customer/customer_verdacht.txt", str_replace($removeIndex . "&", "", $rmDataFromSus));
        return true;
    }

    private function _orderAssign($orderInkreId, $customerid)
    {

        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInkreId);
        $storeId = $order->getStoreId();
        $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        $customer = Mage::getModel("customer/customer")->setWebsiteId($websiteId)->load($customerid);
        $order->setCustomerId($customerid);
        $order->setCustomerIsGuest(0);
        $order->setCustomerGroupId($customer->getData('group_id'));
        $order->addStatusHistoryComment('Generiert von Gast Bestellung(Neue Kunden)');
        $order->save();

        return true;
    }

    protected function _customerGenerate($orderInkreId)
    {

        $order = Mage::getModel("sales/order")->loadByIncrementId($orderInkreId);
        $billingID = $order->getBillingAddress()->getId();
        $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);

        if (in_array($addressForOrder->getData("email"), $this->duplicateForEmail)) {

            $this->_orderAssignByEmail($orderInkreId, $addressForOrder->getData("email"));
        } else {

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

        return $customer->getId();
    }

    protected function _setDefaultBillingAdress($billingID, $customerid){

        $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
        $billingAddress = array(
            'prefix' => $addressForOrder->getData('prefix'),
            'firstname' => $addressForOrder->getData('firstname'),
            'lastname' => $addressForOrder->getData('lastname') ,
            'street' => array(
                '0' => $addressForOrder->getStreet(1),
                '1' => $addressForOrder->getStreet(2),
                '3' => $addressForOrder->getStreet(3)
            ),
            'city' => $addressForOrder->getData('city'),
            'region' => $addressForOrder->getData('region'),
            'region_id' => $addressForOrder->getData('region_id'),
            'company' => $addressForOrder->getData('company'),
            'postcode' => $addressForOrder->getData('postcode'),
            'country_id' => $addressForOrder->getData('country_id'),
            'telephone' => $addressForOrder->getData('telephone'),
            'fax' => $addressForOrder->getData('fax'),
        );
        $customAddress = Mage::getModel('customer/address');
        $customAddress->setData($billingAddress)
            ->setCustomerId($customerid)
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');
        $customAddress->save();

        return true;
    }

//    public function _singleOrder($single_order){
//
//        $base_path = Mage::getBaseDir('base');
//        $collection = Mage::getResourceModel('sales/order_collection')
//            ->addFieldToSelect('*')
//            ->addAttributeToFilter('increment_id', array('like' => $single_order));
//
//        foreach ($collection as $order) {
//
//            $orderInkreID = $order->getIncrementId();
//            $billingID = $order->getBillingAddress()->getId();
//            $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);
//            $GuestLastName = $addressForOrder->getData("lastname");
//            $GuestFirstName = $addressForOrder->getData("firstname");
//            $GuestPrefix = $order->getData('customer_gender');
//            $GuestEmail = $addressForOrder->getData("email");
//            $result = $this->getPreMatched($GuestLastName, $GuestFirstName, $GuestPrefix, $GuestEmail);
//
//            return $result;
//        }
//    }
//
//    public function getPreMatched($lastname, $firstname, $gender, $email){
//
//        $matchedOrders = array();
//        $sound_lastname = soundex($lastname);
//        $sound_firstname = soundex($firstname);
////        Mage::log("searching Match for ". $sound_firstname."  ".$sound_lastname."   ".$gender , null, 'xulin.log');
//        $customers = Mage::getModel('customer/customer')->getCollection()
//            ->addAttributeToSelect('firstname')
//            ->addAttributeToSelect('lastname')
//            ->addAttributeToSelect('gender')
//            ->addAttributeToSelect('email');
//        $i = 0;
//        foreach($customers as $customer){
//
//            if($email == $customer->getEmail()){
//                Mage::log("Email Matched", null, 'xulin.log');
//                return array("status"=>"emailmatched", "customerid"=>$customer->getId());
//            }
//            if(
//                ($sound_firstname == soundex($customer->getFirstname()))
//                &&
//                ($sound_lastname == soundex($customer->getLastname()))
//                &&
////                    Herr 1, Frau 2
//
//                ($gender == $customer->getGender())
//            ){
//                $matchedOrders[] = $customer->getId();
//            }
//            $i++;
//        }
//        Mage::log($matchedOrders, null, 'xulin.log');
//        Mage::log("times: ".$i, null, 'xulin.log');
//        return array("status"=>"searching", "customerids"=>$matchedOrders);
//    }
}
