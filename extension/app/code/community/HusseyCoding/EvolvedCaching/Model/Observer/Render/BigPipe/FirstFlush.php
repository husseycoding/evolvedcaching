<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Render_BigPipe_FirstFlush extends HusseyCoding_EvolvedCaching_Model_Observer_Render_Abstract
{
    public function frontendControllerActionPredispatch($observer)
    {
        if ($this->_isEnabled()):
            evolved::flushPage();
        endif;
    }
}