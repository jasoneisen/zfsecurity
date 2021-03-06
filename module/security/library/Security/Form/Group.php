<?php

class Security_Form_Group extends Zend_Form
{
    public function init()
    {
        $this->addElement('hidden', '_method');
        
        $groupForm = new Zend_Form_SubForm(array('legend' => 'Group'));

        $groupForm->addElement('text', 'name', array(
            'filters'    =>  array(
                'StringTrim'),
            'required'  =>  true,
            'label' =>  'Name'));
        
        $groupForm->addElement('textarea', 'description', array(
            'filters'    =>  array(
                'StringTrim'),
            'required'  =>  false,
            'label' =>  'Description',
            'rows' => 8,
            'cols' => 50));
        
        $this->addSubForm($groupForm, 'group');
        
        $privilegesForm = new Zend_Form_SubForm(array('legend' => 'Privileges'));
					
        if ($acls = Security::getAclInstance()->getAclResultObject()) {
            
            foreach ($acls as $acl) {
                
                if (!$privilegesForm->getSubForm('module_'.$acl->Module->id)) {
                    
                    $privilegesForm->addSubForm(new Zend_Form_SubForm(array('legend' => $acl->Module->name)), 'module_'.$acl->Module->id);
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