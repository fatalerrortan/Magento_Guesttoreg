<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10.04.16
 * Time: 16:00
 */
class Nextorder_Guesttoreg_Adminhtml_GuesttoregController extends Mage_Adminhtml_Controller_Action{

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

//    public function exportInchooCsvAction(){
//        $fileName = 'orders_inchoo.csv';
//        $grid = $this->getLayout()->createBlock('inchoo_orders/adminhtml_sales_order_grid');
//        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
//    }

//    public function exportInchooExcelAction(){
//
//        $fileName = 'orders_inchoo.xml';
//        $grid = $this->getLayout()->createBlock('inchoo_orders/adminhtml_sales_guestorder_grid');
//        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
//    }

    public function orderadminAction(){

        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('guesttoreg/adminhtml_sales_orderassign'));
        $this->renderLayout();
}

    public function assignAction(){

        $params = $this->getRequest()->getParams();
        if(empty($params['customerid'])){
//            Mage::log($params['indexForRemove'], null, 'xulin.log');
            Mage::getSingleton('core/session')->addError('Bitte bestimmen Sie eine Option!');
            Mage::app()->getResponse()->setRedirect(str_replace('index.php/','index.php/admin',Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
        }else{
        $base_path = Mage::getBaseDir('base');
        if($params['customerid'] == 'new'){
            $customerID = $this->_customerGenerate($params['increId']);
            Mage::getSingleton('core/session')->addSuccess("Neuer Kunde(".$customerID.") ist bereit generiert und die Bestellung(".$params['increId']." an ihn zugeordnet.");
            Mage::app()->getResponse()->setRedirect(str_replace('index.php/','index.php/admin',Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
        }else{
            $this->_orderAssign($params['increId'], $params['customerid']);
            $this->_removeDataFromSus($base_path, $params['indexForRemove']);
            Mage::getSingleton('core/session')->addSuccess('Sie haben die Bestellung an KundenID('.$params['customerid'].') zugeordnet!');
            Mage::app()->getResponse()->setRedirect(str_replace('index.php/','index.php/admin',Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
            }
        }
    }

    private function _removeDataFromSus($baseUrl, $removeIndex){

        $rmDataFromSus = file_get_contents($baseUrl."/var/new_customer/customer_verdacht.txt");
        file_put_contents($baseUrl."/var/new_customer/customer_verdacht.txt", str_replace($removeIndex."&","",$rmDataFromSus));
        return true;
    }

    private function _orderAssign($orderInkreId, $customerid){

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

        return  $customer->getId();
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
}
