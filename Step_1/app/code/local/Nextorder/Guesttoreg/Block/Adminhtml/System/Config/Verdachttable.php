<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12.01.16
 * Time: 13:01
 */

class Nextorder_Guesttoreg_Block_Adminhtml_System_Config_Verdachttable
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface{
    public function render(Varien_Data_Form_Element_Abstract $element){

        $base_path = Mage::getBaseDir('base');
        $orgin_string = file_get_contents($base_path."/media/new_customer/customer_verdacht.txt");
        $string_to_array = explode('&',$orgin_string);
        $content = "";
        foreach($string_to_array as $object){
            if(!empty($object)) {
                $content .="<tr><td>".strstr($object, "@", true)."</td><td>".strstr($object, "@")."</td></tr>";
            }
        }
        $table = "<table border='1'><tr><td>Bestellungen im Verdacht(Bestellnummer)</td><td>Mögliche Kunden(CustomerID)</td></tr>".$content."</table><br/><br/><br/>";
        return $table;
    }
}
