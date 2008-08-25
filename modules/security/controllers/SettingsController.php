<?php

class Security_SettingsController extends Security_Controller_Action_Backend
{
    public function indexAction()
    {
        $form = $this->_getForm('put');
		
		if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
		    
			$this->_saveOptions($form->getValues());
		}
        $this->view->form = $form;
    }
    
    protected function _generateForm()
	{
	    $form = new Security_Form_Options();
	    $form->setOptionsPath(Security_System::getInstance()->getParam('optionsPath'));
	    $form->buildFromOptionsPath();
	    
	    return $form;
	}
	
	protected function _saveOptions($post)
	{        
		$options = Doctrine::getTable('SecurityOption')->findAll();

		try {
			
			Doctrine_Manager::connection()->beginTransaction();

			foreach ($options as $option) {
				$option->value = $post[$option->tag];
				$option->save();
			}
			
			Doctrine_Manager::connection()->commit();
			
			return true;
		
		} catch (Exception $e) {
			return false;
		}
	}
}