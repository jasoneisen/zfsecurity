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
            'label' => Security_System::getInstance()->getParam('loginIdentityLabel') .':'));
        
        $this->addElement('password', 'credential', array(
            'filters' => array(
                'StringTrim'),
            'required' => true,
            'label' => Security_System::getInstance()->getParam('loginCredentialLabel') .':'));
        
        $this->addElement('submit', 'submit', array('label' => 'Login'));
    }
}