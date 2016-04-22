<?php
/**
 * Created by PhpStorm.
 * User: FatalError
 * Date: 4/17/2016
 * Time: 10:47 PM
 */
class Nextorder_Guesttoreg_Block_Adminhtml_Render_Phone extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{

    public function render(Varien_Object $row){

        $inkreId =  (int)$row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel("sales/order")->loadByIncrementId($inkreId);
        $billingID = $order->getBillingAddress()->getId();
        $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);

        return "<span>".$addressForOrder->getData('telephone')."</span>";
    }
}
?>