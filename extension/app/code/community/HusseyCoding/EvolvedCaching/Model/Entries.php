<?php
class HusseyCoding_EvolvedCaching_Model_Entries extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('evolvedcaching/entries');
    }
    
    public function getTax()
    {
        return (float) parent::getTax();
    }
}