<?php
class HusseyCoding_EvolvedCaching_Block_Adminhtml_Evolvedcaching extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->_controller = 'adminhtml_evolvedcaching';
        $this->_blockGroup = 'evolvedcaching';
        $this->_headerText = Mage::helper('evolvedcaching')->__('Full Page Cache Entries');
        $this->_removeButton('add');
        if ($this->_isEnabled() && !$this->_mustLogin()):
            $this->_addButton('crawl_all', array(
                'label' => Mage::helper('core')->__('Generate Cache'),
                'onclick' => 'thiscrawler.confirmCrawl()'
            ));
        endif;
        if ($this->_isEnabled()):
            $this->_addButton('refresh_entires', array(
                'label' => Mage::helper('core')->__('Refresh Entries'),
                'onclick' => 'thisrefreshentries.confirmRefresh()'
            ));
        endif;
    }
    
    private function _isEnabled()
    {
        if (Mage::app()->useCache('evolved')):
            return true;
        endif;
        
        return false;
    }
    
    private function _mustLogin()
    {
        return Mage::getStoreConfig('evolvedcaching/general/mustlogin') ? true : false;
    }
}