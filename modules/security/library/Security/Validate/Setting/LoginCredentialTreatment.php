<?php

class Security_Validate_Setting_LoginCredentialTreatment extends Zend_Validate_Abstract
{
    const CONTAINS_SPACES = 'containsSpaces';
    
    const NO_MARKER = 'noMarker';

    protected $_messageTemplates = array(
        self::CONTAINS_SPACES => "Treatment must not contain spaces",
        self::NO_MARKER => "Treatment must contain a marker '?' for placing input"
    );
    
    public function isValid($value)
    {
        $valueString = (string) $value;
        $this->_setValue($valueString);

	    $status = @preg_match('/[ ]/', $valueString);
        if (false === $status) {
            /**
             * @see Zend_Validate_Exception
             */
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("Internal error matching pattern '/[ ]/' against value '$valueString'");
        }
        if ($status > 0) {
            $this->_error(self::CONTAINS_SPACES);
            return false;
        }
        
        $status = @preg_match('/[?]/', $valueString);
        if (false === $status) {
            /**
             * @see Zend_Validate_Exception
             */
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("Internal error matching pattern '/[?]/' against value '$valueString'");
        }
        if (!$status) {
            $this->_error(self::NO_MARKER);
            return false;
        }
        
        return true;
    }

}
