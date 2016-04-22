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
            Mage::app()->getResponse()->setRedirect(str_replace('index.php/','index.php/admin',Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
            Mage::getSingleton('core/session')->addError('Bitte bestimmen Sie eine Option!');
        }
        $base_path = Mage::getBaseDir('base');
        if($params['customerid'] == 0){
            file_put_contents($base_path."/media/new_customer/customer_generate.txt", $params['increId'].",",FILE_APPEND);
            $urString = str_replace(PHP_EOL,'',file_get_contents($base_path."/media/new_customer/customer_generate.txt"));
            file_put_contents($base_path."/media/new_customer/customer_generate.txt", $urString);
            $this->_removeDataFromSus($base_path, $params['indexForRemove']);
            Mage::getSingleton('core/session')->addSuccess('Sie haben die Bestellung als neuen Kunden gestellt. Bitte führen Sie das Job(generate_new_customer_from_guest) der Extension(Aoe_Scheduler) um den neuen Kunden zu generieren!');
            Mage::app()->getResponse()->setRedirect(str_replace('index.php/','index.php/admin',Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
        }else{
            $this->_orderAssign($params['increId'], $params['customerid']);
            $this->_removeDataFromSus($base_path, $params['indexForRemove']);
            Mage::getSingleton('core/session')->addSuccess('Sie haben die Bestellung an KundenID('.$params['customerid'].') zugeordnet!');
            Mage::app()->getResponse()->setRedirect(str_replace('index.php/','index.php/admin',Mage::helper("adminhtml")->getUrl("admin/guesttoreg/index")));
        }
    }

    private function _removeDataFromSus($baseUrl, $removeIndex){

        $rmDataFromSus = file_get_contents($baseUrl."/media/new_customer/customer_verdacht.txt");
        file_put_contents($baseUrl."/media/new_customer/customer_verdacht.txt", str_replace($removeIndex."&","",$rmDataFromSus));
        return true;
    }

    private function _orderAssign($orderInkreId, $customerid){

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

        return true;
    }
}