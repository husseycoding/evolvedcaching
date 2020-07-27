<?php
class HusseyCoding_EvolvedCaching_Model_Mysql4_Crawler extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('evolvedcaching/evolved_crawler', 'id');
    }
}