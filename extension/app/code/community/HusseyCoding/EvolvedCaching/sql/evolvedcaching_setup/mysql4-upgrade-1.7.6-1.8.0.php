<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    CREATE TABLE {$this->getTable('evolvedcaching/evolved_crawler')} (
        `id` int unsigned NOT NULL auto_increment,
        `url` text NOT NULL,
        `store` tinytext NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();