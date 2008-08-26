<?php

class Security_InstallController extends Zend_Controller_Action
{
    protected $_formNamespace = 'securityFormNamespace';
    
    protected $_parts = array();
    
    public function init()
    {
        $front = Zend_Controller_Front::getInstance();
        $request = $this->getRequest();
        
        if ($request->getActionName() == $front->getDefaultAction() && $request->getActionName() != 'index') {
            $this->_forward('index');
            return;
        }
        
        // Set expiration to 5 minutes
        $session = new Zend_Session_Namespace('SecurityInstall');
        $session->setExpirationSeconds(600);
        
        if (!isset($session->exists)) {
            
            // Session expired or didn't exist, start at index if not already
            if ($request->getActionName() != $front->getDefaultAction()) {
                
                $session->exists = true;
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'index'), 'default');
            }
        }
        
        $session->exists = true;

        if ($request->getActionName() != 'finished') {
            
            try {
                
                if (Zend_Loader::isReadable('Security/System.php')) {
                    if (Security_System::getInstance()->isInstalled()) {
                    
                        $this->getHelper('Redirector')->gotoRoute(array('controller'=>'update', 'action'=>'index'), 'default');
                    }
                }
            } catch (Exception $e) {}
        }
    }
    
    public function indexAction()
    {
        // Intro
        $session = new Zend_Session_Namespace('SecurityInstall');
        $session->unsetAll();
        
        $session->exists = true;
    }
    
    public function stepOneAction()
    {
        // Check if bootstrap is setup properly and options path works
        
        $this->view->path = dirname(dirname(__FILE__)) . '/library';
        
        if (!Zend_Loader::isReadable('Security/Form/Options.php')) {
            
            $form = new Zend_Form();
            $form->addElement('submit', 'submit', array('label'=>'submit'));
            $this->view->form = $form;
            
            if ($this->getRequest()->isPost()) {
                $this->view->errors = array("Security library is missing from the include path");
            }
            
            return;
        }
        
        $form = new Security_Form_Options();
        
        if (!$this->getRequest()->isPost()) {
            
            $optionsPath = dirname(dirname(__FILE__)) . '/data/options.xml';
            $form->getElement('optionsPath')->setValue($optionsPath)->setAttrib('size', strlen($optionsPath));
            $this->view->form = $form;
            return;
            
        } else {

            if ($form->isValid($this->getRequest()->getPost())) {
                
                if (Zend_Loader::isReadable('Security/Install.php')) {
                    
                    $install = new Security_Install();
                    
                    if ($install->bootstrapIsSetup()) {
                        
                        $optionsPath = $form->getValue('optionsPath');

                        if ($install->optionsPathCorrect($optionsPath)) {
                            
                            $this->_setSession('optionsPath', $optionsPath);
                            
                            $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-two'), 'default'); 
                        }
                    }
                    $this->view->errors = $install->getErrors();
                    
                } else {
                    
                    $this->view->errors = array("The security library was not found in your include path");
                }
            }
            
            $form->getElement('optionsPath')->setAttrib('size', strlen($form->getValue('optionsPath')));
        }
        
        $this->view->form = $form;
    }
    
    public function stepTwoAction()
    {
        // Check DB privileges
        $migrationPath = dirname(dirname(__FILE__)) . '/data/migrations';
        
        $form = $this->_getForm();
        $form->buildFromOptionsPath(false, array('migrationPath'));
        
        $form->getElement('migrationPath')->setAttrib('size', strlen($migrationPath))
                                          ->setValue($migrationPath);
        
        if ($this->getRequest()->isPost()) {
            
            $install = new Security_Install();
            
            if ($install->hasRequiredDbAccess($form->getValue('migrationPath'))) {
                
                $this->_setSession('migrationPath', $form->getValue('migrationPath'));
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-three'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $this->view->form = $form;
    }
    
    public function stepThreeAction()
    {
        // Generate Models
        $modelPath = dirname(dirname(dirname(dirname(__FILE__)))) . '/models';
        $schemaPath = dirname(dirname(__FILE__)) . '/data/schema.yml';
        
        $form = $this->_getForm();
        $form->buildFromOptionsPath(false, array('accountTableClass',
                                                 'accountTableAlias',
                                                 'modelPath',
                                                 'schemaPath'));
        
        $form->getElement('modelPath')->setAttrib('size', strlen($modelPath))->setValue($modelPath);
        $form->getElement('schemaPath')->setAttrib('size', strlen($schemaPath))->setValue($schemaPath);
        
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            
            $install = new Security_Install();
            
            if ($install->generateModels($form->getValue('accountTableClass'),
                                         $form->getValue('accountTableAlias'),
                                         $form->getValue('modelPath'),
                                         $form->getValue('schemaPath'))) {
                
                $this->_setSession('accountTableClass', $form->getValue('accountTableClass'));
                $this->_setSession('accountTableAlias', $form->getValue('accountTableAlias'));
                $this->_setSession('modelPath', $form->getValue('modelPath'));
                $this->_setSession('schemaPath', $form->getValue('schemaPath'));
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-four'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $this->view->form = $form;
    }
    
    public function stepFourAction()
    {
        // Add relation to user table
        if ($this->getRequest()->isPost()) {
            
            $install = new Security_Install();
            
            if ($install->hasGroupsRelation($this->_getSession('accountTableClass'))) {
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-five'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $class = $this->_getSession('accountTableClass');
        $this->view->class = $class;
        $identifier = Doctrine::getTable($class)->getIdentifier();
        $this->view->column = ($identifier == 'id') ? strtolower($class) . '_id' : $identifier;
        
        $form = new Zend_Form();
        $form->addElement('submit', 'submit', array('label' => 'Submit'));
        $this->view->form = $form;
    }
    
    public function stepFiveAction()
    {
        // Generate and execute SQL from models
        if ($this->getRequest()->isPost()) {
            
            $install = new Security_Install();
            
            if ($install->executeSqlFromModels($this->_getSession('accountTableClass'), $this->_getSession('migrationPath'))) {
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-six'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $form = new Zend_Form();
        $form->addElement('submit', 'submit', array('label' => 'Submit'));
        $this->view->form = $form;
    }
    
    public function stepSixAction()
    {
        // Create / save options
        $form = $this->_getForm();
        $form->buildFromOptionsPath(false);
        
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            
            $optionsPath = $form->getValue('optionsPath');
        
        } else {
            
            $optionsPath = dirname(dirname(__FILE__)) . '/data/options.xml';
        }
        
        $form->getElement('optionsPath')->setValue($optionsPath);
        
        if (!Zend_Loader::isReadable($optionsPath)) {
            
            $this->view->errors = array("Path is not readable");
            $this->view->form = $form;
            return;
        }
        
        $form->buildFromOptionsPath(false, array('isInstall' => true));
        
        foreach($this->_getSession()->getIterator() as $name => $value) {
            
            if (!in_array($name, array('exists', 'submit'))) {
                
                $form->getElement($name)->setValue($value);
                
                if ($form->getElement($name) instanceof Zend_Form_Element_Text) {
                    
                    $form->getElement($name)->setAttrib('size', strlen($value));
                }
            }
        }
        
        $form->getElement('optionsPath')->setValue($optionsPath)->setAttrib('size', strlen($optionsPath));
            
        if ($this->getRequest()->isPost() && $form->isValid(array_merge($this->getRequest()->getPost(), array('optionsPath', $optionsPath)))) {
            
            $install = new Security_Install();
            
            if ($install->setSecurityOptions($form->getValues())) {
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'finished'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        
        $this->view->form = $form;
    }
    
    public function finishedAction()
    {
        // Done, send to /security/update to update acl list
    }
    
    protected function _generateForm()
    {
        $form = new Security_Form_Options();
        $form->setIsInstall(true);
        if ($optionsPath = $this->_getSession('optionsPath')) {
            $form->setOptionsPath($optionsPath);
        }
        return $form;
    }
    
    protected function _getSession($name = null)
    {
        $session = new Zend_Session_Namespace('SecurityInstall');
        if (null === $name) {
            return $session;
        }
        if (isset($session->{$name})) {
            return $session->{$name};
        }
        return null;
    }
    
    protected function _getForm($method = null)
    {
        if (!Zend_Registry::isRegistered($this->_formNamespace)) {
            
            $form = $this->_generateForm();
            
            if ($method !== null) {
                if ($form->getElement('_method')) {
                    $form->getElement('_method')->setValue($method);
                }
            }
            
            $this->_setForm($form);
        }
        return Zend_Registry::get($this->_formNamespace);
    }
    
    protected function _setForm($form)
    {
        Zend_Registry::set($this->_formNamespace, $form);
    }
    
    protected function _setSession($name, $value)
    {
        $session = new Zend_Session_Namespace('SecurityInstall');
        $session->{$name} = $value;
    }
}