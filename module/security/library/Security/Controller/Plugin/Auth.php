<?php

class Security_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
		if (!Security::isEnabled('acl') || !$acl = Security::getAclInstance()) {
		    return;
		}
		
		if ((!$account = Security::getActiveAccount()) || $account instanceof Doctrine_Null) {
		    
		    $groups[] = (object) array('name' => 'Anonymous');
		} else {
		    $groups = $account->Groups;
		}
		
		$defaultModule = Zend_Controller_Front::getInstance()->getDefaultModule();
		$moduleName = ($request->getModuleName()) ? $request->getModuleName() : $defaultModule;
		
		foreach ($groups as $group) {
        
		    try {
		    	
		    	if (!$acl->hasRole($group->name)) {
       	    	    throw new Exception("The requested user role '".$group->name."' does not exist");									
       	    	}
       	    	if (!$acl->has($moduleName.'_'.$request->getControllerName())) {
		    		throw new Exception("The requested controller '".$request->getControllerName()."' does not exist as an ACL resource");
 		    	}
		    	if (!$acl->isAllowed($group->name, $moduleName.'_'.$request->getControllerName(), $request->getActionName())) {
		    		throw new Exception("The page you requested does not exist or you do not have access");
		    	}
		    	
		    	return;
		    	
		    } catch (Exception $e) {}
	    }
        
		if (isset($e)) {
		    
		    if (!$account instanceof Doctrine_Record) {
		        
		        if ($loginRouteName = Security::getParam('loginRouteName')) {
		            $path = $loginRouteName;
		        } else {
		            $path = 'new_security_session_path';
		        }
		        
		        $uri = str_replace($request->getBaseUrl(), '', $request->getRequestUri());
		        
		        $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
		        $flashMessenger->setNameSpace('Security_Return_Url');
		        $flashMessenger->addMessage($uri);
		        
		        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
		        $redirector->gotoRouteAndExit(array(), $path, true);

		    } else {
		        
		        $e->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
		        
		        $this->getResponse()->setException($e);
		        
		        $errorHandler = Zend_Controller_Front::getInstance()
		            ->getPlugin('Zend_Controller_Plugin_ErrorHandler');
		            
                $errorHandler->postDispatch($request);
		    }
		}
    }
}