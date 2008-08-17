<?php

class Security_Form_User extends Zend_Form
{
    public function init()
    {
        $this->addElement('hidden', '_method');
        
        if ($groups = Security_Acl::getInstance()->getGroups()) {
            
            $this->addElement('multiCheckbox', 'groups', array('label' => 'Groups:'));
            
            foreach ($groups as $groupName => $group) {
                
                $this->getElement('groups')->addMultiOption(current($group->identifier()), $groupName);
            }
            
            $this->addDisplayGroup(array('groups'), 'groupsGroup', array('legend'=>'Groups'));
        }
        
        $this->addElement('submit', 'submit', array('label' => 'Submit'));
    }
}