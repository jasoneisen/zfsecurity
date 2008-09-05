<?php
/**
 * Shamelessly stolen from http://www.dasprids.de
 * @see Zend_Validate_Abstract
 */
require_once 'Zend/Validate/Abstract.php';


/**
 * Pseudo validator for custom messages
 */
class Security_Validate_CustomMessages extends Zend_Validate_Abstract
{
    /**
     * Create a custom error message
     *
     * @param string $message
     */
    public function __construct($message)
    {
        $this->_errors[] = $message;
        $this->_messages[] = $message;
    }

    /**
     * Returns always true
     *
     * @param string $value
     * @param mixed $context
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        return false;
    }
}