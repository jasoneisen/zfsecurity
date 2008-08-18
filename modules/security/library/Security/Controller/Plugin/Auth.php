<?php

class Security_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
		$secSys = Security_System::getInstance();
		
		if (!$secSys->isEnabled('acl')) {
			return;
		}
		
		if (!isset($secSys->getActiveModel()->Groups) || !$secSys->getActiveModel()->Groups->count()) {
		    
		    $groups[] = (object) array('name' => 'Anonymous');
		} else {
		    $groups = $secSys->getActiveModel()->Groups;
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
		    	
		    	$error = $e->getMessage();
		    }
	    }
        
		if (isset($error)) {
		    
		    if (!Security_System::getActiveModel()->isLoggedIn()) {
		        
		        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
		        $redirector->gotoRouteAndExit(array(), 'new_session_path', true);

		    } else {
		    
		        $module = $secSys->getOption('useSecurityErrorController') ? 'security' : 'default';
		            
			    $request->setModuleName($module);
			    $request->setControllerName('error');
			    $request->setActionName('error');
			    $request->setParam('error', $error);
			    $request->setDispatched(false);
		    }
		}
    }
}