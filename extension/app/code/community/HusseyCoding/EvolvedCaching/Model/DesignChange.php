<?php
class HusseyCoding_EvolvedCaching_Model_DesignChange extends Mage_Core_Model_Abstract
{
    public function cacheChanges()
    {
        Mage::helper('evolvedcaching/designChange')->cacheChanges();
    }
}