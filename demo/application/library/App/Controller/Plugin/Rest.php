<?php
 
/**
 * This plugin allows the detection of emulated HTTP PUT/DELETE requests in POST 
 * requests. As browsers do not reliably implement HTTP PUT/DELETE, all forms 
 * POSTing to either an edit or destroy action should use PUT and DELETE, respectively.
 * These methods should be specified by a hidden input element in the associated form 
 * of the following format:
 * 
 * <input name="_method" type="hidden" value="put|delete"/>
 * 
 * This plugin attaches to the routeShutdown hook, which runs immediately after
 * the router finishes routing the request.
 * 
 * Requests will be routed as follows:
 * 
 * GET  ':controller'           => :controller/index
 * GET  ':controller/:id'       => :controller/show/:id
 * GET  ':controller/new'       => :controller/new
 * GET  ':controller/:id/edit   => :controller/edit/:id
 * POST ':controller'           => :controller/create
 * PUT  ':controller/:id'       => :controller/update/:id
 * DELETE ':controller/:id'     => :controller/destroy/:id
 *
 * @package default
 * @author David Abdemoulaie
 **/
class App_Controller_Plugin_Rest extends Zend_Controller_Plugin_Abstract
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