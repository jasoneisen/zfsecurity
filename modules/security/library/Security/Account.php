<?php

class Security_Account
{
    protected static $_instance = null;

    protected $_activeRecord = null;

    private function __clone()
    {}

    private function __construct()
    {
        if (($auth = Zend_Auth::getInstance()->getIdentity()) && isset($auth->{$this->getIdentityColumn()})) {
            
            $query = Doctrine_Query::create()
                                     ->from($this->getTableName())
                                     ->leftJoin($this->getTableName().'.Groups g')
                                     ->addWhere($this->getTableName() .'.'. $this->getIdentityColumn() .'= ?');
            
            if ($record = $query->fetchOne(array($auth->{$this->getIdentityColumn()}))) {
                
                $this->_activeRecord = $record;
            }
        }
        
        if (null === $this->_activeRecord) {
            
            $name = Security_System::getInstance()->getOption('accountTableName');
            $this->_activeRecord = new $name();
        }
    }
    
    public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
    
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

    public function getTableName()
    {
        return $this->getTable()->getTableName();
    }
    
    public function getIdentityColumn()
    {
        return $this->getTable()->getIdentifier();
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
            return call_user_func_array(array($this->_activeRecord, $method), $args);
        }
        throw new Security_Exception(sprintf('Invalid method "%s" called in %s', $method, get_class($this->_activeRecord)));
    }
}