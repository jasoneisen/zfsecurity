<?php

class Security_Migration extends Doctrine_Migration
{
    public function __construct($directory = null)
    {
        $this->setTableName('security_migration');
        
        parent::__construct($directory);
    }
}