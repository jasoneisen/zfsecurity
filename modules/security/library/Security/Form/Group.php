<?php

class Security_Form_Group extends Zend_Form
{
    public function init()
    {
        $this->addElement('hidden', '_method');
        
        $groupForm = new Zend_Form_Subform(array('legend' => 'Group'));

        $groupForm->addElement('text', 'name', array(
            'validators'    =>  array(
                'alnum'),
            'required'  =>  true,
            'label' =>  'Name'));
        
        $groupForm->addElement('textarea', 'description', array(
            'validators'    =>  array(),
            'required'  =>  false,
            'label' =>  'Description'));
        
        $this->addSubForm($groupForm, 'group');
        
        $privilegesForm = new Zend_Form_Subform(array('legend' => 'Privileges'));
					
        if ($acls = Security_Acl::getInstance()->getAcl()) {
            
            foreach ($acls as $acl) {
                
                if (!$privilegesForm->getSubForm('module_'.$acl->Module->id)) {
                    
                    $privilegesForm->addSubForm(new Zend_Form_Subform(array('legend' => $acl->Module->name)), 'module_'.$acl->Module->id);
                }
                
                if (!$privilegesForm->getSubForm('module_'.$acl->Module->id)->getElement('resource_'.$acl->Resource->id)) {
                    
                    $privilegesForm->getSubForm('module_'.$acl->Module->id)
                                   ->addElement('multiCheckbox', 'resource_'.$acl->Resource->id, array('label'=>$acl->Resource->name));
                }
                
                if (!$privilegesForm->getSubForm('module_'.$acl->Module->id)->getElement('resource_'.$acl->Resource->id)->getMultiOption('privilege_'.$acl->id)) {
                    
                    $privilegesForm->getSubForm('module_'.$acl->Module->id)
                                   ->getElement('resource_'.$acl->Resource->id)
                                   ->addMultiOption('privilege_'.$acl->id, $acl->Privilege->name);
                }
            }
        }
        
        $this->addSubform($privilegesForm, 'privileges');
        $this->addElement('submit', 'submit', array('label' => 'Submit'));
    }
}