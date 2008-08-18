<?php

class Security_Form_Login extends Security_Form_Rest
{
    public function init()
    {
        parent::init();

        $this->addElement('hidden', 'return_url', array(
            'value' => '/'));
        
        $this->addElement('text', 'identity', array(
            'filters' => array(
                'StringTrim'),
            'required' => true,
            'label' => Security_System::getInstance()->getOption('identityColumnTitle') .':'));
        
        $this->addElement('password', 'credential', array(
            'filters' => array(
                'StringTrim'),
            'required' => true,
            'label' => Security_System::getInstance()->getOption('credentialColumnTitle') .':'));
        
        $this->addElement('submit', 'submit', array('label' => 'Submit'));
    }
}