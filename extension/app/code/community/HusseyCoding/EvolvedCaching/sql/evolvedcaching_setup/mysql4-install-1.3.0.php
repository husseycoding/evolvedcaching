<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    CREATE TABLE {$this->getTable('evolvedcaching/evolved_caching')} (
        `id` int unsigned NOT NULL auto_increment,
        `storecode` tinytext NOT NULL,
        `protocol` tinytext NOT NULL,
        `agentmodifier` text NOT NULL,
        `currency` tinytext NOT NULL,
        `categorymodifier` text NOT NULL,
        `layeredmodifier` text NOT NULL,
        `tax` tinyint NOT NULL,
        `request` text NOT NULL,
        `accessed` datetime NOT NULL,
        `cachekey` tinytext NOT NULL,
        `storage` tinytext NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();