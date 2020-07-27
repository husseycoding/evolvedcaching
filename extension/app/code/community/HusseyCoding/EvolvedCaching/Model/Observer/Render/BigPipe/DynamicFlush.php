<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Render_BigPipe_DynamicFlush extends HusseyCoding_EvolvedCaching_Model_Observer_Render_Abstract
{
    private $_run = true;
    
    public function frontendEvolvedCachingBigpipeFlush($observer)
    {
        $id = $observer->getId();
        $html = $observer->getHtml();
        $showelements = $observer->getShowelements();
        
        if ($showelements == 'true'):
            $html = '<div style="border:1px solid #f00"><div style="float:left; color:#f00; font-weight:bold; font-size:12px; margin:5px; padding:3px 5px; border:1px solid #000; background-color:#fff; text-transform:initial">' . $id . '</div>'. $html . '<div style="clear:both"></div></div>';
        endif;
        $html = Zend_Json::encode($html);
        
        $id = preg_replace('/[^A-Za-z0-9]{1}/', '_', $id);
        echo '<script type="text/javascript">';
        echo 'thisevolvedupdate.showSingleBlockContent("' . $id . '", ' . $html . ', ' . $showelements . ');';
        echo '</script>';
        $this->_outputCookieUpdate();
        ob_flush();
        flush();
    }
    
    private function _outputCookieUpdate()
    {
        if ($this->_run):
            $this->_run = false;
            $lifetime = Mage::getModel('core/cookie')->getLifetime();
            $formkey = Mage::getSingleton('core/session')->getFormKey();
            echo '<script type="text/javascript">';
            echo 'var date = new Date();';
            echo 'date.setSeconds(date.getSeconds() + parseInt(' . $lifetime . '));';
            echo 'document.cookie = "evolved_formkey=" + "' . $formkey . '" + "; expires=" + date.toUTCString() + "; domain=" + window.location.hostname + "; path=/";';
            echo '</script>';
        endif;
    }
}