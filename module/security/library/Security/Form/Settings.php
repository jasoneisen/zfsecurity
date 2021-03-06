<?php

class Security_Form_Settings extends Zend_Form
{
    protected $_dataPath = null;
    
    protected $_isInstall = false;
    
    public function __construct($dataPath = null)
    {
        if (null === $dataPath) {
            $dataPath = Security::getParam('dataPath');
        } else {
            $this->_isInstall = true;
        }
        
        $this->_dataPath = $dataPath;
        
        parent::__construct();
    }
    
    public function init()
    {
        $settingsPath = $this->_dataPath . DIRECTORY_SEPARATOR . 'settings.xml';
        
        if (null === $this->_dataPath ||
            !Zend_Loader::isReadable($settingsPath)) {
            
            $this->addElement('text', 'dataPath', array('label' => 'Data Path',
                                                        'validators' => array(
                                                            new Security_Validate_Setting_DataPath()),
                                                        'description' => 'Path to the security module\'s data directory',
                                                        'required' => true,
                                                        'value' => $this->_dataPath));
            
            
            $this->addElement('submit', 'submit', array('label' => 'Submit'));
            return;
        }
        
        $settings = new Zend_Config_Xml($this->_dataPath . DIRECTORY_SEPARATOR . 'settings.xml');
        $settings = $settings->toArray();
        
        foreach ($settings as $name => $params) {
            
            $validators = array(ucfirst($name));
            
            switch ($params['type']) {
                case 'bool':
                    $type = 'checkbox';
                break;
                case 'number':
                    $type = 'text';
                    $validator = array_merge($validators, array('Digits'));
                break;
                case 'string':
                    $type = 'text';
                break;
                case 'text':
                    $type = 'textarea';
                break;
                case 'date':
                    $type = 'text';
                    $validator = array_merge($validators, array('Date'));
                break;
            }
            
            $this->addElement($type, $name, array('label' => $params['label'],
                                                 'description' => $params['description'],
                                                 'required' => $params['required']));
            
            $this->getElement($name)->removeDecorator('Label');
            $this->getElement($name)->addDecorator('description', array('placement' => 'PREPEND'));
            $this->getElement($name)->addDecorator('Label', array('tag' => 'dt'));
            
            if (!empty($params['validators'])) {
            
                foreach ($params['validators'] as $vName => $validate) {
                    
                    $filter = new Zend_Filter_Word_UnderscoreToSeparator('/');
                    $file = ucfirst($filter->filter($vName)) .'.php';
            
                    if (Zend_Loader::isReadable('Security/Validate/Setting/'. $file)) {
                        
                        $class = 'Security_Validate_Setting_'.ucfirst($vName);
                        
                    } elseif (Zend_Loader::isReadable('Zend/Validate/'. $file)) {
                        
                        $class = 'Zend_Validate_'.ucfirst($vName);
                        
                    } else {
                        continue;
                    }
                    
                    $options = (!empty($validate['options'])) ? $validate['options'] : null;
                    
                    $this->getElement($name)->addValidator(new $class($options));
                }
            }
            
            if (false !== strpos($name, 'enable') && true === $this->_isInstall) {
                
                $this->getElement($name)->setAttrib('disabled', 'disabled');
            }
        }
        $this->addElement('submit', 'submit', array('label' => 'Save'));
    }
    
    public function filterElements(array $elements = array())
    {
        $elements = array_flip($elements);
        
        foreach ($this->getElements() as $element) {
            
            if (!isset($elements[$element->getName()])) {
                
                $this->removeElement($element->getName());
            }
        }
        
        $this->addElement('submit', 'submit', array('label' => 'Next'));
    }
}