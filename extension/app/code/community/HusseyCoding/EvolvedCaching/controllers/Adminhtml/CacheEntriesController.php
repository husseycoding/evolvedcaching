<?php
class HusseyCoding_EvolvedCaching_Adminhtml_CacheEntriesController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/cacheentries')
            ->_addBreadcrumb(Mage::helper('evolvedcaching')->__('Full Page Cache Entries'), Mage::helper('evolvedcaching')->__('Full Page Cache Entries'));
        
        return $this;
    }
    public function indexAction()
    {
        $this->_title($this->__('System'))->_title($this->__('Full Page Cache Entries'));
        $this->_initAction()->renderLayout();
    }
    
    public function deleteAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        Mage::helper('evolvedcaching/entries')->clearCacheEntry($id);
        
        $this->_redirect('*/*/index');
    }
    
    public function massDeleteAction()
    {
        foreach ($this->getRequest()->getParam('evolvedcaching') as $id):
            Mage::helper('evolvedcaching/entries')->clearCacheEntry($id);
        endforeach;
        
        $this->_redirect('*/*/index');
    }
    
    public function refreshAction()
    {
        $total = (int) $this->getRequest()->getPost('totalcount');
        $last = (int) $this->getRequest()->getPost('lastcount');
        $sendtotal = $this->getRequest()->getPost('sendtotal') == 'true' ? true : false;
        $lastid = (int) $this->getRequest()->getPost('lastid');
        $completed = 0;
        
        if ($sendtotal):
            $total = $sendtotal = Mage::getResourceModel('evolvedcaching/entries_collection')->count();
        endif;
        
        $collection = Mage::getResourceModel('evolvedcaching/entries_collection');
        $collection->getSelect()
            ->order('id ASC')
            ->limit(1);
        
        if (!empty($lastid)):
            $collection->getSelect()->where('id > ?', $lastid);
        endif;
        
        foreach ($collection as $entry):
            $lastid = $entry->getId();
            Mage::helper('evolvedcaching/entries')->refreshEntry($entry);
            break;
        endforeach;
        
        $completed = ++$last;
        
        if ($completed && $completed >= $total):
            if (Mage::getStoreConfig('evolvedcaching/autowarm/cron')):
                $message = $this->__($total . ' cache entries will be refreshed via cron');
            else:
                $message = $this->__($total . ' cache entries have been refreshed');
            endif;
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        endif;
        
        $this->getResponse()->setBody(Zend_Json::encode(array('completed' => $completed, 'total' => $sendtotal, 'lastid' => $lastid)));
    }
    
    public function crawlAction()
    {
        $total = (int) $this->getRequest()->getPost('totalcount');
        $sendtotal = $this->getRequest()->getPost('sendtotal') == 'true' ? true : false;
        $completed = 0;
        
        if ($sendtotal):
            Mage::helper('evolvedcaching/crawler')->buildUrlList();
            $total = $sendtotal = Mage::getResourceModel('evolvedcaching/crawler_collection')->count();
        endif;
        
        $collection = Mage::getResourceModel('evolvedcaching/crawler_collection');
        $collection->getSelect()->limit(1);
        
        $remain = Mage::getResourceModel('evolvedcaching/crawler_collection')->count();
        $remain--;
        
        foreach ($collection as $item):
            Mage::helper('evolvedcaching/crawler')->crawl($item);
            break;
        endforeach;
        
        $completed = $total - $remain;
        
        if ($completed && $completed >= $total):
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__($total . ' pages crawled'));
        endif;
        
        $this->getResponse()->setBody(Zend_Json::encode(array('completed' => $completed, 'total' => $sendtotal)));
    }
    
    public function viewCacheAction()
    {
        if ($id = $this->getRequest()->getParam('id')):
            $this->getResponse()->setBody(Mage::helper('evolvedcaching/entries')->getCachePageHtml($id));
        endif;
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/cacheentries');
    }
}