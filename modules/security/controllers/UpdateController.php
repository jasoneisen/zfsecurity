<?php

class Security_UpdateController extends Security_Controller_Action_Backend
{
    public function indexAction()
    {
        
    }
    
    public function aclAction()
    {
        $gen = new Security_Acl_Generator();
        
        if (!$this->getRequest()->isPost()) {
            
            $modules = array();
            
            foreach ($gen->getResources() as $genModule => $genResources) {
                
                foreach ($genResources as $genResource) {
                    
                    foreach ($gen->getActions($genResource) as $genAction) {
                        
                        if (!$this->_aclExists($genModule, $genResource, $genAction)) {

                            $modules[$genModule]['resources'][$genResource]['privileges'][$genAction]['new'] = true;
                        }
                    }
                }
            }
            
            if (!empty($modules)) {
                $this->view->acl = $modules;
            }
        } else {
            
            $parts = Doctrine_Query::create()
                                     ->select('ap.name')
                                     ->from('AclPart ap INDEXBY ap.name')
                                     ->execute()
                                     ->toArray();
            
            foreach ($gen->getResources() as $genModule => $genResources) {
                
                $module = $this->_addPart($genModule);
                
                foreach ($genResources as $genResource) {
                    
                    $resource = $this->_addPart($genResource);
                    
                    foreach ($gen->getActions($genResource) as $genAction) {
                        
                        $privilege = $this->_addPart($genAction);
                        
                        if (!$this->_aclExists($module->name, $resource->name, $privilege->name)) {
                           
                           $acl = new Acl();
                           $acl->module_id = $module->id;
                           $acl->resource_id = $resource->id;
                           $acl->privilege_id = $privilege->id;
                           $acl->save();
                        }
                    }
                }
            }
        }
    }
    
    protected function _aclExists($module, $resource, $privilege)
    {
        try {
            Doctrine::getTable('Acl');
        } catch (Doctrine_Exception $e) {
            return;
        }
        // This could be time tested against looping through Security_Acl::getInstance->getAcl()
        return (Doctrine_Query::create()
                                ->from('Acl a')
                                ->innerJoin('a.Module m')
                                ->innerJoin('a.Resource r')
                                ->innerJoin('a.Privilege p')
                                ->addWhere('m.name = ?')
                                ->addWhere('r.name = ?')
                                ->addWhere('p.name = ?')
                                ->fetchOne(array($module, $resource, $privilege))) ? true : false;
    }
    
    protected function _addPart($name)
    {
        if (!isset($this->_parts[$name])) {
            if (!$aclPart = Doctrine::getTable('AclPart')->findOneByName($name)) {
                $aclPart = new AclPart();
                $aclPart->name = $name;
                $aclPart->save();
            }
            $this->_parts[$name] = $aclPart;
        }
        return $this->_parts[$name];
    }
    
    protected function _generateForm()
    {
        
    }
}