<?php

class Security_SessionsController extends Security_Controller_Action_Backend
{
    public function init()
    {
        parent::init();
        
        // We don't forward if the parent has
        if ($this->getRequest()->isDispatched()) {
        
            $actionName = $this->getRequest()->getActionName();
            
            // Enforce this even if ACL does not
            if (($actionName == 'new' || $actionName == 'create') && Security::getActiveAccount()) {
                $this->getHelper('Redirector')->gotoRoute(array(), 'default', true);
            }
            
            if (($actionName == 'delete' || $actionName == 'destroy') && !Security::getActiveAccount()) {
                $this->getHelper('Redirector')->gotoRoute(array(), 'new_security_session_path', true);
            }
        }
    }
    public function indexAction()
    {
        $this->_forward('new');
    }    
    
    public function newAction()
    {
        if ($this->_getParam('isViewAction')) {
            $this->view->isViewAction = true;
        }
        
        $form = $this->_getForm('post');
        
        $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
        $flashMessenger->setNameSpace('Security_Return_Url');
        
        if ($flashMessenger->hasMessages()) {
            
            list($returnUrl) = $flashMessenger->getMessages();
            $flashMessenger->clearMessages();
            $flashMessenger->addMessage($returnUrl);
        }
        
        $this->view->loginForm = $this->_getForm('post');
    }
    
    public function createAction()
    {
        $form = $this->_getForm('post');
        
        if ($form->isValid($this->getRequest()->getPost())) {
            
            $options = Security::getParams();
            
            $authAdapter = new Security_Auth_Adapter_Doctrine_Record(
			                        Doctrine::getConnectionByTableName($options['accountTableClass']));
			
			$authAdapter->setTableName($options['accountTableClass'])
            			->setIdentityColumn($options['loginIdentityColumn'])
            			->setCredentialColumn($options['loginCredentialColumn'])
            			->setIdentity($form->getValue('identity'))
                        ->setCredential($form->getValue('credential'));
            
            if ($options['loginCredentialTreatment']) {
                
                $authAdapter->setCredentialTreatment($options['loginCredentialTreatment']);
            }
            
        	$result = Zend_Auth::getInstance()->authenticate($authAdapter);
        	
        	switch ($result->getCode()) {
        	    
                case Zend_Auth_Result::SUCCESS:
                
                    Zend_Auth::getInstance()->getStorage()->write(
                        $authAdapter->getResultRowObject(
                            Doctrine::getTable($options['accountTableClass'])->getIdentifier(), $options['loginCredentialColumn']));
                    
                    $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
                    $flashMessenger->setNameSpace('Security_Return_Url');
                    
                    if ($flashMessenger->hasMessages()) {
                    
                        list($returnUrl) = $flashMessenger->getMessages();
                        $this->_redirect($returnUrl);
                    }
                    
                    $this->getHelper('Redirector')->gotoRoute(array(), 'default', true);
                    break;
                
                case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                
                    $form->getElement('identity')
                         ->addValidator('customMessages', false, array(
                             $options['loginIdentityLabel'].' \''.$form->getValue('identity').'\' does not exist'))
                         ->isValid($form->getValue('identity'));
                    break;
                
                case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                
                    $form->getElement('credential')
                         ->addValidator('customMessages', false, array(
                             $options['loginCredentialLabel'] .' is invalid for supplied '. $options['loginIdentityLabel']))
                         ->isValid($form->getValue('credential'));
                    break;
                
                default:
                    break;
            }
        }
        $this->_setForm($form);
        $this->_forward('new');
        return;
    }
    
    public function deleteAction()
    {
        $this->view->form = $this->_getForm('delete');
    }
    
    public function destroyAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
		Zend_Session::destroy();
        $this->getHelper('Redirector')->gotoRoute(array(), 'new_security_session_path', true);
    }
    
    protected function _generateForm()
    {
        $actionName = $this->getRequest()->getActionName();
        
        if ($actionName == 'new' || $actionName == 'create') {
            
            $form = new Security_Form_Login();
            $form->setAction($this->view->Url(array(), 'new_security_session_path', true));
            
            return $form;
        }
        if ($actionName == 'delete' || $actionName == 'destroy') {
            
            $form = new Security_Form_Logout();
            $form->setAction($this->view->Url(array(), 'delete_security_session_path', true));
            
            return $form;
        }
    }
}