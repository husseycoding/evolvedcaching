<?php
class HusseyCoding_EvolvedCaching_Model_Crawler extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('evolvedcaching/crawler');
    }
}