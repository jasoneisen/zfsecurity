<?php

class Security_GroupsController extends Security_Controller_Action_Backend
{
	public function indexAction()
	{
	   $this->view->groups = Doctrine_Query::create()
	                                        ->select('g.id, g.name')
	                                        ->from('Group g')
	                                        ->orderby('g.name')
	                                        ->execute();
	}
    
    public function showAction()
    {
       //$this->view->group = $this->_getGroup($this->getRequest()->getParam('id'));
       $this->getHelper('Redirector')->gotoRoute(array(), 'security_groups_path');
    }
    
    public function newAction()
    {
       $this->view->form = $this->_getForm('post');
    }
    
    public function createAction()
    {
        $form = $this->_getForm('post');
        
        if ($form->isValid($this->getRequest()->getPost())) {
        
            if ($group = $this->_saveGroup($form->getValues())) {
            
                $this->getHelper('Redirector')->gotoRoute(array('id'=>current($group->identifier())), 'security_group_path');
            }
        }
        
        $this->_setForm($form);
        $this->_forward('new');
    }
    
    public function editAction()
    {
       $this->view->form = $this->_getForm('put');
    }
    
    public function updateAction()
    {
       $form = $this->_getForm('post');
        
        if ($form->isValid($this->getRequest()->getPost())) {
        
            if ($group = $this->_saveGroup($form->getValues(), $this->getRequest()->getParam('id'))) {
            
                $this->getHelper('Redirector')->gotoRoute(array('id'=>current($group->identifier())), 'security_group_path');
            }
        }
        
        $this->_setForm($form);
        $this->_forward('edit');
    }
    
    public function destroyAction()
    {
       
    }
    
    protected function _generateForm()
    {
        $form = new Security_Form_Group();
        
        if (!$this->getRequest()->isPost()
                && $group = $this->_getGroup($this->getRequest()->getParam('id'))) {
            
            $form->populate(array('group' => $group->toArray()));
            $acls = Security_Acl::getInstance()->getAcl();
            
            $populate = array();
            
            foreach ($acls as $acl) {
                
                if (isset($acl->Groups[$group->id])) {
                    
                    // This is stupid
                    $populate = array_merge_recursive($populate, 
                                    array('privileges' => 
                                        array('module_'.$acl->module_id => 
                                            array('resource_'.$acl->resource_id => 
                                                array($acl->id => 'privilege_'.$acl->id)))));
                }
            }
            $form->populate($populate);
        }
        
        return $form;
    }
    
    protected function _saveGroup($data, $group = null)
    {
        if ($group === null) {
            
            $group = new Group();
            
        } elseif ($group instanceof Doctrine_Record
                 || (is_numeric($group) && $group = $this->_getGroup($group))) {
            
            Doctrine_Query::create()
                            ->delete()
                            ->from('GroupAcl ga')
                            ->addWhere('ga.group_id = ?', current($group->identifier()))
                            ->execute();
                            
            $group->refresh();
            
        } else {
            return false;
        }
        
        $group->merge($data['group']);
        $group->save();
        
        foreach ($data['privileges'] as $moduleName => $module) {
        
            list($garbage, $moduleId) = explode("_", $moduleName);
            
            foreach ($module as $resourceName => $resource) {
            
                list($garbage, $resourceId) = explode("_", $resourceName);
                
                if (!empty($resource)) {
                    
                    foreach ($resource as $key => $privilege) {
                        
                        list($garbage, $privilegeId) = explode("_", $privilege);
                        
                        $groupAcl = new GroupAcl();
                        $groupAcl->group_id = $group->id;
                        $groupAcl->acl_id = $privilegeId;
                        $groupAcl->save();
                    }
                }
            }
        }
        return $group;
    }
    
    protected function _getGroup($id)
    {
        if (!is_numeric($id)) {
            return false;
        }
        return Doctrine_Query::create()->from('Group g')->addWhere('g.id = ?')->fetchOne(array($id));
    }
}