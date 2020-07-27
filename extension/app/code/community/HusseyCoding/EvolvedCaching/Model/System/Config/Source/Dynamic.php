<?php
class HusseyCoding_EvolvedCaching_Model_System_Config_Source_Dynamic
{
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label' => Mage::helper('evolvedcaching')->__('AJAX')),
            array('value' => 0, 'label' => Mage::helper('evolvedcaching')->__('BigPipe'))
        );
    }
}
