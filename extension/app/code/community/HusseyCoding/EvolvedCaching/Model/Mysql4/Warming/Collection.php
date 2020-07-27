<?php
class HusseyCoding_EvolvedCaching_Model_Mysql4_Warming_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('evolvedcaching/warming');
    }
}