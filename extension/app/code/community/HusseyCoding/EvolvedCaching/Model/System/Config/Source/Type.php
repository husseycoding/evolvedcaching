<?php
class HusseyCoding_EvolvedCaching_Model_System_Config_Source_Type
{
    public function toOptionArray()
    {
        $return = array();
        
        if (@extension_loaded('redis') && @class_exists('Redis')):
            $return[] = array('value' => 3, 'label' => Mage::helper('evolvedcaching')->__('Redis'));
        endif;
        
        if (@extension_loaded('apc') && @ini_get('apc.enabled')):
            $return[] = array('value' => 2, 'label' => Mage::helper('evolvedcaching')->__('APC'));
        endif;
        
        if (@extension_loaded('memcache') && @class_exists('Memcache')):
            $return[] = array('value' => 1, 'label' => Mage::helper('evolvedcaching')->__('Memcached'));
        endif;
        
        $return[] = array('value' => 0, 'label' => Mage::helper('evolvedcaching')->__('Files'));
        
        return $return;
    }
}
