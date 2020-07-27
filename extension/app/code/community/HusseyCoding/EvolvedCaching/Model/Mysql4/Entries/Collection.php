<?php
class HusseyCoding_EvolvedCaching_Model_Mysql4_Entries_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    private $_shouldValidate = false;
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('evolvedcaching/entries');
    }
    
    public function setShouldValidate()
    {
        $this->_shouldValidate = true;
        
        return $this;
    }
    
    public function getShouldValidate()
    {
        return $this->_shouldValidate;
    }
    
    public function addTimestampFilterCallback($collection, $column)
    {
        if ($filter = $column->getFilter()->getCondition()):
            $now = Mage::getModel('core/date')->timestamp();
            if (!empty($filter['orig_from'])):
                $from = str_replace('/', '-', $filter['orig_from']);
                $from = strtotime($from, $now);
                $from = date('Y-m-d H:i:s', $from);
                
                $collection->getSelect()->where('accessed >= (?)', $from);
            endif;
            if (!empty($filter['orig_to'])):
                $to = str_replace('/', '-', $filter['orig_to']);
                $to = strtotime($to, $now);
                $to += 86399;
                $to = date('Y-m-d H:i:s', $to);

                $collection->getSelect()->where('accessed <= (?)', $to);
            endif;
        endif;
    }
}