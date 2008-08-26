<?php

class Security_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
		if (!$acl = Security_System::getAclInstance()) {
		    return;
		}
		
		if ((!$account = Security_System::getActiveAccount()) || !$account->Groups->count()) {
		    
		    $groups[] = (object) array('name' => 'Anonymous');
		} else {
		    $groups = $account->Groups;
		}
		
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
		    
		    if (!$account instanceof Doctrine_Record) {
		        
		        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
		        $redirector->gotoRouteAndExit(array(), 'new_security_session_path', true);

		    } else {
		    
		        if (!$secSys->getParam('useSecurityErrorController')) {
		            
		            throw new Security_Exception($error);
	            }
		            
			    $request->setModuleName('security');
			    $request->setControllerName('error');
			    $request->setActionName('error');
			    $request->setParam('error', $error);
			    $request->setDispatched(false);
		    }
		}
    }
}