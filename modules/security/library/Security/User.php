<?php

class Security_User
{
    protected static $_instance = null;

    protected $_records = array();

    private function __clone()
    {}

    private function __construct()
    {
        if (($auth = Zend_Auth::getInstance()->getIdentity()) && isset($auth->{$this->getIdentityColumn()})) {
            
            if ($record = Doctrine::getTable($this->getTableName())->find($auth->{$this->getIdentityColumn()})) {}
            
            if ($record !== false) {
                
                foreach ($record as $key => $value) {
                
                    $this->_setVar($key, $value);
                }
                
                // @TODO remove this, we need multiple groups
                
                $this->_setRecord($this->getTableName(), $record);
            }
        } else {
            
            // @TODO set to anonymous
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
        if (Zend_Auth::getInstance()->hasIdentity()) {
            return true;
        }
        //$role = Security_System::getInstance()->getRole();
        
        //if ($role === null || !isset($role->name) || strtolower($role->name) == 'anonymous') {
        //    return false;
        //}
        //return true;
        return false;
    }
    
    /**
     * Undocumented function.
     *
     * @todo document me
     * @return unknown
     * @author jaeisenmenger@edrivemedia.com
     **/
    public function getVar($varName)
    {
        if (isset($this->_vars[$varName]))
        {
            return $this->_vars[$varName];
        }
        return null;
    }
    
    /**
     * Undocumented function.
     *
     * @todo document me
     * @return unknown
     * @author jaeisenmenger@edrivemedia.com
     **/
    public function getRecord($rowName)
    {
        if (isset($this->_records[$rowName])) {
            return $this->_records[$rowName];
        }
        return null;
    }
    
    /**
     * Undocumented function.
     *
     * @todo document me
     * @return unknown
     * @author jaeisenmenger@edrivemedia.com
     **/
    protected function _setVar($varName, $varValue)
    {
        $this->_vars[$varName] = $varValue;
    }
    
    protected function _setRecord($recordName, $record)
    {
        $this->_records[$recordName] = $record;
    }
    
    public function getTableName()
    {
        return Security_System::getInstance()->getOption('accountTable');
    }
    
    public function getIdentityColumn()
    {
        return Doctrine::getTable($this->getTableName())->getIdentifier();
    }
    
    //final public function __set()
    //{
    //    
    //}
    //
    //final public function __get()
    //{
    //    
    //}
    //
    //final public function __isset()
    //{
    //    
    //}
    //
    //final public function __unset()
    //{
    //    
    //}
}