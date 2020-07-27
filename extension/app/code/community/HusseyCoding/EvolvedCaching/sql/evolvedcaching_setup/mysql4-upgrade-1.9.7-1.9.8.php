<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY storecode VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY protocol VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY agentmodifier VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY currency VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY categorymodifier VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY layeredmodifier VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY request VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY cachekey VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_caching')}` MODIFY storage VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_crawler')}` MODIFY url VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_crawler')}` MODIFY store VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_crawler')}` MODIFY area VARCHAR(255);
    ALTER TABLE `{$this->getTable('evolvedcaching/evolved_crawler')}` MODIFY currency VARCHAR(255);
    CREATE INDEX cachekey ON {$this->getTable('evolvedcaching/evolved_caching')} (cachekey);
    CREATE INDEX storage ON {$this->getTable('evolvedcaching/evolved_caching')} (storage);
    CREATE INDEX storecode ON {$this->getTable('evolvedcaching/evolved_caching')} (storecode);
    CREATE INDEX request ON {$this->getTable('evolvedcaching/evolved_caching')} (request);
");

$installer->endSetup();