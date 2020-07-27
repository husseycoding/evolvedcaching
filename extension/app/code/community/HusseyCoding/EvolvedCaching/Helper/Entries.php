<?php
class HusseyCoding_EvolvedCaching_Helper_Entries extends HusseyCoding_EvolvedCaching_Helper_Data
{
    private $_memcached = array();
    private $_redis = array();
    private $_mexists;
    private $_aexists;
    private $_rexists;
    private $_categoryids = array();
    private $_match = false;
    private $_cronwarm;
    private $_ips = array();
    private $_queue = array();
    private $_missing = array();
    private $_stores = array();
    
    public function __construct()
    {
        $this->_mexists = @extension_loaded('memcache') && @class_exists('Memcache');
        $this->_aexists = @extension_loaded('apc') && @ini_get('apc.enabled');
        $this->_rexists = @extension_loaded('redis') && @class_exists('Redis');
    }
    
    public function validateEntries($collection)
    {
        foreach ($collection as $entry):
            $cachekey = 'page_' . $entry->getCachekey();
            switch ($entry->getStorage()):
                case 'files':
                    if ($this->_validateFilesEntry($cachekey)):
                        $entry->setExpired(false);
                    else:
                        $entry->setExpired(true);
                    endif;
                    break;
                case 'memcached':
                    if ($this->_validateMemcachedEntry($cachekey)):
                        $entry->setExpired(false);
                    else:
                        $entry->setExpired(true);
                    endif;
                    break;
                case 'apc':
                    if ($this->_validateApcEntry($cachekey)):
                        $entry->setExpired(false);
                    else:
                        $entry->setExpired(true);
                    endif;
                case 'redis':
                    if ($this->_validateRedisEntry($cachekey)):
                        $entry->setExpired(false);
                    else:
                        $entry->setExpired(true);
                    endif;
                    break;
            endswitch;
        endforeach;
    }
    
    public function refreshEntry($entry)
    {
        if (Mage::getStoreConfig('evolvedcaching/general/refreshexpired')):
            $this->_recacheEntry($entry);
        else:
            $cachekey = 'page_' . $entry->getCachekey();
            switch ($entry->getStorage()):
                case 'files':
                    if ($this->_verifyFilesEntry($cachekey, $entry)):
                        $this->_recacheEntry($entry);
                    endif;
                    break;
                case 'memcached':
                    if ($this->_verifyMemcachedEntry($cachekey, $entry)):
                        $this->_recacheEntry($entry);
                    endif;
                    break;
                case 'apc':
                    if ($this->_verifyApcEntry($cachekey, $entry)):
                        $this->_recacheEntry($entry);
                    endif;
                    break;
                case 'redis':
                    if ($this->_verifyRedisEntry($cachekey, $entry)):
                        $this->_recacheEntry($entry);
                    endif;
                    break;
                default:
                    $this->_clearLogEntry($entry);
            endswitch;
        endif;
    }
    
    private function _recacheEntry($entry)
    {
        $this->_warmByStrategy($entry, true);
        $this->clearCacheEntry($entry->getId());
        $this->processWarmQueue(false, false);
    }
    
    private function _validateFilesEntry($cachekey)
    {
        $path = @class_exists('evolved', false) ? EVOLVED_ROOT : MAGENTO_ROOT;
        $path = $path . DS . 'var' . DS . 'evolved' . DS . $cachekey;
        if (!file_exists($path)):
            return false;
        endif;
        
        return true;
    }
    
    private function _verifyFilesEntry($cachekey, $entry)
    {
        $path = @class_exists('evolved', false) ? EVOLVED_ROOT : MAGENTO_ROOT;
        $path = $path . DS . 'var' . DS . 'evolved' . DS . $cachekey;
        if (!file_exists($path)):
            $this->_clearLogEntry($entry);
        
            return false;
        endif;
        
        return true;
    }
    
    private function _validateMemcachedEntry($cachekey)
    {
        if ($this->_mexists):
            if ($memcached = $this->_getMemcachedServer()):
                if (!$memcached->get($cachekey)):
                    $this->_closeMemcached($memcached);
                    
                    return false;
                endif;
                $this->_closeMemcached($memcached);
            else:
                return false;
            endif;
        else:
            return false;
        endif;
        
        return true;
    }
    
    private function _verifyMemcachedEntry($cachekey, $entry)
    {
        if ($this->_mexists):
            if ($memcached = $this->_getMemcachedServer()):
                if (!$memcached->get($cachekey)):
                    $this->_clearLogEntry($entry);
                    $this->_closeMemcached($memcached);
                    
                    return false;
                endif;
                $this->_closeMemcached($memcached);
            else:
                $this->_clearLogEntry($entry);
                
                return false;
            endif;
        else:
            $this->_clearLogEntry($entry);
            
            return false;
        endif;
        
        return true;
    }
    
    private function _validateRedisEntry($cachekey)
    {
        if ($this->_rexists):
            if ($redis = $this->_getRedisServer()):
                if (!$redis->get($cachekey)):
                    $this->_closeRedis($redis);
                    
                    return false;
                endif;
                $this->_closeRedis($redis);
            else:
                return false;
            endif;
        else:
            return false;
        endif;
        
        return true;
    }
    
    private function _verifyRedisEntry($cachekey, $entry)
    {
        if ($this->_rexists):
            if ($redis = $this->_getRedisServer()):
                if (!$redis->get($cachekey)):
                    $this->_clearLogEntry($entry);
                    $this->_closeRedis($redis);
                    
                    return false;
                endif;
                $this->_closeRedis($redis);
            else:
                $this->_clearLogEntry($entry);
                
                return false;
            endif;
        else:
            $this->_clearLogEntry($entry);
            
            return false;
        endif;
        
        return true;
    }
    
    private function _validateApcEntry($cachekey)
    {
        if ($this->_aexists):
            if (!apc_fetch($cachekey)):
                return false;
            endif;
        else:
            return false;
        endif;
        
        return true;
    }
    
    private function _verifyApcEntry($cachekey, $entry)
    {
        if ($this->_aexists):
            if (!apc_fetch($cachekey)):
                $this->_clearLogEntry($entry);
                
                return false;
            endif;
        else:
            $this->_clearLogEntry($entry);
            
            return false;
        endif;
        
        return true;
    }
    
    private function _clearLogEntry($entry)
    {
        $entry->delete();
    }
    
    public function clearCacheEntry($id)
    {
        $entry = Mage::getModel('evolvedcaching/entries')->load((int) $id);
        
        $this->_clearVarnishEntry($entry);
        
        $cachekey = 'page_' . $entry->getCachekey();
        switch ($entry->getStorage()):
            case 'files':
                $cachedir = @class_exists('evolved', false) ? EVOLVED_ROOT : MAGENTO_ROOT;
                if ($cachedir == 'MAGENTO_ROOT'):
                    $cachedir = getcwd();
                endif;
                if ($cachedir):
                    $cachedir .= DS . 'var' . DS . 'evolved' . DS;
                    $file = $cachedir . $cachekey;
                    if (is_file($file)):
                        @unlink($file);
                    endif;
                endif;
                break;
            case 'memcached':
                if ($this->_mexists):
                    if ($memcached = $this->_getMemcachedServer()):
                        if ($memcached->get($cachekey)):
                            $memcached->delete($cachekey);
                        endif;
                        $this->_closeMemcached($memcached);
                    endif;
                endif;
                break;
            case 'apc':
                if ($this->_aexists):
                    if (apc_fetch($cachekey)):
                        apc_delete($cachekey);
                    endif;
                endif;
            case 'redis':
                if ($this->_rexists):
                    if ($redis = $this->_getRedisServer()):
                        if ($redis->get($cachekey)):
                            $redis->del($cachekey);
                        endif;
                        $this->_closeRedis($redis);
                    endif;
                endif;
                break;
        endswitch;
        
        $this->_clearLogEntry($entry);
    }
    
    private function _getMemcachedServer()
    {
        $memcached = false;
        try {
            $memcached = new Memcache;
            if ($this->_getMemcachedConfig('persistent')):
                if (!$memcached->pconnect($this->_getMemcachedConfig('host'), $this->_getMemcachedConfig('port'))):
                    Mage::getSingleton('adminhtml/session')->addError('Failed to connect to memcached server.');
                    $this->_closeMemcached($memcached);
                    
                    return false;
                endif;
            else:
                if (!$memcached->connect($this->_getMemcachedConfig('host'), $this->_getMemcachedConfig('port'))):
                    Mage::getSingleton('adminhtml/session')->addError('Failed to connect to memcached server.');
                    $this->_closeMemcached($memcached);
                    
                    return false;
                endif;
            endif;
        } catch (Exception $ex) {
            $this->_closeMemcached($memcached);
        }
        
        return $memcached;
    }
    
    private function _getMemcachedConfig($key = false)
    {
        if (empty($this->_memcached)):
            $host = trim(Mage::getStoreConfig('evolvedcaching/storage/host'));
            $this->_memcached['host'] = !empty($host) ? $host : '127.0.0.1';
            $port = (int) Mage::getStoreConfig('evolvedcaching/storage/port');
            $this->_memcached['port'] = !empty($port) ? $port : 11211;
            $persistent = (int) Mage::getStoreConfig('evolvedcaching/storage/persistent');
            $this->_memcached['persistent'] = !empty($persistent) ? $persistent : false;
        endif;
        
        if ($key && isset($this->_memcached[$key])):
            return $this->_memcached[$key];
        endif;
        
        return false;
    }
    
    private function _closeMemcached($memcached = false)
    {
        if (is_object($memcached)):
            $memcached->close();
        endif;
    }
    
    private function _getRedisServer()
    {
        $redis = false;
        try {
            $redis = new Redis();
            $persistence = $this->_getRedisConfig('persistence');
            if (!empty($persistence)):
                $success = $redis->pconnect($this->_getRedisConfig('host'), $this->_getRedisConfig('port'), 2.5, $persistence);
            else:
                $success = $redis->connect($this->_getRedisConfig('host'), $this->_getRedisConfig('port'), 2.5);
            endif;
            if ($success):
                $password = $this->_getRedisConfig('password');
                if (!empty($password)):
                    if (!$redis->auth($password)):
                        $this->_closeRedis($redis);
                        
                        return false;
                    endif;
                endif;
                if (!$redis->select($this->_getRedisConfig('database'))):
                    $this->_closeRedis($redis);
                    
                    return false;
                endif;

                return $redis;
            else:
                $this->_closeRedis($redis);
            endif;
        } catch (Exception $ex) {
            $this->_closeRedis($redis);
        }
        
        return false;
    }
    
    private function _getRedisConfig($key = false)
    {
        if (empty($this->_redis)):
            $host = trim(Mage::getStoreConfig('evolvedcaching/storage/rhost'));
            $this->_redis['host'] = !empty($host) ? $host : '127.0.0.1';
            $port = (int) Mage::getStoreConfig('evolvedcaching/storage/rport');
            $this->_redis['port'] = !empty($port) ? $port : 6379;
            $persistence = trim(Mage::getStoreConfig('evolvedcaching/storage/rpersistence'));
            $this->_redis['persistence'] = !empty($persistence) ? $persistence : null;
            $database = (int) Mage::getStoreConfig('evolvedcaching/storage/rdatabase');
            $this->_redis['database'] = !empty($database) ? $database : 0;
            $password = trim(Mage::getStoreConfig('evolvedcaching/storage/rpassword'));
            $this->_redis['password'] = !empty($password) ? $password : null;
        endif;
        
        if ($key && isset($this->_redis[$key])):
            return $this->_redis[$key];
        endif;
        
        return false;
    }
    
    private function _closeRedis($redis = false)
    {
        if (is_object($redis)):
            $redis->close();
        endif;
    }
    
    public function clearEntriesOfType($type = 'files')
    {
        $resource = Mage::getSingleton('core/resource');
        $table = $resource->getTableName('evolvedcaching/evolved_caching');
        $connection = $resource->getConnection('core_write');
        $sql = 'DELETE FROM `' . $table . '` WHERE `storage` = \'' . $type . '\';';
        $connection->query($sql);
    }
    
    public function clearProductCache($product)
    {
        foreach ($this->getProductPaths($product) as $path):
            if (!$this->_clearCacheByPath($path) && Mage::getStoreConfig('evolvedcaching/autowarm/always')):
                $this->_missing[] = $path;
            endif;
        endforeach;
    }
    
    public function getProductPaths($product)
    {
        $paths = $this->_getProductRewrites($product);
        $categoryids = array_merge($this->_getCategoryIds(), $product->getCategoryIds());
        $categoryids = $this->_addAnchorCategories($categoryids);
        foreach ($categoryids as $id):
            $category = Mage::getModel('catalog/category')->load((int) $id);
            $paths = $this->_getCategoryRewrites($category, $paths);
        endforeach;
        
        return $paths;
    }
    
    public function clearCategoryCache($category)
    {
        foreach ($this->_getCategoryRewrites($category) as $path):
            if (!$this->_clearCacheByPath($path) && Mage::getStoreConfig('evolvedcaching/autowarm/always')):
                $this->_missing[] = $path;
            endif;
        endforeach;
    }
    
    public function clearCmsCache($cms)
    {
        $identifier = $cms->getIdentifier();
        $slashes = substr_count($identifier, '/');
        $stores = $cms->getStores();
        if (empty($stores)):
            $stores = $cms->getStoreId();
        endif;
        foreach ($this->_getStores($stores) as $store):
            $defaulthome = Mage::getStoreConfig('web/default/cms_home_page', $store->getCode());
            if ($identifier == $defaulthome):
                if (!$this->_clearCacheByPath('') && Mage::getStoreConfig('evolvedcaching/autowarm/always')):
                    $this->_missing[] = '';
                    $this->_stores[$store->getId()][] = '';
                endif;
                if (Mage::getStoreConfig('web/url/use_store')):
                    if (!$this->_clearCacheByPath($store->getCode()) && Mage::getStoreConfig('evolvedcaching/autowarm/always')):
                        $this->_missing[] = $store->getCode();
                        $this->_stores[$store->getId()][] = $store->getCode();
                    endif;
                endif;
            else:
                $path = Mage::helper('cms/page')->getPageUrl($cms->getId());
                $parts = explode('/', $path);
                $path = end($parts);
                if ($slashes > 0):
                    $count = $slashes;
                    $catch = 10;
                    while ($count > 0 && $catch > 0):
                        $path = prev($parts) . '/' . $path;
                        $count--;
                        $catch--;
                    endwhile;
                endif;

                if (!$this->_clearCacheByPath($path) && Mage::getStoreConfig('evolvedcaching/autowarm/always')):
                    $this->_missing[] = $path;
                    $this->_stores[$store->getId()][] = $path;
                endif;

                if (Mage::getStoreConfig('web/url/use_store')):
                    if (!$this->_clearCacheByPath($store->getCode() . '/' . $path) && Mage::getStoreConfig('evolvedcaching/autowarm/always')):
                        $this->_missing[] = $path;
                        $this->_stores[$store->getId()][] = $path;
                    endif;
                endif;
            endif;
        endforeach;
    }
    
    private function _getStores($ids)
    {
        $all = false;
        $storeids = array();
        foreach ($ids as $id):
            if ($id == '0'):
                $all = true;
                break;
            else:
                $storeids[] = $id;
            endif;
        endforeach;

        $stores = array();
        if ($all):
            foreach (Mage::app()->getStores() as $store):
                $stores[] = $store;
            endforeach;
        else:
            foreach ($storeids as $id):
                $stores[] = Mage::getModel('core/store')->load((int) $id);
            endforeach;
        endif;
        
        return $stores;
    }
    
    private function _clearCacheByPath($path)
    {
        $collection = Mage::getResourceModel('evolvedcaching/entries_collection');
        if ($path):
            if (Mage::helper('core')->isModuleEnabled('Enterprise_UrlRewrite')):
                $collection->getSelect()->where('request LIKE ?', '/%' . $path . '%');
            else:
                $collection->getSelect()->where('request = ?', '/' . $path);
            endif;
        else:
            $collection->getSelect()->where('request = ?', $path);
        endif;
        
        $found = false;
        foreach ($collection as $entry):
            $found = true;
            if (!$this->_match):
                $this->_match = true;
            endif;
            $this->_warmByStrategy($entry);
            $this->clearCacheEntry($entry->getId());
        endforeach;
        
        return $found;
    }
    
    private function _getProductRewrites($product, $urls = null)
    {
        $urls = isset($urls) ? $urls : array();
        $products = array();
        
        $type = $product->getTypeInstance(true);
        $products[] = $product;
        if (!$type->getChildrenIds($product->getId(), false)):
            if ($parents = $this->_getProductParents($product)):
                foreach ($parents as $parent):
                    $products[] = $parent;
                endforeach;
            endif;
        endif;
        
        foreach ($products as $product):
            $path = 'catalog/product/view/id/' . $product->getId();
            if (Mage::helper('core')->isModuleEnabled('Enterprise_UrlRewrite')):
                $rewrites = Mage::getResourceModel('enterprise_urlrewrite/url_rewrite_collection');
            else:
                $rewrites = Mage::getResourceModel('core/url_rewrite_collection');
            endif;
            $rewrites->getSelect()
                ->where('target_path = ?', $path)
                ->orWhere('target_path LIKE ?', $path . '/%');

            foreach ($rewrites as $rewrite):
                if ($url = $rewrite->getRequestPath()):
                    if (!in_array($url, $urls)):
                        $urls[] = $url;
                        foreach ($this->_getStores($product->getStoreIds()) as $store):
                            $this->_stores[$store->getId()][] = $url;
                        endforeach;
                        if (Mage::getStoreConfig('web/url/use_store')):
                            foreach ($this->_getStores($product->getStoreIds()) as $store):
                                $newurl = $store->getCode() . '/' . $url;
                                if (!in_array($newurl, $urls)):
                                    $urls[] = $newurl;
                                    $this->_stores[$store->getId()][] = $newurl;
                                endif;
                            endforeach;
                        endif;
                    endif;
                endif;
            endforeach;

            if (!in_array($path, $urls)):
                $urls[] = $path;
            endif;
        endforeach;
        
        return $urls;
    }
    
    private function _getCategoryRewrites($category, $urls = null)
    {
        $urls = isset($urls) ? $urls : array();
        $path = 'catalog/category/view/id/' . $category->getId();
        if (Mage::helper('core')->isModuleEnabled('Enterprise_UrlRewrite')):
            $rewrites = Mage::getResourceModel('enterprise_urlrewrite/url_rewrite_collection');
        else:
            $rewrites = Mage::getResourceModel('core/url_rewrite_collection');
        endif;
        $rewrites->getSelect()
            ->where('target_path = ?', $path)
            ->orWhere('target_path LIKE ?', $path . '/%');
        
        foreach ($rewrites as $rewrite):
            if ($url = $rewrite->getRequestPath()):
                if (!in_array($url, $urls)):
                    $urls[] = $url;
                    foreach ($this->_getStores($category->getStoreIds()) as $store):
                        $this->_stores[$store->getId()][] = $url;
                    endforeach;
                    if (Mage::getStoreConfig('web/url/use_store')):
                        foreach ($this->_getStores($category->getStoreIds()) as $store):
                            $newurl = $store->getCode() . '/' . $url;
                            if (!in_array($newurl, $urls)):
                                $urls[] = $newurl;
                                $this->_stores[$store->getId()][] = $newurl;
                            endif;
                        endforeach;
                    endif;
                endif;
            endif;
        endforeach;
        
        if (!in_array($path, $urls)):
            $urls[] = $path;
        endif;
        
        return $urls;
    }
    
    private function _getProductParents($product)
    {
        $parents = array();
        $categoryids = array();
        $products = array();
        
        $type = Mage::getModel('catalog/product_type_grouped');
        foreach ($type->getParentIdsByChild($product->getId()) as $parentid):
            $products[] = Mage::getModel('catalog/product')->load((int) $parentid);
        endforeach;
        
        $type = Mage::getModel('catalog/product_type_configurable');
        foreach ($type->getParentIdsByChild($product->getId()) as $parentid):
            $products[] = Mage::getModel('catalog/product')->load((int) $parentid);
        endforeach;
        
        $type = Mage::getModel('bundle/product_type');
        foreach ($type->getParentIdsByChild($product->getId()) as $parentid):
            $products[] = Mage::getModel('catalog/product')->load((int) $parentid);
        endforeach;
        
        foreach ($products as $product):
            foreach ($product->getCategoryIds() as $id):
                $categoryids[] = $id;
            endforeach;
            $parents[] = $product;
        endforeach;
        
        $this->_setCategoryIds($categoryids);
        
        return $parents;
    }
    
    public function clearBlockHtmlCache()
    {
        Mage::app()->getCacheInstance()->cleanType('block_html');
    }
    
    private function _addAnchorCategories($categoryids)
    {
        foreach ($categoryids as $categoryid):
            $category = Mage::getModel('catalog/category')->load($categoryid);
            $category = $category->getParentCategory();
            $escape = 50;
            while ($category->getId() && $escape > 0):
                if ($category->getIsAnchor()):
                    if (!in_array($category->getId(), $categoryids)):
                        $categoryids[] = $category->getId();
                    endif;
                endif;
                $category = $category->getParentCategory();
                $escape--;
            endwhile;
        endforeach;
        
        return $categoryids;
    }
    
    private function _setCategoryIds($categoryids)
    {
        $this->_categoryids = $categoryids;
    }
    
    private function _getCategoryIds()
    {
        return $this->_categoryids;
    }
    
    private function _buildWarmRequest($entry, $skipvalidate = false)
    {
        if (Mage::getStoreConfig('evolvedcaching/autowarm/enabled')):
            if ($cachekey = $entry->getCachekey() && !$skipvalidate):
                $cachekey = 'page_' . $cachekey;
                switch ($entry->getStorage()):
                    case 'files':
                        if (!$this->_validateFilesEntry($cachekey)):
                            return false;
                        endif;
                        break;
                    case 'memcached':
                        if (!$this->_validateMemcachedEntry($cachekey)):
                            return false;
                        endif;
                        break;
                    case 'apc':
                        if (!$this->_validateApcEntry($cachekey)):
                            return false;
                        endif;
                    case 'redis':
                        if (!$this->_validateRedisEntry($cachekey)):
                            return false;
                        endif;
                        break;
                endswitch;
            endif;
            
            foreach (Mage::app()->getStores() as $store):
                if ($store->getCode() == $entry->getStorecode()):
                    $secure = $entry->getProtocol() == 'http' ? false : true;
                    $baseurl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, $secure);
                endif;
            endforeach;

            if (isset($baseurl)):
                $host = preg_replace('/'. $entry->getProtocol() . ':\/\//', '', $baseurl);
                $host = preg_replace('/\/.*/', '', $host);
                if (!isset($this->_ips[$host])):
                    $this->_ips[$host] = gethostbyname($host . '.');
                endif;
                if ($this->_ips[$host] != $host . '.'):
                    $url = $entry->getProtocol() . '://' . $this->_ips[$host] . '/' . ltrim($entry->getRequest(), '/');
                else:
                    $url = $entry->getProtocol() . '://127.0.0.1/' . ltrim($entry->getRequest(), '/');
                endif;
                
                $append = array();

                if ($arguments = $entry->getCategorymodifier()):
                    $arguments = explode('<br />', $arguments);
                    foreach ($arguments as $argument):
                        $argument = explode(': ', $argument);
                        switch ($argument[0]):
                            case 'Direction':
                                $append[] = 'dir=' . $argument[1];
                                break;
                            case 'Limit':
                                $append[] = 'limit=' . $argument[1];
                                break;
                            case 'Mode':
                                $append[] = 'mode=' . $argument[1];
                                break;
                            case 'Order':
                                $append[] = 'order=' . $argument[1];
                                break;
                            case 'Page':
                                $append[] = 'p=' . $argument[1];
                                break;
                        endswitch;
                    endforeach;
                endif;

                if ($arguments = $entry->getLayeredmodifier()):
                    $arguments = explode('<br />', $arguments);
                    foreach ($arguments as $argument):
                        $argument = explode(': ', $argument);
                        $append[] = $argument[0] . '=' . $argument[1];
                    endforeach;
                endif;

                if ($append):
                    $append = implode('&', $append);
                    $url = $url . '?' . $append;
                endif;
                
                $url = array($url);
                
                if ($entry->getTax() && $entry->getCurrency()):
                    $options = array(
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => array('warmrequest' => 'true', 'tax' => $entry->getTax(), 'currency' => $entry->getCurrency()),
                        CURLOPT_CONNECTTIMEOUT => 2,
                        CURLOPT_TIMEOUT => 5,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_COOKIE => 'currency=' . $entry->getCurrency() . ';evolved_tax=' . $entry->getTax() . ';store=' . $entry->getStorecode() . ';evolved_key=' . $entry->getCachekey(),
                        CURLOPT_HTTPHEADER => array('Host: ' . $host)
                    );
                    
                    if ($proxy = Mage::getStoreConfig('evolvedcaching/general/proxy')):
                        $proxy = trim($proxy, '.');
                        $proxy = trim($proxy);
                        if (!empty($proxy)):
                            $options[CURLOPT_PROXY] = $proxy;
                        endif;
                    endif;
                    
                    $agent = explode('<br />', $entry->getAgentmodifier());
                    $agent = reset($agent);
                    if ($agent && !is_array($agent)):
                        $options[CURLOPT_USERAGENT] = $agent;
                    else:
                        $options[CURLOPT_USERAGENT] = 'evolvedcaching_crawler';
                    endif;
                else:
                    $options = array(
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => array('warmrequest' => 'true'),
                        CURLOPT_CONNECTTIMEOUT => 2,
                        CURLOPT_TIMEOUT => 5,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_COOKIE => 'store=' . $entry->getStorecode() . ';evolved_key=' . $entry->getCachekey(),
                        CURLOPT_HTTPHEADER => array('Host: ' . $host),
                        CURLOPT_USERAGENT => 'evolvedcaching_crawler'
                    );
                    
                    if ($proxy = Mage::getStoreConfig('evolvedcaching/general/proxy')):
                        $proxy = trim($proxy, '.');
                        $proxy = trim($proxy);
                        if (!empty($proxy)):
                            $options[CURLOPT_PROXY] = $proxy;
                        endif;
                    endif;
                endif;
                
                return array('url' => $url, 'options' => $options);
            endif;
        endif;
        
        return false;
    }
    
    private function _execWarmRequest($warmrequest)
    {
        if ($warmrequest):
            $adapter = new Varien_Http_Adapter_Curl();
            ob_start();
            $adapter->multiRequest($warmrequest['url'], $warmrequest['options']);
            ob_end_clean();
        endif;
    }
    
    private function _reportStatus()
    {
        if (Mage::app()->useCache('evolved')):
            if (Mage::getStoreConfig('evolvedcaching/autowarm/enabled')):
                if ($this->_match):
                    if (Mage::getStoreConfig('evolvedcaching/autowarm/cron')):
                        Mage::getSingleton('adminhtml/session')->addSuccess('Full page cache warming requests have been successfully queued.');
                    else:
                        Mage::getSingleton('adminhtml/session')->addSuccess('Full page cache has been warmed.');
                    endif;
                else:
                    Mage::getSingleton('adminhtml/session')->addNotice('No full page cache entries to warm.');
                endif;
            endif;
        endif;
    }
    
    private function _warmByStrategy($entry, $skipvalidate = false)
    {
        if (Mage::app()->useCache('evolved')):
            if (!isset($this->_cronwarm)):
                $this->_cronwarm = Mage::getStoreConfig('evolvedcaching/autowarm/cron') ? true : false;
            endif;

            if ($this->_cronwarm):
                $this->_addWarmEntry($entry, $skipvalidate);
            else:
                $warmrequest = $this->_buildWarmRequest($entry, $skipvalidate);
                if ($warmrequest):
                    $this->_queue[] = $warmrequest;
                endif;
            endif;
        endif;
    }
    
    private function _addWarmEntry($entry, $skipvalidate = false)
    {
        $queue = Mage::getModel('evolvedcaching/warming');
        if ($warmrequest = $this->_buildWarmRequest($entry, $skipvalidate)):
            $warmrequest = Zend_Json::Encode($warmrequest);
            $queue->setRequest($warmrequest)->save();
        endif;
    }
    
    public function warmCache()
    {
        foreach (Mage::getResourceModel('evolvedcaching/warming_collection') as $warmer):
            $warmer->setLockModifiedTime();
            $warmrequest = $warmer->getRequest();
            $warmrequest = Zend_Json::decode($warmrequest);
            $this->_execWarmRequest($warmrequest);
            $warmer->delete();
        endforeach;
        
        $this->userCleanup();
    }
    
    public function processWarmQueue($cms = false, $report = true)
    {
        $this->_warmMissing($cms);
        
        foreach ($this->_queue as $warmrequest):
            $this->_execWarmRequest($warmrequest);
        endforeach;
        
        if ($report):
            $this->_reportStatus();
        endif;
    }
    
    private function _warmMissing($cms = false)
    {
        foreach ($this->_missing as $missing):
            if (!$cms):
                foreach ($this->_stores as $id => $urls):
                    if (in_array($missing, $urls)):
                        $store = Mage::getModel('core/store')->load((int) $id);
                        break;
                    endif;
                endforeach;
                
                if (!empty($store) && $store->getId() && !$this->_checkExcluded($missing, $store)):
                    $entry = Mage::getModel('evolvedcaching/entries');
                    $protocol = strpos($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false), 'https') !== false ? 'https' : 'http';

                    $entry
                        ->setStorecode($store->getCode())
                        ->setRequest('/' . $missing)
                        ->setProtocol($protocol);

                    $this->_warmByStrategy($entry);

                    if (!$this->_match):
                        $this->_match = true;
                    endif;
                endif;
            else:   
                foreach ($this->_getStores($cms->getStores()) as $store):
                    if (!empty($store) && $store->getId() && !$this->_checkExcluded($missing, $store)):
                        $protocol = strpos($store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false), 'https') !== false ? 'https' : 'http';
                        $entry = Mage::getModel('evolvedcaching/entries');

                        $entry
                            ->setStorecode($store->getCode())
                            ->setRequest('/' . $missing)
                            ->setProtocol($protocol);

                        $this->_warmByStrategy($entry);
                        
                        if (Mage::getStoreConfig('web/url/use_store')):
                            $entry = Mage::getModel('evolvedcaching/entries');
                            
                            $entry
                                ->setStorecode($store->getCode())
                                ->setRequest('/' . $store->getCode() . '/' . $missing)
                                ->setProtocol($protocol);

                            $this->_warmByStrategy($entry);
                        endif;

                        if (!$this->_match):
                            $this->_match = true;
                        endif;
                    endif;
                endforeach;
            endif;
        endforeach;
    }
    
    private function _checkExcluded($url, $store)
    {
        $rewrites = Mage::getStoreConfig('web/seo/use_rewrites', $store->getCode());
        if (strpos($url, '/view/id/') !== false && $rewrites) return true;
                
        $config = Mage::getStoreConfig('evolvedcaching/exclude/pages', $store->getCode());
        $config = explode(',', $config);
        
        foreach ($config as $exclude):
            $exclude = trim($exclude);
            if (strpos($url, trim($exclude, '/')) !== false):
                return true;
            endif;
        endforeach;
        
        return false;
    }
    
    public function getCachePageHtml($id)
    {
        if ($id):
            $entry = Mage::getModel('evolvedcaching/entries')->load((int) $id);
            if ($entry->getId()):
                $cachekey = 'page_' . $entry->getCachekey();
                switch ($entry->getStorage()):
                    case 'files':
                        $cachedir = @class_exists('evolved', false) ? EVOLVED_ROOT : MAGENTO_ROOT;
                        if ($cachedir):
                            $cachedir .= DS . 'var' . DS . 'evolved' . DS;
                            $file = $cachedir . $cachekey;
                            if (is_file($file)):
                                if ($html = file_get_contents($file)):
                                    if (@extension_loaded('zlib')):
                                        if ($deflated = @gzuncompress($html)):
                                            $html = $deflated;
                                        endif;
                                    endif;
                                    
                                    return $this->_stripCacheTags($html);
                                endif;
                            endif;
                        endif;
                        break;
                    case 'memcached':
                        if ($this->_mexists):
                            if ($memcached = $this->_getMemcachedServer()):
                                if ($html = $memcached->get($cachekey)):
                                    $this->_closeMemcached($memcached);
                                    
                                    return $this->_stripCacheTags($html);
                                endif;
                                $this->_closeMemcached($memcached);
                            endif;
                        endif;
                        break;
                    case 'apc':
                        if ($this->_aexists):
                            if ($html = apc_fetch($cachekey)):
                                if (@extension_loaded('zlib')):
                                    if ($deflated = @gzuncompress($html)):
                                        $html = $deflated;
                                    endif;
                                endif;
                                
                                return $this->_stripCacheTags($html);
                            endif;
                        endif;
                    case 'redis':
                        if ($this->_rexists):
                            if ($redis = $this->_getRedisServer()):
                                if ($html = $redis->get($cachekey)):
                                    $this->_closeRedis($redis);
                                    if (@extension_loaded('zlib')):
                                        if ($deflated = @gzuncompress($html)):
                                            $html = $deflated;
                                        endif;
                                    endif;
                                    
                                    return $this->_stripCacheTags($html);
                                endif;
                                $this->_closeRedis($redis);
                            endif;
                        endif;
                        break;
                endswitch;
            endif;
        endif;
        
        return '';
    }
    
    private function _stripCacheTags($html)
    {
        $html = unserialize($html);
        $html = preg_replace('/<!-- evolved_id-.*<!-- close -->/Us', '', $html);
        
        return $html;
    }
    
    public function userCleanup()
    {
        $cachedir = Mage::getBaseDir();
        if ($cachedir):
            $cachedir .= DS . 'var' . DS . 'evolved' . DS;

            if (is_dir($cachedir)):
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
            endif;
        endif;
    }
    
    private function _clearVarnishEntry($entry)
    {
        Mage::helper('evolvedcaching/varnish')->clearByEntry($entry);
    }
    
    protected function _clearStoreCache($store)
    {
        $collection = Mage::getResourceModel('evolvedcaching/entries_collection');
        $collection->getSelect()->where('storecode = ?', $store);
        
        foreach ($collection as $entry):
            $this->clearCacheEntry($entry->getId());
        endforeach;
    }
}