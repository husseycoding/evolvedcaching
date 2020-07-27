<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_crawler')}` ADD `area` TINYTEXT NOT NULL;
");

$installer->endSetup();