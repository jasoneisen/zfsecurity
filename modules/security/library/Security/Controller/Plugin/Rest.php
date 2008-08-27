<?php

class Security_Controller_Plugin_Rest extends Zend_Controller_Plugin_Abstract
{
    /**
     * undocumented function
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     * @author David Abdemoulaie
     **/
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
}