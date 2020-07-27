<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    CREATE TABLE {$this->getTable('evolvedcaching/evolved_warming')} (
        `id` int unsigned NOT NULL auto_increment,
        `request` text NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();