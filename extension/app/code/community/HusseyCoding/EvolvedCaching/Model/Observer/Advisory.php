<?php
class HusseyCoding_EvolvedCaching_Model_Observer_Advisory
{
    private $_run = true;
    
    public function adminhtmlAdminSystemConfigChangedSectionEvolvedcaching($observer)
    {
        $this->adminhtmlControllerActionPostdispatchAdminhtml($observer, true);
        $found = false;
        foreach (Mage::app()->getStores() as $store):
            if (Mage::getStoreConfig('evolvedcaching/exclude/blocks', $store->getCode())):
                $found = true;
                break;
            endif;
        endforeach;
        
        if (!$found):
            Mage::getSingleton('adminhtml/session')->addError('You must exclude at least one block before caching will function.');
        endif;
        
        $host = Mage::app()->getRequest()->getHttpHost();
        $ip = gethostbyname($host . '.');
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, false);
        $show = false;
        if ($ip == $host . '.'):
            $show = true;
        else:
            $options = array(
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_USERAGENT => 'availability',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_VERBOSE => true,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => array('Host: ' . $host),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            );
            
            if ($proxy = Mage::getStoreConfig('evolvedcaching/general/proxy')):
                $proxy = trim($proxy, '.');
                $proxy = trim($proxy);
                if (!empty($proxy)):
                    $options[CURLOPT_PROXY] = $proxy;
                endif;
            endif;
            
            $curl = curl_init($url);
            curl_setopt_array($curl, $options);
            $response = curl_exec($curl);
            if (!curl_errno($curl)):
                $size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
                curl_close($curl);
                $header = substr($response, 0, $size);
                if (strpos($header, '401 Restricted')):
                    $show = true;
                endif;
            endif;
                                
        endif;
        
        if ($show):
            Mage::getSingleton('adminhtml/session')->addError('It looks like the store is not accessible externally - automatic cache clearing and warming, Varnish integration and the cache crawler may all fail to function correctly.');
        endif;
    }
}