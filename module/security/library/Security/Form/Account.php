<?php

class Security_Form_Account extends Security_Form_Rest
{
    public function init()
    {
        parent::init();
        
        $accountForm = new Zend_Form_SubForm(array('legend' => 'Account'));
        
        $accountForm->addElement('text', 'identity', array(
            'filters' => array(
                'StringTrim'),
            'required' => true,
            'label' => Security::getParam('loginIdentityLabel') .':'));
        
        $accountForm->addElement('password', 'credential', array(
            'filters' => array(
                'StringTrim'),
            'required' => false,
            'label' => Security::getParam('loginCredentialLabel') .':'));
        
        $accountForm->addElement('password', 'credential_confirm', array(
            'filters' => array(
                'StringTrim'),
            'required' => false,
            'label' => 'Confirm '. Security::getParam('loginCredentialLabel') .':'));
        
        $this->addSubForm($accountForm, 'account');
        
        if ($groups = Doctrine::getTable('SecurityGroup')->findAll()) {
            
            $groupForm = new Zend_Form_SubForm(array('legend' => 'Groups'));
            
            foreach ($groups as $group) {
                
                $groupForm->addElement('checkbox', $group->id, array('label' => $group->name));
            }
            
            $this->addSubForm($groupForm, 'groups');
        }
        
        $this->addElement('submit', 'submit', array('label' => 'Submit'));
    }
}