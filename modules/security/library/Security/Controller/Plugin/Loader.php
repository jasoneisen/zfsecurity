<?php

class Security_Controller_Plugin_Loader extends Zend_Controller_Plugin_Abstract
{
    public function __construct()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new Security_Controller_Plugin_Rest());
    }
    
	public function routeStartup(Zend_Controller_Request_Abstract $request)
	{
	    $front = Zend_Controller_Front::getInstance();
	    $router = $front->getRouter();
		
		// @todo fix this hack
		$routesPath = dirname(Security_System::getInstance()->getParam('optionsPath')) . '/routes.xml';
		
		if (Zend_Loader::isReadable($routesPath)) {
		    
		    $routes = new Zend_Config_Xml($routesPath);
		    $router->addConfig($routes);
		}
	}
}