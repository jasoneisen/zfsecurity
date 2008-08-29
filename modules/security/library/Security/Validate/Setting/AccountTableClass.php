<?php

class Security_Validate_Setting_AccountTableClass extends Zend_Validate_Abstract
{
    const FAILED_GET_TABLE = 'failedGetTable';
    
    const ARRAY_IDENTIFIER = 'arrayIdentifier';

    protected $_messageTemplates = array(
        self::FAILED_GET_TABLE => "Trying to call Doctrine::getTable('%value%'); throws an exception",
        self::ARRAY_IDENTIFIER => "Table cannot have a composite primary key"
    );
    
    public function isValid($value)
    {
        $class = (string) $value;
        $this->_setValue($class);
        
        try {
            $table = Doctrine::getTable($class);
        } catch (Exception $e) {
            $this->_error(self::FAILED_GET_TABLE);
            return false;
        }
        
        $identifier = $table->getIdentifier();
        
        if (is_array($identifier)) {
            $this->_error(self::ARRAY_IDENTIFIER);
            return false;
        }

        return true;
    }

}
