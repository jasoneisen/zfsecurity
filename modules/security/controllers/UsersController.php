<?php

class Security_UsersController extends Security_Controller_Action_Backend
{
    public function indexAction()
    {
        $this->view->accounts = Doctrine_Query::create()
    	                                        ->select('g.id, g.name')
    	                                        ->from('Group g')
    	                                        ->execute();
    }
    
    public function loginAction()
    {
        $this->_forward('login', 'users', 'default');
    }
    
    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        Zend_Session::destroy();
        $this->_redirect('/');
    }
    
    public function registrationAction()
    {
        $this->_forward('registration', 'users', 'default');
    }
    
    public function verifyAction()
    {
        $this->_forward('verify', 'users', 'default');
    }
    
    public function lostpasswordAction()
    {
        $this->_forward('lostpassword', 'users', 'default');
    }
    
    public function setlostpasswordAction()
    {
        $this->_forward('setlostpassword', 'users', 'default');
    }
    
    protected function _generateForm()
    {
        
    }
}