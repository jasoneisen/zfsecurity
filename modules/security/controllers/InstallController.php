<?php

/*
 * This class has copied into it everything in Security_Controller_Action_Backend
 * (which every other controller extends) because on index and step-one the include
 * path may have not been added yet, thus creating a dependency which breaks
 * the installer.
 */
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
        
        // Adds partial path to the view if it hasn't been already
        // Copied from backend
        $view = Zend_Layout::getMvcInstance()->getView();
        
        foreach ($view->getScriptPaths() as $path) {
            
            if (false !== strpos($path, 'security/views/scripts/')) {
                
                $setPath = dirname($path) .'/partials/';
                
            } elseif (false !== strpos($path, 'security/views/partials/')) {
                
                $dontSet = true;
            }
        }
        
        if (!isset($dontSet)) {
            
            $view->addScriptPath($setPath);
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
            
            $dataPath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'data';
            $form->getElement('dataPath')->setValue($dataPath)->setAttrib('size', strlen($dataPath));
            $this->view->form = $form;
            return;
            
        } else {

            if ($form->isValid($this->getRequest()->getPost())) {
                
                if (Zend_Loader::isReadable('Security/Install.php')) {
                    
                    $install = new Security_Install();
                    
                    if ($install->bootstrapIsSetup()) {
                        
                        $dataPath = $form->getValue('dataPath');

                        if ($install->dataPathCorrect($dataPath)) {
                            
                            $this->_setSession('dataPath', $dataPath);
                            
                            $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-two'), 'default'); 
                        }
                    }
                    $this->view->errors = $install->getErrors();
                    
                } else {
                    
                    $this->view->errors = array("The security library was not found in your include path");
                }
            }
            
            $form->getElement('dataPath')->setAttrib('size', strlen($form->getValue('dataPath')));
        }
        
        $this->view->form = $form;
    }
    
    public function stepTwoAction()
    {
        // Check DB privileges
        $migrationPath = $this->_getSession('dataPath') . DIRECTORY_SEPARATOR . 'migrations';
        
        if ($this->getRequest()->isPost()) {
            
            $install = new Security_Install();
            
            if ($install->hasRequiredDbAccess($migrationPath) {
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-three'), 'default');
                
            } else {
                
                $this->view->errors = $install->getErrors();
            }
        }
        $form = new Zend_Form();
        $form->addElement('submit', 'submit', array('label'=>'submit'));
        $this->view->form = $form;
    }
    
    public function stepThreeAction()
    {
        // Generate Models
        $modelPath = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'models';
        $schemaPath = $this->_getSession('dataPath') . DIRECTORY_SEPARATOR . 'schema.yml';
        
        $form = $this->_getForm();
        $form->buildFromDataPath(false, array('accountTableClass',
                                                 'accountTableAlias',
                                                 'modelPath'));
        
        $form->getElement('modelPath')->setAttrib('size', strlen($modelPath))->setValue($modelPath);
        
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            
            $install = new Security_Install();
            
            if ($install->generateModels($form->getValue('accountTableClass'),
                                         $form->getValue('accountTableAlias'),
                                         $form->getValue('modelPath'),
                                         $schemaPath)) {
                
                $this->_setSession('accountTableClass', $form->getValue('accountTableClass'));
                $this->_setSession('accountTableAlias', $form->getValue('accountTableAlias'));
                $this->_setSession('modelPath', $form->getValue('modelPath'));
                
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
            
            if ($install->executeSqlFromModels($this->_getSession('accountTableClass'), $this->_getSession('dataPath'))) {
                
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
        $form->buildFromDataPath(false);
        
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
        
        $form->buildFromDataPath(false, array('isInstall' => true));
        
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