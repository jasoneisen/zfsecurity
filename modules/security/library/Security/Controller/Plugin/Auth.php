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
		
		//$roles = $security->getRole();
		//$acl = Security_Acl::getInstance();
        //
		//try {
		//	
		//	if (!$acl->hasRole($role->name)) {
       	//	    throw new Exception("The requested user role '".$role->name."' does not exist");									
       	//	}
       	//	if (!$acl->has($request->getModuleName().'_'.$request->getControllerName())) {
		//		throw new Exception("The requested controller '".$request->getControllerName()."' does not exist as an ACL resource");
 		//	}
		//	if (!$acl->isAllowed($role->name, $request->getModuleName().'_'.$request->getControllerName(), $request->getActionName())) {
		//		throw new Exception("The page you requested does not exist or you do not have access");
		//	}
		//	
		//} catch (Exception $e) {
		//	
		//	if (!$security->getOption('useSecurityErrorController')) {
		//		throw new Security_Acl_Exception($e->getMessage());
		//	}
		//	
		//	$error = $e->getMessage();
		//}
        //
		//if (isset($error)) {
		//	
		//	$security->setEnabled('acl', false);
		//	
		//	Zend_Layout::getMvcInstance()->getView()->error = $error;
		//	
		//	$request->setModuleName('security');
		//	$request->setControllerName('error');
		//	$request->setActionName('error');
		//	$request->setDispatched(false);
		//	
		//}
    }
}