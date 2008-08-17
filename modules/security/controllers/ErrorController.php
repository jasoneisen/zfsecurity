<?php

class Security_ErrorController extends Zend_Controller_Action
{
	public function errorAction()
	{
		$this->getResponse()->clearBody();
		$errors = $this->_getParam('error_handler');
		
		if (isset($errors->exception)) {
			//$error = new Error();
			//$error->error_msg = $errors->exception;
			//$error->save();
			echo $errors->exception;
		}
	}
}