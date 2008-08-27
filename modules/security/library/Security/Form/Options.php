<?php

class Security_Form_Options extends Zend_Form
{
    protected $_isInstall = false;
    
    protected $_dataPath = null;
    
    public function init()
    {
        $this->addElement('text', 'dataPath', array('label' => 'Data path', 'required' => true));
        
        $this->addElement('submit', 'submit', array('label' => 'Submit', 'order' => 100));
    }
    
    public function setIsInstall($value = true)
    {
        $this->_isInstall = (bool) $value;
    }
    
    public function isInstall()
    {
        return $this->_isInstall;
    }
    
    public function setDataPath($path)
    {
        $this->_dataPath = $path;
    }
    
    public function getDataPath()
    {
        if (null === $this->_dataPath) {
            throw new Security_Exception("Data path has not been set");
        }
        return $this->_dataPath;
    }
    
    public function buildFromDataPath($querySystem = true, $options = array())
    {
        $optionsPath = $this->getDataPath() . DIRECTORY_SEPARATOR . 'options.xml';
        
        $configs = new Zend_Config_Xml($optionsPath);
        
        $this->removeElement('dataPath');
        
        if ($querySystem === true) {
            $secSys = Security_System::getInstance();
        }
        
        foreach ($configs as $name => $config) {
            
            if (empty($options) || in_array($name, $options)) {
            
                $validators = array();
                
                switch ($config->type) {
                    
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
                
                $this->addElement($type, $name, array('label' => $config->description,
                                                      'validators' => array($validators),
                                                      'required' => $config->required));
                
                $this->addDisplayGroup(array($name), $name .'_group', array('legend' => $config->label));
                
                if (false !== strpos($name, 'enable') && $this->isInstall()) {
                    
                    $this->getElement($name)->setAttrib('disabled', 'disabled');
                }
                
                if ($querySystem === true) {
                    
                    if ($config->type == 'bool' && $secSys->getParam($name)) {
                        
                        $this->getElement($name)->setChecked(true);
                    
                    } elseif ($config->type == 'string') {
                        
                        $this->getElement($name)->setAttrib('size', strlen($secSys->getParam($name))+5)->setValue($secSys->getParam($name));
                            
                    } else {
                        
                        $this->getElement($name)->setValue($secSys->getParam($name));
                    }
                    
                }
            }
        }
    }
}