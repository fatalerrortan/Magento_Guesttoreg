<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.04.16
 * Time: 10:47
 */
class Nextorder_Guesttoreg_Block_Adminhtml_Render_Email extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{

    public function render(Varien_Object $row){

        $inkreId =  (int)$row->getData($this->getColumn()->getIndex());
        $order = Mage::getModel("sales/order")->loadByIncrementId($inkreId);
        $billingID = $order->getBillingAddress()->getId();
        $addressForOrder = Mage::getModel('sales/order_address')->load($billingID);

        return "<span>".$addressForOrder->getData('email')."</span>";
    }
}
?>