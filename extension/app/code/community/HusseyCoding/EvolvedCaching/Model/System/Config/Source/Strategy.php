<?php
class HusseyCoding_EvolvedCaching_Model_System_Config_Source_Strategy
{
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => Mage::helper('evolvedcaching')->__('Immediate')),
            array('value' => 1, 'label' => Mage::helper('evolvedcaching')->__('Cron'))
        );
    }
}