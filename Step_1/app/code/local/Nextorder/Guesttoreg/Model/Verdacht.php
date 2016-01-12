<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 12.01.16
 * Time: 12:02
 */
require_once dirname(__FILE__)."/Classes/PHPExcel.php";
class Nextorder_Guesttoreg_Model_Verdacht{

    public function _verdachtExcel(){

        $base_path = Mage::getBaseDir('base');
        $orgin_string = file_get_contents($base_path."/media/new_customer/customer_verdacht.txt");
        $string_to_array = explode('&',$orgin_string);
        $dataToExcel = array();
        foreach($string_to_array as $object){
            if(!empty($object)) {
                $dataToExcel[] = array("orderid" => strstr($object, "@", true), "customers" => strstr($object, "@"));
            }
        }

        $path_for_excel = $this->_generateExcel($dataToExcel);

//        Mage::log($dataToExcel, null, 'xulin.log');
        return "Die mitgenerierte Excel-Datei über die Gast-Bestellungen und möglichen Kunden befindet sich auf ".$path_for_excel;
    }

    public function _generateExcel($dataToExcel){

        /** Error reporting */
        error_reporting(E_ALL);
        ini_set('display_errors', TRUE);
        ini_set('display_startup_errors', TRUE);
        date_default_timezone_set('Europe/Berlin');
        define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

        $objPHPExcel = new PHPExcel();
// Set document properties
        $adminUser = Mage::getSingleton('admin/session')->getUser()->getUsername();
        $objPHPExcel->getProperties()->setCreator($adminUser)
            ->setLastModifiedBy($adminUser)
            ->setTitle("New Customers Generate at ".date("Y.m.d"))
            ->setSubject("New Customers Generate at ".date("Y.m.d"))
            ->setDescription("Cron generates New Customers and assigns order to Customers at ".date("Y.m.d"))
            ->setKeywords("New Customers")
            ->setCategory("New Customers");
// Add some data
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'Bestellnummer')
            ->setCellValue('B1', 'Customers im Verdacht(Customer ID)');
        $index = 2;
        foreach($dataToExcel as $row){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$index, $row['orderid'])
                ->setCellValue('B'.$index, $row['customers']);
            $index++;
        }
// Save Excel 2007 file
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save(str_replace('.php', '.xlsx', __FILE__));
        rename(str_replace('.php', '.xlsx', __FILE__), Mage::getBaseDir("base")."/media/new_customer/Verdacht_".date("Y.m.d").".xlsx");

        return Mage::getBaseDir("base")."/media/new_customer/Verdacht_".date("Y.m.d").".xlsx";
    }
}