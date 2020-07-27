<?php
class HusseyCoding_EvolvedCaching_Block_Adminhtml_Evolvedcaching_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('evolvedcaching_entries');
        $this->setDefaultSort('accessed');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('evolvedcaching/entries_collection')->setShouldValidate();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('accessed', array(
            'header' => Mage::helper('evolvedcaching')->__('Timestamp'),
            'index' => 'accessed',
            'type' => 'datetime',
            'renderer' => 'evolvedcaching/adminhtml_evolvedcaching_grid_column_renderer_timestamp',
            'filter_condition_callback' => array(Mage::getResourceModel('evolvedcaching/entries_collection'), 'addTimestampFilterCallback')
        ));
        
        $this->addColumn('request', array(
            'header' => Mage::helper('evolvedcaching')->__('URL'),
            'type' => 'text',
            'index' => 'request'
        ));
        
        $this->addColumn('storecode', array(
            'header' => Mage::helper('evolvedcaching')->__('Store'),
            'index' => 'storecode',
            'type' => 'options',
            'options' => Mage::helper('evolvedcaching')->getStoreOptions(),
            'width' => '70px'
        ));
        
        $this->addColumn('protocol', array(
            'header' => Mage::helper('evolvedcaching')->__('Protocol'),
            'type' => 'options',
            'index' => 'protocol',
            'options' => Mage::helper('evolvedcaching')->getProtocolOptions(),
            'width' => '70px'
        ));
        
        $this->addColumn('agentmodifier', array(
            'header' => Mage::helper('evolvedcaching')->__('User Agent'),
            'type' => 'text',
            'index' => 'agentmodifier',
            'width' => '150px'
        ));
        
        $this->addColumn('currency', array(
            'header' => Mage::helper('evolvedcaching')->__('Currency'),
            'type' => 'options',
            'index' => 'currency',
            'options' => Mage::helper('evolvedcaching')->getCurrencyOptions(),
            'width' => '70px'
        ));
        
        $this->addColumn('categorymodifier', array(
            'header' => Mage::helper('evolvedcaching')->__('Category Sorting'),
            'type' => 'text',
            'index' => 'categorymodifier',
            'width' => '140px'
        ));
        
        $this->addColumn('layeredmodifier', array(
            'header' => Mage::helper('evolvedcaching')->__('Layered Sorting'),
            'type' => 'text',
            'index' => 'layeredmodifier',
            'width' => '140px'
        ));
        
        $this->addColumn('tax', array(
            'header' => Mage::helper('evolvedcaching')->__('Tax'),
            'type' => 'number',
            'index' => 'tax',
            'width' => '50px'
        ));
        
        $this->addColumn('storage', array(
            'header' => Mage::helper('evolvedcaching')->__('Storage Type'),
            'index' => 'storage',
            'type' => 'options',
            'options' => Mage::helper('evolvedcaching')->getStorageOptions(),
            'width' => '90px'
        ));
        
        $this->addColumn('expired', array(
            'header' => Mage::helper('evolvedcaching')->__('Expired'),
            'index' => 'expired',
            'type' => 'options',
            'options' => Mage::helper('evolvedcaching')->getExpiredOptions(),
            'width' => '70px',
            'filter' => false,
            'sortable' => false,
            'column_css_class' => 'expired'
        ));
        
        $this->addColumn('action_view', array(
            'width' => '50px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('evolvedcaching')->__('View'),
                    'onclick' => 'window.thisviewcache.view(this)',
                    'style' => 'cursor:pointer'
                )
            ),
            'filter' => false,
            'sortable' => false
        ));
        
        $this->addColumn('action_delete', array(
            'width' => '50px',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('evolvedcaching')->__('Delete'),
                    'field' => 'id',
                    'url' => array(
                        'base' => '*/*/delete'
                    )
                )
            ),
            'filter' => false,
            'sortable' => false
        ));
        
        return parent::_prepareColumns();
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('evolvedcaching_mass');
        $this->getMassactionBlock()->setFormFieldName('evolvedcaching');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('evolvedcaching')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('evolvedcaching')->__('Are you sure?')
        ));
        
        return $this;
    }
    
    public function getRowUrl($row)
    {
        return;
    }
}