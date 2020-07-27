<?php
class HusseyCoding_EvolvedCaching_Block_Adminhtml_Evolvedcaching_Grid_Column_Renderer_Timestamp extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $now = Mage::getModel('core/date')->timestamp();
        $date = $row->getAccessed();
        $date = strtotime($date, $now);
        if ($date > 0):
            $date = new Zend_Date($date, Zend_Date::TIMESTAMP);

            return $this->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
        endif;
        
        return $row->getAccessed();
    }
}
