<?php

/**
 * Undocumented class.
 *
 * @todo document me
 * @package unknown
 * @author jaeisenmenger@edrivemedia.com
 **/
class Security_Form_SubForm_Option extends Zend_Form_SubForm
{
	/**
	 * Undocumented function.
	 *
	 * @todo document me
	 * @return unknown
	 * @author jaeisenmenger@edrivemedia.com
	 **/
	public function init()
	{
		$options = Doctrine::getTable('SecurityOption')->findAll();
		
		$elements = array();
		
		foreach ($options as $option) {
			
			$element = new Zend_Form_Element_Text($option->tag, array('size'=>'10'));
			$element->addFilter('StringTrim');
			$element->setLabel($option->description);
			$this->addElement($element);
			
			$elements[] = $option->tag;
		}
		
		$this->addDisplayGroup($elements,
			'options');
			//array('legend'=>'Role'));
	}
}