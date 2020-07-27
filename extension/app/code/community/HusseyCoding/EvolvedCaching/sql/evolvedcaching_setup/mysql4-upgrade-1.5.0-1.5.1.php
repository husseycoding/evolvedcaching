<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` CHANGE `tax` `tax` DECIMAL(12,4) NOT NULL;
");

$installer->endSetup();