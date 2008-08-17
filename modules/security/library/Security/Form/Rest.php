<?php

/**
 * Undocumented class.
 *
 * @todo document me
 * @package unknown
 * @author jaeisenmenger@edrivemedia.com
 **/
class Security_Form_Rest extends Zend_Form
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
		// Rest method
		$restMethod = new Zend_Form_Element_Hidden('_method');
		$restMethod->setOrder(100);
		$this->addElement($restMethod);
	}
}