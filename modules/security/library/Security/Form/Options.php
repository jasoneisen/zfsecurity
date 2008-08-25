<?php

class Security_Form_Options extends Zend_Form
{
    public function init()
    {
        $this->addElement('text', 'path', array('label' => 'Options path', 'required' => true));
        
        $this->addElement('submit', 'submit', array('label' => 'Submit', 'order' => 100));
    }
    public function buildFromOptions($path)
    {
        $optionsPath = $this->getValue('optionsPath');
        
        $options = new Zend_Config_Xml($optionsPath);
        
        $this->removeElement('optionsPath');
        $this->addElement('hidden', 'optionsPath', array('value' => $optionsPath));
        
        foreach ($options as $name => $option) {
            
            $validator = array();
            
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
            
            $this->addElement($type, $name, array('label' => $options->name, 'validators' => array($validator), 'required' => true));
        }
    }
}