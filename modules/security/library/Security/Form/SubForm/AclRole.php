<?php

/**
 * Undocumented class.
 *
 * @todo document me
 * @package unknown
 * @author jaeisenmenger@edrivemedia.com
 **/
class Security_Form_SubForm_AclRole extends Zend_Form_SubForm
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
		$name = new Zend_Form_Element_Text('name', array('size' => '32'));
		$name->addFilter('StringTrim');
		$name->setRequired(true);
		$name->setLabel('Name');
		$this->addElement($name);
		
		$description = new Zend_Form_Element_Textarea('description', array('rows' => '10'));
		$description->addFilter('StringTrim');
		$description->setRequired(true);
		$description->setLabel('Description');
		$this->addElement($description);
		
		$this->addDisplayGroup(array('name', 'description'),
			'role');
			//array('legend'=>'Role'));
	}
}