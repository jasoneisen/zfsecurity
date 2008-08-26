<?php

class Security_SessionsController extends Security_Controller_Action_Backend
{
    public function init()
    {
        parent::init();
        
        // We don't forward if the parent has
        //if ($this->getRequest()->isDispatched()) {
        //
        //    $actionName = $this->getRequest()->getActionName();
        //    
        //    // Enforce this even if ACL does not
        //    if (($actionName == 'new' || $actionName == 'create') && Security_System::getActiveModel()->isLoggedIn()) {
        //        $this->getHelper('Redirector')->gotoRoute(array(), 'default', true);
        //    }
        //    
        //    if (($actionName == 'delete' || $actionName == 'destroy') && !Security_System::getActiveModel()->isLoggedIn()) {
        //        $this->getHelper('Redirector')->gotoRoute(array(), 'new_security_session_path', true);
        //    }
        //}
    }
    public function indexActon()
    {
        $this->_forward('new');
    }    
    
    public function newAction()
    {
        if ($this->_getParam('isViewAction')) {
            $this->view->isViewAction = true;
        }
        
        $form = $this->_getForm('post');
        
        if (($returnUrl = $this->_getParam('returnUrl'))
            || $returnUrl = str_replace($this->getRequest()->getBaseUrl(), "", $this->getRequest()->getRequestUri())) {
            
            // Check if the return url is pointing to this site
            //if (0 === strstr($returnUrl, $this->view->BaseUrl())) {
                
                $form->getElement('return_url')->setValue($returnUrl);
            //}
        }
        
        $this->view->loginForm = $this->_getForm('post');
    }
    
    public function createAction()
    {
        $form = $this->_getForm('post');
        
        if ($form->isValid($this->getRequest()->getPost())) {
            
            $options = Security_System::getInstance()->getParams();
            
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
                    
                    if ($form->getValue('return_url')) {
                        
                        $this->_redirect($form->getValue('return_url'));
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