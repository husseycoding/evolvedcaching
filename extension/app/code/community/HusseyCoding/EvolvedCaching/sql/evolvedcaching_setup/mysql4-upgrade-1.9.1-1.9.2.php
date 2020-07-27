<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_crawler')}` ADD `currency` TINYTEXT NOT NULL;
");

$installer->endSetup();