<?php
class HusseyCoding_EvolvedCaching_Model_Mysql4_Entries extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('evolvedcaching/evolved_caching', 'id');
    }
}