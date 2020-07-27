<?php
class HusseyCoding_EvolvedCaching_Model_Design_CorePackage extends Mage_Core_Model_Design_Package
{
    private $_count = 0;
    private $_handlestring;
    private $_shouldrun;
    private $_jsenabled;
    private $_cssenabled;
    private $_currentstore;
    
    public function getMergedJsUrl($files)
    {
        $url = parent::getMergedJsUrl($files);
        
        return !empty($url) ? $this->_getNewUrl($url) : $url;
    }
    
    public function getMergedCssUrl($files)
    {
        $url = parent::getMergedCssUrl($files);
        
        return !empty($url) ? $this->_getNewUrl($url) : $url;
    }
    
    protected function _mergeFiles(array $srcFiles, $targetFile = false,
        $mustMerge = false, $beforeMergeCallback = null, $extensionsFilter = array())
    {
        if ($this->_shouldRun()):
            $this->_count++;
            $targetinfo = pathinfo($targetFile);
            if ($this->_mergingEnabled($targetinfo['extension'])):
                $newname = md5($this->_getHandleString() . $this->_getCurrentStore() . $this->_count);
                $targetFile = $targetinfo['dirname'] . DS . $newname . '.' . $targetinfo['extension'];
                if ($this->_checkFilesModified($srcFiles, $targetFile, $mustMerge)):
                    $mustMerge = true;
                endif;
            endif;
        endif;
        
        return parent::_mergeFiles($srcFiles, $targetFile, $mustMerge, $beforeMergeCallback, $extensionsFilter);
    }
    
    private function _checkFilesModified($files, $targetfile, $mustmerge)
    {
        if (!file_exists($targetfile)):
            return false;
        endif;
        
        $newest = 0;
        foreach ($files as $file):
            if (file_exists($file)):
                if ($modified = filemtime($file)):
                    if ($modified > $newest):
                        $newest = $modified;
                    endif;
                endif;
            endif;
        endforeach;
        
        return $newest > filemtime($targetfile) ? true : $mustmerge;
    }
    
    private function _getHandleString()
    {
        if (!isset($this->_handlestring)):
            $handles = Mage::app()->getLayout()->getUpdate()->getHandles();
            $this->_handlestring = implode(',', $handles);
        endif;
        
        return $this->_handlestring;
    }
    
    private function _shouldRun()
    {
        if (!isset($this->_shouldrun)):
            $this->_shouldrun = Mage::getStoreConfig('evolvedcaching/general/merged');
        endif;
        
        return $this->_shouldrun;
    }
    
    private function _mergingEnabled($type)
    {
        if (!isset($this->_jsenabled)):
            $this->_jsenabled = Mage::getStoreConfigFlag('dev/js/merge_files');
        endif;
        
        if (!isset($this->_cssenabled)):
            $this->_cssenabled = Mage::getStoreConfigFlag('dev/css/merge_css_files');
        endif;
        
        if ($type == 'js'):
            return $this->_jsenabled;
        endif;
        
        return $this->_cssenabled;
    }
    
    private function _getCurrentStore()
    {
        if (!isset($this->_currentstore)):
            $this->_currentstore = Mage::app()->getStore()->getCode();
        endif;
        
        return $this->_currentstore;
    }
    
    private function _getNewUrl($url)
    {
        if ($this->_shouldRun()):
            $targeturl = parse_url($url);
            $targetinfo = pathinfo($targeturl['path']);
            $newname = md5($this->_getHandleString() . $this->_getCurrentStore() . $this->_count);

            return str_replace($targetinfo['filename'], $newname, $url);
        endif;
        
        return $url;
    }
}