<?php

class Security_Validate_Setting_ColumnExists extends Zend_Validate_Abstract
{
    const FAILED_RETRIEVE_CLASS = 'failedRetrieveClass';
    
    const FAILED_GET_TABLE = 'failedGetTable';
    
    const COLUMN_NOT_FOUND = 'columnNotFound';
    
    protected $_field;

    protected $_messageTemplates = array(
        self::FAILED_RETRIEVE_CLASS => "Could not retrieve table class to validate column",
        self::FAILED_GET_TABLE => "Trying to call Doctrine::getTable('%value%'); throws an exception",
        self::COLUMN_NOT_FOUND => "Column '%value%' does not exist on specified accounts class"
    );
    
    public function __construct($field = null)
	{
		$this->_field = $field;
	}
    
    public function isValid($value, $context = null)
    {
        $column = (string) $value;
        $this->_setValue($column);

		if (is_array($context) && !empty($context[$this->_field])) {
		    
		    $class = $context[$this->_field];
	        
		} elseif (is_string($context)) {
		    
			$class = $context;
		}
		
		if (!isset($class)) {
		    $this->_error(self::FAILED_RETRIEVE_CLASS);
		    return false;
		}
        
        try {
            $table = Doctrine::getTable($class);
        } catch (Exception $e) {
            $this->_error(self::FAILED_GET_TABLE);
            return false;
        }
        
        if (!$table->hasColumn($column)) {
		    $this->_error(self::COLUMN_NOT_FOUND);
		    return false;
	    }
	    
	    return true;
    }
}
