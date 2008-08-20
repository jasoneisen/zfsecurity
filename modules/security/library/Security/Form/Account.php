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
            'label' => Security_System::getInstance()->getOption('identityColumnTitle') .':'));
        
        $accountForm->addElement('password', 'credential', array(
            'filters' => array(
                'StringTrim'),
            'required' => false,
            'label' => Security_System::getInstance()->getOption('credentialColumnTitle') .':'));
        
        $accountForm->addElement('password', 'credential_confirm', array(
            'filters' => array(
                'StringTrim'),
            'required' => false,
            'label' => 'Confirm '. Security_System::getInstance()->getOption('credentialColumnTitle') .':'));
        
        $this->addSubForm($accountForm, 'account');
        
        if ($groups = Security_Acl::getInstance()->getGroups()) {
            
            $groupForm = new Zend_Form_SubForm(array('legend' => 'Groups'));
            
            foreach ($groups as $groupName => $group) {
                
                $groupForm->addElement('checkbox', $group->id, array('label' => $groupName));
            }
            
            $this->addSubForm($groupForm, 'groups');
        }
        
        $this->addElement('submit', 'submit', array('label' => 'Submit'));
    }
}