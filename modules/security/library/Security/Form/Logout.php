<?php

class Security_Form_Logout extends Security_Form_Rest
{
    public function init()
    {
        parent::init();
        
        $this->addElement('submit', 'submit', array('label' => 'Logout'));
    }
}