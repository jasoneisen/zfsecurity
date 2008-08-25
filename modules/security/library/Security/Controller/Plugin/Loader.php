<?php

class Security_Controller_Plugin_Loader extends Zend_Controller_Plugin_Abstract
{
	public function __construct()
	{
		$front = Zend_Controller_Front::getInstance();
		
		if (Security_System::getInstance()->isEnabled('acl')) {
			
			$front->registerPlugin(new Security_Controller_Plugin_Auth());
		}
	}

	public function routeStartup(Zend_Controller_Request_Abstract $request)
	{
	    if ($request->getModuleName() == 'security') {
	        
	        $front = Zend_Controller_Front::getInstance();
	        $front->registerPlugin(new Security_Controller_Plugin_Rest());
	    }
		
	}
	
	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
	{
		
	}
}