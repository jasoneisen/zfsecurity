<?php

class Security_Form_Install extends Zend_Form
{
    public function init()
    {
        $intro = new Zend_Form_Subform();
        
        $introText = "Welcome to the security module installer.  
                      Before this module can work, you will need to do a few things to your app.  
                      This installer will guide you.  Click Begin to start.";
        
        $intro->addElement('multicheckbox', 'test',array('label' => 'This is a label',
                                                         'required' => true));
        $intro->getElement('test')->addMultiOptions(array('1'=>'1',
                                                         '2'=>'2'));
        
        //$intro->addElement('submit', 'submit', array('label' => $introText));
        //$intro->getElement('submit')
        //      ->setDisableLoadDefaultDecorators(true)
        //      ->addDecorator('ViewHelper')
        //      ->addDecorator('Errors')
        //      ->addDecorator('HtmlTag', array('tag'=>'dd'))
        //      ->addDecorator('Label', array('tag'=>'dt'))
        //      ->setValue('Test');
        
        $this->addSubform($intro, 'intro');
    }
}