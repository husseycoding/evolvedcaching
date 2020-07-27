<?php
class HusseyCoding_EvolvedCaching_Model_System_Config_Source_Compression
{
    public function toOptionArray()
    {
        if (@extension_loaded('zlib')):
            return array(
                array('value' => 1, 'label' => Mage::helper('evolvedcaching')->__('Yes')),
                array('value' => 0, 'label' => Mage::helper('evolvedcaching')->__('No'))
            );
        endif;
        
        return array(
            array('value' => 0, 'label' => Mage::helper('evolvedcaching')->__('No'))
        );
    }
}
