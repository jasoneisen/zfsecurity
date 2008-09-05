<?php

class Security_Controller_Plugin_Router extends Zend_Controller_Plugin_Abstract
{
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        if ($request->isPost()) {
            switch ($request->getParam('_method')) {
                case 'post':
                    $request->setActionName('create');
                    break;
                    
                case 'put':
                    $request->setActionName('update');
                    break;
                    
                case 'delete':
                    $request->setActionName('destroy');
                    break;
                    
                default:    
                    break;
            }
        } 
    }
    
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
	{
	    if ($request->getModuleName() == 'security') {
	        
	        $front = Zend_Controller_Front::getInstance();
	        
	        if ($request->getControllerName() == $front->getDefaultControllerName() &&
	            $request->getControllerName() != 'index') {
	            
			    $request->setControllerName('index');
			    $request->setActionName('index');
			    $request->setDispatched(false);
	            
	        } elseif ($request->getActionName() == $front->getDefaultAction() &&
	                  $request->getActionName() != 'index') {
	            
			    $request->setActionName('index');
			    $request->setDispatched(false);
            }
        }
	}
}