<?php

class Security_AccountsController extends Security_Controller_Action_Backend
{
    public function init()
    {
        if (!$formAction = $this->getRequest()->getParam('_action')) {
            return;
        }
        
        $requestAction = $this->getRequest()->getActionName();
        
        if ($requestAction == 'create') || $requestAction == 'destroy') {
            
            switch ($formAction) {
                
                case 'verify':
                
                break;
                
                case 'recover':
                
                break;

                default:
                    $action = $requestAction;
                    $controller = 'sessions';
                    $module = 'security';
                    $params = array('AuthenticationForm' => $this->_getLoginForm());
                break;
            }
            
            $this->_forward($action, $controller, $module, array_merge($this->getRequest()->getParams(), $params)));
        }
    }
    
    public function indexAction()
    {
        
    }

    protected function _generateForm()
    {
        
    }
}