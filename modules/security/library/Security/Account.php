<?php

abstract class Security_Account
{
    protected static $_instance = null;

    protected $_activeRecord = null;

    private function __clone()
    {}

    protected function __construct()
    {
        $tableName = Security_System::getInstance()->getOption('accountTableName');
        $columnName = Doctrine::getTable($tableName)->getIdentifier();
        
        if (($auth = Zend_Auth::getInstance()->getIdentity()) && isset($auth->{$columnName})) {
            
            $query = Doctrine_Query::create()
                                     ->from($tableName)
                                     ->leftJoin($tableName.'.Groups g')
                                     ->addWhere($tableName .'.'. $columnName .'= ?');
            
            if ($record = $query->fetchOne(array($auth->{$columnName}))) {
                
                $this->_activeRecord = $record;
            }
        }
        
        if (null === $this->_activeRecord || $this->_activeRecord instanceof Doctrine_Null) {
            
            $name = Security_System::getInstance()->getOption('accountTableName');
            $this->_activeRecord = new $name();
        }
    }
    
    abstract public static function getInstance();
    
    public function isLoggedIn()
    {
        if (Zend_Auth::getInstance()->hasIdentity() && null !== $this->_activeRecord) {
            return true;
        }
        return false;
    }

    public function getActiveRecord()
    {
        return $this->_activeRecord;
    }
    
    public function __get($name)
    {
        return $this->getActiveRecord()->{$name};
    }
    
    public function __set($name, $value)
    {
        return $this->getActiveRecord()->{$name} = $value;
    }
    
    public function __isset($name)
    {
        return isset($this->getActiveRecord()->{$name});
    }
    
    public function __unset($name)
    {
        unset($this->getActiveRecord()->{$name});
    }
    
    public function __call($method, $args)
    {
        if (method_exists($this->getActiveRecord(), $method)) {
            return call_user_func_array(array($this->getActiveRecord(), $method), $args);
        }
        throw new Security_Exception(sprintf('Invalid method "%s" called in %s', $method, get_class($this->_activeRecord)));
    }
}