<?php

class Security_IndexController extends Security_Controller_Action_Backend
{
	protected $_formNamespace = 'SecurityIndexControllerForm';
	
	public function indexAction()
	{
		
	}
	
	public function optionsAction()
	{
		$this->view->form = $this->_getForm('put');
	}
	
	public function updateAction()
	{
		$form = $this->_getForm('put');
		
		if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
			if ($this->_saveOptions()) {
				$this->getHelper('Redirector')->goto('options');
			}
		}
		
		$this->_setForm($form);
		$this->_forward('options');
	}
	
	protected function _generateForm($method = null)
	{
		$form = new Security_Form_Rest();
		
		if ($options = Doctrine::getTable('SecurityOption')->findAll()) {
			
			foreach ($options as $option) {
				
				$subform = new Zend_Form_SubForm();
				
				$element = new Zend_Form_Element_Text($option->tag, array('size'=>'10'));
				$element->addFilter('StringTrim');
				$element->setLabel($option->description);
				
				if (!$this->getRequest()->isPost()) {
					$element->setValue($option->value);
				}
				
				$subform->addElement($element);
				$subform->setLegend($option->name);
        
				//$subform->addDisplayGroup(array($option->tag), 'options', array('legend'=>$option->name));
				$form->addSubform($subform, $option->tag);
			}
		} else {
			
			$this->getHelper('Redirector')->goto('home', 'install');
		}
        
		$form->addElement('submit', 'submit', array('label' => 'Submit'));
		
		return $form;
	}
	
	protected function _saveOptions()
	{
		$form = $this->_getForm();
		$post = $form->getValues();

		$options = Doctrine::getTable('SecurityOption')->findAll();

		try {
			
			Doctrine_Manager::connection()->beginTransaction();

			foreach ($options as $option) {
				$option->value = $post[$option->tag][$option->tag];
				$option->save();
			}
			
			Doctrine_Manager::connection()->commit();
			
			return true;
		
		} catch (Exception $e) {
			return false;
		}
	}
}