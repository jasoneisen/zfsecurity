<?php

class Security_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        
    }
    
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
		$security = Security_System::getInstance();
		
		if (!$security->isEnabled('acl')) {
			return;
		}
		
		if (!$groups = $security->getActiveModel()->getRecord('Groups')) {
		    
		    $groups[] = (object) array('name' => 'Anonymous');
		}
		
		$acl = Security_Acl::getInstance();
		
		foreach ($groups as $group) {
        
		    try {
		    	
		    	if (!$acl->hasRole($group->name)) {
       	    	    throw new Exception("The requested user role '".$group->name."' does not exist");									
       	    	}
       	    	if (!$acl->has($request->getModuleName().'_'.$request->getControllerName())) {
		    		throw new Exception("The requested controller '".$request->getControllerName()."' does not exist as an ACL resource");
 		    	}
		    	if (!$acl->isAllowed($group->name, $request->getModuleName().'_'.$request->getControllerName(), $request->getActionName())) {
		    		throw new Exception("The page you requested does not exist or you do not have access");
		    	}
		    	
		    	return;
		    	
		    } catch (Exception $e) {
		    	
		    	if (!$security->getOption('useSecurityErrorController')) {
		    		throw new Security_Acl_Exception($e->getMessage());
		    	}
		    	
		    	$error = $e->getMessage();
		    }
		    
	    }
        
		if (isset($error)) {
			
			$security->setEnabled('acl', false);
			
			Zend_Layout::getMvcInstance()->getView()->error = $error;
			
			$request->setModuleName('security');
			$request->setControllerName('error');
			$request->setActionName('error');
			$request->setDispatched(false);
			
		}
    }
}