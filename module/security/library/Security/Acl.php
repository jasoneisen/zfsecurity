<?php
 
class Security_Acl extends Zend_Acl
{ 
    protected $_aclResultObject = null;
    
    public function __construct()
    {
        if (!$this->has('security_error')) {
            $this->add(new Zend_Acl_Resource('security_error'));
        }
        
        if (!$this->has('security_sessions')) {
            $this->add(new Zend_Acl_Resource('security_sessions'));
        }
        
        if (!$this->has('default_error')) {
            $this->add(new Zend_Acl_Resource('default_error'));
        }
        
        if (!$this->hasRole('Anonymous')) {
            $this->addRole(new Zend_Acl_Role('Anonymous'));
            $this->allow('Anonymous', 'security_sessions', array('new','create'));
        }
        
        if (!Security::isEnabled('acl')) {
            return;
        }
        
        $acls = $this->getAclResultObject();
        
        foreach ($acls as $acl) {
           
           if (!$this->has($acl->Module->name . '_' . $acl->Resource->name)) {
           
               $this->add(new Zend_Acl_Resource($acl->Module->name . '_' . $acl->Resource->name));
           }
            
           foreach ($acl->Groups as $group) {
           
              if (!$this->hasRole($group->name)) {
              
                  $this->addRole(new Zend_Acl_Role($group->name));
              }
              
              $this->allow($group->name, $acl->Module->name . '_' . $acl->Resource->name, $acl->Privilege->name);
            }
        }
        
        $this->allow(null, 'security_error');
        $this->allow(null, 'default_error');
    }
    
    public function isAllowed($group = null, $resource = null, $privilege = null)
    {
        if (Security::isEnabled('acl')) {
            
            if ($group instanceof Doctrine_Record) {
                
                return $this->recordIsAllowed($group, $resource, $privilege);
            }
            return parent::isAllowed($group, $resource, $privilege);
        }
        return true;
    }
    
    protected function recordIsAllowed(Doctrine_Record $object, $resource = null, $privilege = null) {
        
        if (get_class($object) == 'SecurityGroup') {
            
            return parent::isAllowed($object->name, $resource, $privilege);
        
        } elseif ((get_class($object) == Security::getParam('accountTableClass') ||
                   is_subclass_of($object, Security::getParam('accountTableClass')))
                  && $object->Groups->count()) {
            
            foreach ($object->Groups as $group) {
                
                if (true === parent::isAllowed($group->name, $resource, $privilege)) {
                    
                    return true;
                }
            }
        }
        return false;
    }
    
    public function getAclResultObject()
    {
        if (null === $this->_aclResultObject) {
            
            $this->_aclResultObject = Doctrine_Query::create()
                        ->from('SecurityAcl a')
                        ->innerJoin('a.Module m')
                        ->innerJoin('a.Resource r')
                        ->innerJoin('a.Privilege p')
                        ->leftJoin('a.Groups g INDEXBY g.id')
                        ->orderby('m.name, r.name, p.name')
                        ->execute();
        }
        return $this->_aclResultObject;
    }
}