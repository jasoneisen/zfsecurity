<?php

/**
 * undocumented class
 *
 * @todo Add current process user/group checking to insert into error
 **/
class Security_Validate_Setting_ModelPath extends Zend_Validate_Abstract
{
    const STRING_EMPTY = 'stringEmpty';
    
    const NOT_READABLE = 'notReadable';
    
    const NOT_WRITABLE = 'notWritable';

    protected $_messageTemplates = array(
        self::STRING_EMPTY => "'%value%' is an empty string",
        self::NOT_READABLE => "'%value%' is not a readable directory",
        self::NOT_WRITABLE => "'%value%' is not a writable directory"
    );
    
    public function isValid($value)
    {
        $modelPath = (string) $value;
        $this->_setValue($modelPath);

        if ('' === $modelPath) {
            $this->_error(self::STRING_EMPTY);
            return false;
        }
        
        // Add a trailing slash
        if (substr($modelPath, -1, 1) != DIRECTORY_SEPARATOR) {
            $modelPath .= DIRECTORY_SEPARATOR;
        }
        
        if (!is_readable($modelPath)) {
            $this->_error(self::NOT_READABLE);
        }
        
        if (!is_writable($modelPath)) {
            $this->_error(self::NOT_WRITABLE);
        }
        
        if (!empty($this->_errors)) {
            return false;
        }

        return true;
    }

}
