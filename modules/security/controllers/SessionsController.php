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
            if (($actionName == 'new' || $actionName == 'create') && Security_System::getActiveModel()->isLoggedIn()) {
                $this->getHelper('Redirector')->gotoRoute(array(), 'default', true);
            }
            
            if (($actionName == 'delete' || $actionName == 'destroy') && !Security_System::getActiveModel()->isLoggedIn()) {
                $this->getHelper('Redirector')->gotoRoute(array(), 'new_security_sessions_path', true);
            }
        }
    }
    public function indexActon()
    {
        $this->_forward('new');
    }    
    
    public function newAction()
    {
        $this->view->form = $this->_getForm('post');
    }
    
    public function createAction()
    {
        $form = $this->_getForm('post');
        
        if ($form->isValid($this->getRequest()->getPost())) {
            
            $options = Security_System::getInstance()->getOptions();
            
            $authAdapter = new Site_Auth_Adapter_Doctrine_Record(
			                        Doctrine::getConnectionByTableName($options['accountTableName']));
			
			$authAdapter->setTableName($options['accountTableName'])
            			->setIdentityColumn($options['identityColumnName'])
            			->setCredentialColumn($options['credentialColumnName'])
            			->setIdentity($form->getValue('identity'))
                        ->setCredential($form->getValue('credential'));
            
            if ($options['credentialColumnTreatment']) {
                
                $authAdapter->setCredentialTreatment($options['credentialColumnTreatment']);
            }
            
        	$result = Zend_Auth::getInstance()->authenticate($authAdapter);
        	
        	switch ($result->getCode()) {
        	    
                case Zend_Auth_Result::SUCCESS:
                
                    Zend_Auth::getInstance()->getStorage()->write(
                        $authAdapter->getResultRowObject(
                            Doctrine::getTable($options['accountTableName'])->getIdentifier(), $options['credentialColumnName']));
                    
                    if ($form->getValue('return_url')) {
                        
                        $this->_redirect($form->getValue('return_url'));
                    }
                    $this->getHelper('Redirector')->gotoRoute(array(), 'default', true);
                    break;
                
                case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                
                    $form->getElement('identity')
                         ->addValidator('customMessages', false, array(
                             $options['identityColumnTitle'].' \''.$form->getValue('identity').'\' does not exist'))
                         ->isValid($form->getValue('identity'));
                    break;
                
                case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                
                    $form->getElement('credential')
                         ->addValidator('customMessages', false, array(
                             $options['credentialColumnTitle'] .' is invalid for supplied '. $options['identityColumnTitle']))
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
        $this->getHelper('Redirector')->gotoRoute(array(), 'new_security_sessions_path', true);
    }
    
    protected function _generateForm()
    {
        $actionName = $this->getRequest()->getActionName();
        
        if ($actionName == 'new' || $actionName == 'create') {
            return new Security_Form_Login();
        }
        if ($actionName == 'delete' || $actionName == 'destroy') {
            return new Security_Form_Logout();
        }
    }
}