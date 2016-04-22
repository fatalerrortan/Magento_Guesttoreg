<?php
class Nextorder_Guesttoreg_Block_Adminhtml_Render_Check extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

public function render(Varien_Object $row){
    
$value =  $row->getData($this->getColumn()->getIndex());
return '<a href='. str_replace('index.php/','index.php/admin',Mage::helper("adminhtml")->getUrl("admin/guesttoreg/orderadmin")) .'?orderid='.$value.'>'.$value.'</a>';
}

}
?>