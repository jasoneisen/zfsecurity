<?php

class Security_Form_Options extends Zend_Form
{
    public function init()
    {
        $this->addElement('text', 'optionsPath', array('label' => 'Options path', 'required' => true));
        
        $this->addElement('submit', 'submit', array('label' => 'Submit', 'order' => 100));
    }
    public function buildFromOptionsPath($formOptions = array())
    {
        $optionsPath = $this->getValue('optionsPath');
        
        $options = new Zend_Config_Xml($optionsPath);
        
        $this->removeElement('optionsPath');
        
        foreach ($options as $name => $option) {
            
            $validators = array();
            
            switch ($option->type) {
                
                case 'bool':
                    $type = 'checkbox';
                break;
                
                case 'number':
                    $type = 'text';
                    $validator = 'Digits';
                break;
                
                case 'string':
                    $type = 'text';
                break;
                
                case 'text':
                    $type = 'textarea';
                break;
                
                case 'date':
                    $type = 'text';
                    $validator = 'Date';
                break;
            }
            
            $this->addElement($type, $name, array('label' => $option->label,
                                                  'validators' => array($validators),
                                                  'required' => $option->required));
            
            if (false !== strpos($name, 'enable') && isset($formOptions['isInstall']) && $formOptions['isInstall'] === true) {
                
                $this->getElement($name)->setAttrib('disabled', 'disabled');
            }
        }
    }
}