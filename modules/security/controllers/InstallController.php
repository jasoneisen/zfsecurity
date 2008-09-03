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
    
    protected $_errors = array();
    
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
                
                if (Zend_Loader::isReadable('Security.php')) {
                    if (Security::isInstalled()) {
                    
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
        $this->view->path = dirname(dirname(realpath(__FILE__))) . DIRECTORY_SEPARATOR . 'library';
        $dataPath = dirname(dirname(realpath(__FILE__))) . DIRECTORY_SEPARATOR . 'data';
        
        if (!Zend_Loader::isReadable('Security/Form/Settings.php')) {
            
            
            
            $form = new Zend_Form();
            $form->addElement('text','dataPath', array('label' => 'Data Path',
                                                       'size' => 60,
                                                       'value' => $dataPath));
            $form->addElement('submit', 'submit', array('label'=>'Next'));
            $this->view->form = $form;
            
            if ($this->getRequest()->isPost()) {
                
                $this->_addError("The security library was not found in your include path");
            }
            
            return;
        }
        
        $form = $this->_getForm($dataPath);
        $form->getElement('dataPath')->setValue($dataPath);
        $form->filterElements(array('dataPath'));
        
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            
            try {
                
                if ($secSys = Security::isInitialized()) {
                
                    $this->_setSession('dataPath', $form->getValue('dataPath'));
                        
                    $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-two'), 'default');
                } else {
                    $this->_addError("Security::init() has not been added, or is not being executed");
                }

            } catch (Security_Exception $e) {

                $this->_addError($e->getMessage());
            }
        }
        
        $form->getElement('dataPath')->setAttrib('size', strlen($form->getValue('dataPath')));
        $this->view->form = $form;
    }
    
    public function stepTwoAction()
    {
        // Check DB privileges
        $form = new Security_Form_Settings();
        $form->filterElements(array());
        
        if ($this->getRequest()->isPost()) {
            
            $migrationPath = $this->_getSession('dataPath') . DIRECTORY_SEPARATOR . 'migrations';
            
            if ($this->_runMigration($migrationPath)) {
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-three'), 'default');
                
            }
        }
        $this->view->form = $form;
    }
    
    public function stepThreeAction()
    {
        // Generate Models
        $form = $this->_getForm();
        $form->filterElements(array('accountTableClass', 'accountTableAlias', 'modelPath'));
        
        $modelPath = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'models';
        $form->getElement('modelPath')->setAttrib('size', strlen($modelPath))->setValue($modelPath);
        
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            
            $schemaPath = $this->_getSession('dataPath') . DIRECTORY_SEPARATOR . 'schema.yml';
            
            if ($this->_generateModels($form->getValue('accountTableClass'),
                                       $form->getValue('accountTableAlias'),
                                       $form->getValue('modelPath'),
                                       $schemaPath)) {
                
                $this->_setSession('accountTableClass', $form->getValue('accountTableClass'));
                $this->_setSession('accountTableAlias', $form->getValue('accountTableAlias'));
                $this->_setSession('modelPath', $form->getValue('modelPath'));
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-four'), 'default');
            }
        }
        $this->view->form = $form;
    }
    
    public function stepFourAction()
    {
        // Add relation to user table
        $form = $this->_getForm();
        $form->filterElements(array());
        
        $class = $this->_getSession('accountTableClass');
        
        if ($this->getRequest()->isPost()) {
            
            try{
                
               $relations = Doctrine::getTable($class)->getRelations();

               if (isset($relations['Groups'])) {
                   
                   $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-five'), 'default');
               }

               $this->_addError("No 'Groups' relation found on class '$class'");

           } catch (Exception $e) {

               $this->_addError($e->getMessage());
           }
        }
        
        $identifier = Doctrine::getTable($class)->getIdentifier();
        
        $this->view->class = $class;
        $this->view->column = ($identifier == 'id') ? strtolower($class) . '_id' : $identifier;
        $this->view->form = $form;
    }
    
    public function stepFiveAction()
    {
        // Generate and execute SQL from models
        $form = $this->_getForm();
        $form->filterElements(array());
        
        if ($this->getRequest()->isPost()) {
            
            try {
                
                $export = new Doctrine_Export();
                $sql = $export->exportSortedClassesSql(array('SecurityAcl',
                                                             'SecurityAclPart',
                                                             'SecurityGroup',
                                                             'SecurityGroupAccount',
                                                             'SecurityGroupAcl',
                                                             'SecurityOption'));

                $conn = Doctrine_Manager::connection();

                foreach ($sql[0] as $stmnt) {

                    $conn->exec($stmnt);
                }

                $this->getHelper('Redirector')->gotoRoute(array('action'=>'step-six'), 'default');

            } catch (Exception $e) {

                $this->_addError($e->getMessage());
            }
        }
        
        $this->view->form = $form;
    }
    
    public function stepSixAction()
    {
        die('woo');
        // Create / save options
        $form = $this->_getForm();
            
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            
            try {
                
                $settings = new Zend_Config_Xml($this->_getSession('dataPath') . DIRECTORY_SEPARATOR . 'settings.xml');

                foreach ($form->getValues() as $name => $value) {

                    if ($name != 'submit') {

                        $option = Doctrine::getTable('SecurityOption')->findOneByName($name);

                        if (!$option || $option instanceof Doctrine_Null) {

                            $option = new SecurityOption();

                        }

                        $option->name = $name;
                        $option->value = $value;
                        $option->save();
                    }
                }
                
                $this->getHelper('Redirector')->gotoRoute(array('action'=>'finished'), 'default');

            } catch (Exception $e) {

                $this->_addError($e->getMessage());
            }
        }
        
        $this->view->form = $form;
    }
    
    public function finishedAction()
    {
        // Done, send to /security/update to update acl list
    }
    
    protected function _getForm($dataPath = null)
    {
        
        if (!Zend_Registry::isRegistered($this->_formNamespace)) {
            
            if (null === $dataPath && !$dataPath = $this->_getSession('dataPath')) {
                throw new Security_Exception("Could not get security data path to generate form");
            }
            
            $form = new Security_Form_Settings($dataPath);
            
            if ($vars = $this->_getSession()) {
                
                foreach ($vars as $name => $value) {
                    
                    if ($element = $form->getElement($name)) {
                        
                        if ($element instanceof Zend_Form_Element_Text) {

                            $element->setAttrib('size', strlen($value));
                        }
                        
                        $element->setValue($value);
                    }
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
    
    public function _getErrors()
    {
        return $this->_errors;
    }
    
    public function _hasErrors()
    {
        return !empty($this->_errors);
    }
    
    protected function _addError($msg)
    {
        $this->_errors[] = $msg;
        $this->view->errors = $this->_errors;
    }
    
    protected function _runMigration($migrationPath)
    {
        if (!Zend_Loader::isReadable($migrationPath)) {
    
            $this->_addError("Migrations path '$migrationPath' is not readable");
            return false;
        }
    
        $migration = new Security_Migration($migrationPath);
    
        if ((false === ($version = $migration->getCurrentVersion())) || $version == 0) {
    
            try {
                $migration->migrate(1);
    
            } catch (Exception $e) {
    
                $this->_addError("No CREATE or INSERT access");
                return false;
            }
        }
    
        if ($migration->getCurrentVersion() == 1) {
    
            try {
                $migration->migrate(2);
    
            } catch (Exception $e) {
    
                $this->_addError("No UPDATE access");
                return false;
            }
        }
    
        if ($migration->getCurrentVersion() == 2) {
    
            try {
                $migration->migrate(3);
    
            } catch (Exception $e) {
    
                $this->_addError("No ALTER access");
                return false;
            }
        }
    
        if ($migration->getCurrentVersion() >= 3) {
    
            return true;
        }
    }
    
    protected function _generateModels($accountTable, $alias, $modelPath, $schemaPath)
    {
        if (!Zend_Loader::isReadable($schemaPath)) {
    
            $this->_addError("Schema file is not readable");
            return false;
        }
        
        if (!$string = @file_get_contents($schemaPath)) {
            
            $this->_addError("Schema file could not be opened");
            return false;
        }
        
        if (substr($string, 0, 3) != '---') {
            $this->_addError("Schema file does not contain YML");
            return false;
        }
        
        try {
            $table = Doctrine::getTable($accountTable);
            
        } catch (Exception $e) {
            
            $this->_addError("Invalid table specified");
        }
        
        if (!is_writable($modelPath)) {
            
            $this->_addError("Models path '$modelPath' is not writable.  Please chmod -R 777.");
        }
        
        if (!Zend_Loader::isReadable($schemaPath)) {
            
            $this->_addError("Schema path '$schemaPath' is not readable");
        }
        
        if ($this->_hasErrors()) {
            
            return false;
        }
        
        try {
            
            $column = $table->getIdentifier();
            
            if (is_array($column)) {
                
                $this->_addError("Account table cannot have a compound primary key");
                return false;
            }
            
            $definition = $table->getColumnDefinition($column);
            
            $localColumn = ($column == 'id') ? strtolower($accountTable) . '_id' : $column;
            
            $import = new Doctrine_Import_Schema();
            $definitions = $import->buildSchema($schemaPath, 'yml');
            
            $definitions['SecurityGroupAccount']['columns'][$column] = $definition;
            $definitions['SecurityGroupAccount']['columns'][$column]['autoincrement'] = false;
            $definitions['SecurityGroupAccount']['columns'][$column]['name'] = $localColumn;
            
            $definitions['SecurityGroupAccount']['relations'][$accountTable] = array(
                'class' => $accountTable,
                'local' => $localColumn,
                'foreign' => $column,
                'type' => Doctrine_Relation::ONE,
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE',
                'alias' => $accountTable,
                'key' => md5($localColumn.$column.$accountTable));
            
            $definitions['SecurityGroup']['relations'][$alias] = array(
                'class' => $accountTable,
                'local' => 'group_id',
                'foreign' => $localColumn,
                'type' => Doctrine_Relation::MANY,
                'alias' => $alias,
                'refClass' => 'SecurityGroupAccount',
                'key' => md5('id'.$localColumn.$accountTable.'SecurityGroupAccount'));
            
            $builder = new Doctrine_Import_Builder();
            $builder->setTargetPath($modelPath);
            
            foreach ($definitions as $definition) {
                
                $builder->buildRecord($definition);
            }
                
            return true;

        } catch (Exception $e) {
            
            $this->_addError($e->getMessage());
            return false;
        }
    }
}