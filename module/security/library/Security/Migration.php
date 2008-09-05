<?php

class Security_Migration extends Doctrine_Migration
{
    protected $_migrationTableName = 'security_migration';
    
    protected $_accountTable = null;
    
    /**
     * getCurrentVersion
     *
     * Get the current version of the database
     *
     * @return int|false on Doctrine_Connection_Exception
     */
    public function getCurrentVersion()
    {
        $conn = Doctrine_Manager::connection();
        
        try {
            
            $result = $conn->fetchColumn("SELECT version FROM " . $this->_migrationTableName);
            
        } catch (Doctrine_Connection_Exception $e) {
            
            return false;
        }
        
        return isset($result[0]) ? $result[0]:0;
    }
}