<?php

class Security_UpdateController extends Security_Controller_Action_Backend
{
    protected $_aclParts = array();
    
    public function indexAction()
    {
        
    }
    
    public function aclAction()
    {
        $modules = $this->_getAclChanges();
        
        if (!$this->getRequest()->isPost()) {
            
            $this->view->modules = $modules;
            
        } else {
            
            foreach ($modules as $moduleName => $module) {
                
                $mPart = $this->_getPart($moduleName);
                
                foreach ($module['resources'] as $resourceName => $resource) {
                    
                    $rPart = $this->_getPart($resourceName);
                    
                    foreach ($resource['privileges'] as $privName => $priv) {
                        
                        $pPart = $this->_getPart($privName);
                        
                        if ($priv) {
                           
                           $acl = new Acl();
                           $acl->module_id = $mPart->id;
                           $acl->resource_id = $rPart->id;
                           $acl->privilege_id = $pPart->id;
                           $acl->save();
                           
                        } else {
                            
                            Doctrine_Query::create()->delete()
                                                    ->from('SecurityAcl')
                                                    ->addWhere('module_id = ?', $mPart->id)
                                                    ->addWhere('resource_id = ?', $rPart->id)
                                                    ->addWhere('privilege_id = ?', $pPart->id)
                                                    ->execute();
                        }
                    }
                }
            }
            $this->view->message = "ACL successfully updated.";
        }
    }
    
    protected function _addPart($part)
    {
        if (!isset($this->_aclParts[$part->name])) {
            $this->_aclParts[$part->name] = $part;
        }
    }
    
    protected function _getPart($name)
    {
        if (!isset($this->_aclParts[$name])) {
            
            if (!$aclPart = Doctrine::getTable('SecurityAclPart')->findOneByName($name)) {
                
                $aclPart = new AclPart();
                $aclPart->name = $name;
                $aclPart->save();
            }
            $this->_aclParts[$name] = $aclPart;
        }
        return $this->_aclParts[$name];
    }
    
    protected function _getAclChanges()
    {
        $gen = new Security_Acl_Generator();
        $acls = Security_Acl::getInstance()->getAcl();
        $modules = array();
        
        foreach ($acls as $acl) {
            
            $modules[$acl->Module->name]['resources'][$acl->Resource->name]['privileges'][$acl->Privilege->name] = 0;
            
            $this->_addPart($acl->Module);
            $this->_addPart($acl->Resource);
            $this->_addPart($acl->Privilege);
        }
        
        foreach ($gen->getResources() as $genModule => $genResources) {
            
            foreach ($genResources as $genResource) {
                
                foreach ($gen->getActions($genResource) as $genAction) {
                    
                    if (!isset($modules[$genModule]['resources'][$genResource]['privileges'][$genAction])) {

                        $modules[$genModule]['resources'][$genResource]['privileges'][$genAction] = 1;
                        
                    } else {
                        
                        unset($modules[$genModule]['resources'][$genResource]['privileges'][$genAction]);
                    }
                }
                
                if (empty($modules[$genModule]['resources'][$genResource]['privileges'])) {
                    
                    unset($modules[$genModule]['resources'][$genResource]);
                }
            }
            
            if (empty($modules[$genModule]['resources'])) {
                
                unset($modules[$genModule]);
            }
        }
        return $modules;
    }
    
    protected function _generateForm()
    {
        // This does nothing
    }
}