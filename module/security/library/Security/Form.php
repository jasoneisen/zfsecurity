<?php

class Security_Form extends Zend_Form
{
    public function __construct($options = array())
    {
        parent::__construct($options);
        
        //$this->addPrefixPath('Security_Form', 'Security/Form');
        $this->addElementPrefixPath('Security_Validate', 'Security/Validate', 'validate');
        $this->setAttrib('accept-charset', 'utf-8');
    }
}