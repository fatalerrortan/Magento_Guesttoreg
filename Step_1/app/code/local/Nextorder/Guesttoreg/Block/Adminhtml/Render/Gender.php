<?php
/**
 * Created by PhpStorm.
 * User: FatalError
 * Date: 4/14/2016
 * Time: 11:58 PM
 */

class Nextorder_Guesttoreg_Block_Adminhtml_Render_Gender extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if($value == 1){
            $gender = "Herr";
        }
        else{
            $gender = "Frau";
        }

        return "<span>".$gender."</span>";
    }

}
?>