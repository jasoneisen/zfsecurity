<?php

class Security_ErrorController extends Zend_Controller_Action
{
	public function errorAction()
	{
		$this->getResponse()->clearBody();
		
		if ($error = $this->getRequest()->getParam('error')) {
		    
		    $this->view->error = $error;
		}
	}
}