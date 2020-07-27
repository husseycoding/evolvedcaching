<?php
class HusseyCoding_EvolvedCaching_Model_Warming extends Mage_Core_Model_Abstract
{
    private $_basedir;
    private $_datehelper;
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('evolvedcaching/warming');
    }
    
    public function warmCache()
    {
        if ($this->_shouldProcess()):
            $this->_lockCron();
        
            Mage::helper('evolvedcaching/entries')->warmCache();
            
            $this->_unlockCron();
        endif;
    }
    
    private function _shouldProcess()
    {
        if (file_exists($this->_getLockFilePath()) && !$this->_processFailed()):
            Mage::log('Evolved Caching cron - lock file ' . $this->_getLockFilePath() . ' found', 3);
            Mage::log('Evolved Caching cron - another cron process is still running, aborting', 3);
        
            return false;
        endif;
        
        return true;
    }
    
    private function _lockCron()
    {
        if (!file_exists($this->_getLockFilePath())):
            if (!fopen($this->_getLockFilePath(), 'w+')):
                Mage::log('Evolved Caching cron - could not lock cron process, duplicate processes will be able to run', 3);
                Mage::log('Evolved Caching cron - check file ' . $this->_getLockFilePath() . ' can be created', 3);
            else:
                $this->_setLockModifiedTime();
                Mage::log('Evolved Caching cron - locked cron process', 7);
            endif;
        endif;
    }
    
    private function _unlockCron()
    {
        if (file_exists($this->_getLockFilePath())):
            if (!unlink($this->_getLockFilePath())):
                Mage::log('Evolved Caching cron - could not unlock cron process, no more cron processes will run', 3);
                Mage::log('Evolved Caching cron - check file ' . $this->_getLockFilePath() . ' can be deleted', 3);
            else:
                Mage::log('Evolved Caching cron - unlocked cron process', 7);
            endif;
        endif;
    }
    
    private function _getBaseDir()
    {
        if (!isset($this->_basedir)):
            $this->_basedir = Mage::getBaseDir('var');
        endif;
        
        return $this->_basedir;
    }
    
    private function _getLockFilePath()
    {
        return $this->_getBaseDir() . DS . '.evolvedlock';
    }
    
    private function _processFailed()
    {
        $current = filemtime($this->_getLockFilePath());
        $missed = $this->_getFailedTimestamp();

        if ($current <= $missed):
            Mage::log('Evolved Caching cron - the cron process appears to have failed, removing lock', 5);
            $this->_unlockCron();

            return true;
        endif;
        
        return false;
    }
    
    public function setLockModifiedTime()
    {
        $this->_setLockModifiedTime();
    }
    
    private function _setLockModifiedTime()
    {
        if (file_exists($this->_getLockFilePath())):
            $timestamp = $this->_getDateHelper()->gmtTimestamp();
            touch($this->_getLockFilePath(), $timestamp);
        endif;
    }
    
    private function _getFailedTimestamp()
    {
        $compare = $this->_getDateHelper()->gmtTimestamp();
        
        return $compare - 600;
    }
    
    private function _getDateHelper()
    {
        if (!isset($this->_datehelper)):
            $this->_datehelper = Mage::getModel('core/date');
        endif;
        
        return $this->_datehelper;
    }
}