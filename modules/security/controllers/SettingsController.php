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
	    $form = new Security_Form_Settings();
	    
	    $params = Security::getParams();
	    
	    foreach ($params as $name => $value) {
            
            if ($element = $form->getElement($name)) {
                
                if ($element instanceof Zend_Form_Element_Text) {

                    $element->setAttrib('size', strlen($value));
                }
                
                $element->setValue($value);
            }
        }
        
        if (!Zend_Loader::isReadable($params['dataPath'])) {
            
            $dataPath = dirname(dirname(realpath(__FILE__))) . DIRECTORY_SEPARATOR . 'data';
            $form->getElement('dataPath')->setValue($dataPath);
        }
	    
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
		    die($e);
			return false;
		}
	}
}