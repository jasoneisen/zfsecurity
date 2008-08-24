<?php

class Security_Install
{
    protected $_errors = array();
    
    public function getErrors()
    {
        return $this->_errors;
    }
    
    public function hasErrors()
    {
        return !empty($this->_errors);
    }
    
    protected function _addError($msg)
    {
        $this->_errors[] = $msg;
    }
    
    public function bootstrapIsSetup()
    {
        try {
            $secSys = Security_System::getInstance();

        } catch (Security_Exception $e) {

            $this->_addError($e->getMessage());
            return false;
        }
        return true;
    }
    
    public function hasRequiredDbAccess($migrationPath)
    {
        if (!Zend_Loader::isReadable($migrationPath)) {
            
            $this->_addError("Migrations path '$migrationPath' is not readable");
            return false;
        }
        
        $migration = new Security_Migration($migrationPath);
        
        if ((false === ($version = $migration->getCurrentVersion())) || $version == 0) {
            
            try {
                $migration->migrate(1);
                
            } catch (Exception $e) {
                
                $this->_addError("No CREATE or INSERT access");
                return false;
            }
        }
        
        if ($migration->getCurrentVersion() == 1) {
            
            try {
                $migration->migrate(2);
                
            } catch (Exception $e) {
                
                $this->_addError("No UPDATE access");
                return false;
            }
        }
        
        if ($migration->getCurrentVersion() == 2) {
            
            try {
                $migration->migrate(3);
                
            } catch (Exception $e) {
                
                $this->_addError("No ALTER access");
                return false;
            }
        }
        
        if ($migration->getCurrentVersion() >= 3) {
            
            return true;
        }
    }
    
    public function generateModels($accountTable, $alias, $modelPath, $schemaPath)
    {
        try {
            $table = Doctrine::getTable($accountTable);
            
        } catch (Exception $e) {
            
            $this->_addError("Invalid table specified");
        }
        
        if (!is_writable($modelPath)) {
            
            $this->_addError("Models path '$modelPath' is not writable");
        }
        
        if (!Zend_Loader::isReadable($schemaPath)) {
            
            $this->_addError("Schema path '$schemaPath' is not readable");
        }
        
        if ($this->hasErrors()) {
            
            return false;
        }
        
        try {
            
            $column = $table->getIdentifier();
            
            if (is_array($column)) {
                
                $this->_addError("Account table cannot have a compound primary key");
                return false;
            }
            
            $definition = $table->getColumnDefinition($column);
            
            $localColumn = ($column == 'id') ? strtolower($accountTable) . '_id' : $column;
            
            $import = new Doctrine_Import_Schema();
            $definitions = $import->buildSchema($schemaPath, 'yml');
            
            $definitions['SecurityGroupAccount']['columns'][$column] = $definition;
            $definitions['SecurityGroupAccount']['columns'][$column]['autoincrement'] = false;
            $definitions['SecurityGroupAccount']['columns'][$column]['name'] = $localColumn;
            
            $definitions['SecurityGroupAccount']['relations'][$accountTable] = array(
                'class' => $accountTable,
                'local' => $localColumn,
                'foreign' => $column,
                'type' => Doctrine_Relation::ONE,
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE',
                'alias' => $accountTable,
                'key' => md5($localColumn.$column.$accountTable));
            
            $definitions['SecurityGroup']['relations'][$alias] = array(
                'class' => $accountTable,
                'local' => 'id',
                'foreign' => $localColumn,
                'type' => Doctrine_Relation::MANY_COMPOSITE,
                'alias' => $alias,
                'refClass' => 'SecurityGroupAccount',
                'key' => md5('id'.$localColumn.$accountTable.'SecurityGroupAccount'));
            
            $builder = new Doctrine_Import_Builder();
            $builder->setTargetPath($modelPath);
            
            foreach ($definitions as $definition) {
                
                $builder->buildRecord($definition);
            }
                
            return true;

        } catch (Exception $e) {
            
            $this->_addError($e->getMessage());
            return false;
        }
    }
    
    public function hasGroupsRelation($table)
    {
        try{
           $relations = Doctrine::getTable($table)->getRelations();
           
           if (isset($relations['Groups'])) {
               return true;
           }
           
           $this->_addError("No 'Groups' relation found on table class '$table'");
           
       } catch (Exception $e) {
           
           $this->_addError($e->getMessage());
       }
       return false;
    }
    
    public function executeSqlFromModels($accountTable, $migrationPath)
    {
        try {
            $export = new Doctrine_Export();
            $sql = $export->exportSortedClassesSql(array('SecurityAcl',
                                                         'SecurityAclPart',
                                                         'SecurityGroup',
                                                         'SecurityGroupAccount',
                                                         'SecurityGroupAcl',
                                                         'SecurityOption'));
            
            $conn = Doctrine_Manager::connection();
            
            foreach ($sql[0] as $stmnt) {
                
                $conn->exec($stmnt);
            }
            
            return true;

        } catch (Exception $e) {
            
            $this->_addError($e->getMessage());
        }
        return false;
    }
}