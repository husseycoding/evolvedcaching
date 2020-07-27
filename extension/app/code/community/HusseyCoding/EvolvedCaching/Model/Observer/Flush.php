<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Flush
{
    public function adminhtmlControllerActionPostdispatchAdminhtmlCacheMassRefresh($observer)
    {
        $types = Mage::app()->getRequest()->getPost('types');
        if (in_array('evolved', $types)):
            $this->_clearCache();
        endif;
    }
    
    public function adminhtmlControllerActionPostdispatchAdminhtmlCacheFlushSystem($observer)
    {
        if (!Mage::getStoreConfig('evolvedcaching/lock/enabled')):
            $this->_clearCache();
        else:
            Mage::getSingleton('adminhtml/session')->addNotice('The full page cache is locked and has not been cleaned.');
        endif;
    }
    
    public function adminhtmlControllerActionPostdispatchAdminhtmlCacheFlushAll($observer)
    {
        $this->_clearCache();
        if (Mage::getStoreConfig('evolvedcaching/lock/enabled')):
            Mage::getSingleton('adminhtml/session')->addSuccess('The full page cache has been cleaned.');
        endif;
    }
    
    private function _clearCache()
    {
        $helper = Mage::helper('evolvedcaching/entries');
        $cachedir = @class_exists('evolved', false) ? EVOLVED_ROOT : MAGENTO_ROOT;
        if ($cachedir):
            $cachedir .= DS . 'var' . DS . 'evolved' . DS;

            if (is_dir($cachedir)):
                $pages = 'page_*';
                foreach (glob($cachedir . $pages) as $file):
                    @unlink($file);
                endforeach;

                $lifetime = array();
                foreach (Mage::app()->getStores() as $store):
                    $lifetime[] = (int) Mage::getStoreConfig('web/cookie/cookie_lifetime', $store->getCode());
                endforeach;
                $lifetime = max($lifetime) ? max($lifetime) : 3600;
                $users = 'user_*';
                foreach (glob($cachedir . $users) as $file):
                    $time = time();
                    $modified = filemtime($file);
                    if ($time > ($modified + $lifetime)):
                        @unlink($file);
                    endif;
                endforeach;
                
                $helper->clearEntriesOfType('files');
            endif;
        endif;
        
        if (Mage::getStoreConfig('evolvedcaching/storage/use') == '1' && @extension_loaded('memcache') && @class_exists('Memcache')):
            $host = trim(Mage::getStoreConfig('evolvedcaching/storage/host'));
            $host = !empty($host) ? $host : '127.0.0.1';
            $port = (int) Mage::getStoreConfig('evolvedcaching/storage/port');
            $port = !empty($port) ? $port : 11211;
            $persistent = trim(Mage::getStoreConfig('evolvedcaching/storage/persistent')) ? true : false;
            if ($host && $port):
                $memcached = new Memcache;
                if ($persistent):
                    $result = $memcached->pconnect($host, $port);
                else:
                    $result = $memcached->connect($host, $port);
                endif;
                if ($result):
                    $memcached->flush();
                    $memcached->close();
                    $helper->clearEntriesOfType('memcached');
                endif;
            endif;
        elseif (Mage::getStoreConfig('evolvedcaching/storage/use') == '2' && @extension_loaded('apc') && @ini_get('apc.enabled')):
            apc_clear_cache('user');
            $helper->clearEntriesOfType('apc');
        elseif (Mage::getStoreConfig('evolvedcaching/storage/use') == '3' && @extension_loaded('redis') && @class_exists('Redis')):
            $host = trim(Mage::getStoreConfig('evolvedcaching/storage/rhost'));
            $host = !empty($host) ? $host : '127.0.0.1';
            $port = (int) Mage::getStoreConfig('evolvedcaching/storage/rport');
            $port = !empty($port) ? $port : 0;
            $persistence = trim(Mage::getStoreConfig('evolvedcaching/storage/rpersistence'));
            $persistence = !empty($persistence) ? $persistence : null;
            $database = (int) Mage::getStoreConfig('evolvedcaching/storage/rdatabase');
            $database = !empty($database) ? $database : 0;
            $password = trim(Mage::getStoreConfig('evolvedcaching/storage/rpassword'));
            $password = !empty($password) ? $password : null;
            
            $redis = new Redis();
            if (!empty($persistence)):
                $redis->pconnect($host, $port, 2.5, $persistence);
            else:
                $redis->connect($host, $port, 2.5);
            endif;
            if (!empty($password)):
                $redis->auth($password);
            endif;
            $redis->select($database);
            $redis->flushDB();
            $redis->close();
            $helper->clearEntriesOfType('redis');
        endif;
        
        Mage::helper('evolvedcaching/varnish')->clearAllHtml();
    }
    
    public function adminhtmlCleanCatalogImagesCacheAfter($observer)
    {
        Mage::helper('evolvedcaching/varnish')->clearAllImages();
    }
    
    public function adminhtmlCleanMediaCacheAfter($observer)
    {
        Mage::helper('evolvedcaching/varnish')->clearAllCssJs();
    }
}