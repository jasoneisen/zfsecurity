<?php
 
class Security_Acl extends Zend_Acl
{ 
    protected static $_instance = null;
    
    protected $_acl = null;
    
    protected $_groups = null;
    
	private function __construct() {}
    
	protected function __clone() {}
    
	public static function getInstance() {
		if(null === self::$_instance) {
			self::$_instance = new self();
			self::$_instance->_initialize();
		}
		return self::$_instance;
	}
 
    protected function _initialize() {
	
		$acls = Doctrine_Query::create()
					->from('SecurityAcl a')
					->innerJoin('a.Module m')
					->innerJoin('a.Resource r')
					->innerJoin('a.Privilege p')
					->leftJoin('a.Groups g INDEXBY g.id')
					->orderby('m.name, r.name, p.name')
					->execute();
		
		$this->_acl = $acls;
	    
	    foreach ($acls as $acl) {
	       
	       if (!$this->has($acl->Module->name . '_' . $acl->Resource->name)) {
	       
	           $this->add(new Zend_Acl_Resource($acl->Module->name . '_' . $acl->Resource->name));
	       }
			
		   foreach ($acl->Groups as $group) {
		   
		      if (!$this->hasRole($group->name)) {
		      
		          $this->addRole(new Zend_Acl_Role($group->name));
		          $this->_groups[$group->name] = $group;
		      }
		      
		      $this->allow($group->name, $acl->Module->name . '_' . $acl->Resource->name, $acl->Privilege->name);
			}
        }
		
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
		
        $this->allow(null, 'security_error');
        $this->allow(null, 'default_error');
    }
	
	public function isAllowed($group = null, $resource = null, $privilege = null)
	{
		if (Security_System::getInstance()->isEnabled('acl')) {
			return parent::isAllowed($group, $resource, $privilege);
		}
		
		return true;
	}
	
	public function getAcl($array = false)
	{
	    if ($array === true && $this->_acl instanceof Doctrine_Record) {
	        return $this->_acl->toArray();
	    }
	    return $this->_acl;
	}
	
	public function getGroups()
	{
	    if ($this->_groups !== null) {
	        return $this->_groups;
	    }
	    return false;
	}
	
	public function getGroup($name)
	{
	    if (isset($this->_groups[$name]) && $this->_groups[$name] instanceof Doctrine_Record) {
	        return $this->_groups[$name];
	    }
	    return false;
	}
}