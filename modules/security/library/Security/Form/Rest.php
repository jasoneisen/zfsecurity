<?php

/**
 * Undocumented class.
 *
 * @todo document me
 * @package unknown
 * @author jasoneisen@gmail.com
 **/
class Security_Form_Rest extends Security_Form
{
	/**
	 * Undocumented function.
	 *
	 * @todo document me
	 * @return unknown
	 * @author jasoneisen@gmail.com
	 **/
	public function init()
	{
		// Rest method
		$restMethod = new Zend_Form_Element_Hidden('_method');
		$restMethod->setOrder(100);
		$this->addElement($restMethod);
	}
}