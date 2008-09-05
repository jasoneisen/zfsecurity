<?php

class Security_SettingsController extends Security_Controller_Action_Backend
{
    public function indexAction()
    {
        $form = $this->_getForm('put');
		
		if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
		    
			if ($this->_saveOptions($form->getValues())) {
			    
			    // Redirect so the values get loaded
			    $this->getHelper('Redirector')->gotoRoute(array('module'=>'security','controller'=>'settings','action'=>'index'), 'default');
			}
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
        
        if (!isset($params['dataPath']) || !Zend_Loader::isReadable($params['dataPath'])) {
            
            $dataPath = dirname(dirname(realpath(__FILE__))) . DIRECTORY_SEPARATOR . 'data';
            $form->getElement('dataPath')->setValue($dataPath)->setAttrib('size',strlen($dataPath));
        }
	    
	    return $form;
	}
	
	protected function _saveOptions($post)
	{
		$options = Doctrine_Query::create()
		    ->select('so.name, so.value')
		    ->from('SecurityOption so INDEXBY so.name')
		    ->execute();

		try {
			
			Doctrine_Manager::connection()->beginTransaction();

			foreach ($post as $name => $value) {
			    
			    if ($name == 'submit') {
			        continue;
		        }
			    
			    if (!isset($options[$name])) {
			        
			        $option = new SecurityOption();
			        $option->name = $name;
			        
			    } else {
			        $option = $options[$name];
			    }
			    
				$option->value = $value;
				$option->save();
			}
			
			Doctrine_Manager::connection()->commit();
			
			return true;
		
		} catch (Exception $e) {
		    Doctrine_Manager::connection()->rollback();
			return false;
		}
	}
}