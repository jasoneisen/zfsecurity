<?php

class Security_InstallController extends Security_Controller_Action_Backend
{
    protected $_parts = array();
    
    public function init()
    {
        parent::init();
        
        try {
            
            $secSys = Security_System::getInstance();
            
            Doctrine::getTable('Acl');
            Doctrine::getTable('AclPart');
            Doctrine::getTable('Group');
            Doctrine::getTable('GroupAcl');
            Doctrine::getTable('SecurityOption');
            
            $name = $secSys->getParam('accountTableName');
            Doctrine::getTable('Group'.$name);
            
        } catch (Security_Exception $e) {
            
            // Improper bootstrap/include path
            $this->getHelper('Redirector')->gotoRoute(array('action'=>'setup'));
            
        } catch (Exception $e) {
            
        }
    }
    
    public function setupAction()
    {
        
    }
    
    public function indexAction()
    {
        $directory = dirname(dirname(__FILE__)) . '/install/migrations';
        $migration = new Security_Migration($directory);
        $migration->migrate(1);
        
       $form = $this->_getForm();
       
       if ($this->getRequest()->isPost()) {
           
           if ($this->getRequest()->getPost('submit') != 'Begin') {
           
           } else {
           
               $subForm = "stepOne";
           }
       } else {
       
           $subForm = "intro";
       }
       
       //$this->view->form = $form->getSubForm($subForm);
       $this->view->form = new Security_Form_Login();
    }
    
    public function stepOneAction()
    {
        // User add stuff to bootstrap + check
    }
    
    public function stepTwoAction()
    {
        // Generate Models + check
    }
    
    public function stepThreeAction()
    {
        // Check DB privileges
    }
    
    public function stepFourAction()
    {
        // Run SQL from Models + check
    }
    
    public function stepFiveAction()
    {
        // Scan/Add ACL
    }
    
    public function finishedAction()
    {
        // Done!
    }
    public function testAction()
    {
        
        
        $bldr = new Doctrine_Import_Builder();
        
        $definition = array(
            'className'     =>  'GroupUser',
            'tableName'     =>  'security_group_user',
            'columns'       =>  array(
                'group_id'  =>  array(
                    'unsigned'          =>  true,
                    'primary'           =>  true,
                    'notnull'           =>  true,
                    'autoincrement'     =>  false,
                    'type'              =>  'integer',
                    'length'            =>  4),
                'user_id'   =>  array(
                    'unsigned'          =>  true,
                    'primary'           =>  true,
                    'notnull'           =>  true,
                    'autoincrement'     =>  false,
                    'type'              =>  'integer',
                    'length'            =>  4),
                ),
            'relations'     =>  array(
                array(
                    'class'         =>  'Group',
                    'alias'         =>  'Groups',
                    'type'          =>  Doctrine_Relation::ONE,
                    'local'         =>  'group_id',
                    'foreign'       =>  'id',
                    'onDelete'      =>  'CASCADE',
                    'onUpdate'      =>  'CASCADE'),
                array(
                    'class'         =>  'User',
                    'alias'         =>  'Users',
                    'type'          =>  Doctrine_Relation::ONE,
                    'local'         =>  'user_id',
                    'foreign'       =>  'user_id',
                    'onDelete'      =>  'CASCADE',
                    'onUpdate'      =>  'CASCADE')),
            'options'       =>  array(
                'type'      =>  '',
                'charset'   =>  '',
                'collation' =>  '',
                'collate'   =>  ''
            ),
        );
        
        // Outputs what would normally go in Base*.php
        $gen = $bldr->buildDefinition($definition);
        
        eval($gen);
        
        
        
        Doctrine::getTable('User')->getRecordInstance()->hasMany('Group as Groups', array(
			'local'		=>	'user_id',
			'foreign'	=>	'group_id',
			'refClass'	=>	'GroupUser'));
        
        //die($gen);
        //eval("class GroupUser extends Doctrine_Record {}");
        //
        //$groupUser = new GroupUser();
        //
        //$groupUser->setTableName('security_group_user');
        //
        //$groupUser->hasColumn('group_id', 'integer', 4, array(
        //        'unsigned'          =>  true,
        //        'primary'           =>  true,
        //        'notnull'           =>  true,
        //        'autoincrement'     =>  false));
        //        
        //$groupUser->hasColumn('user_id', 'integer', 4, array(
        //        'unsigned'          =>  true,
        //        'primary'           =>  true,
        //        'notnull'           =>  true,
        //        'autoincrement'     =>  false));
        //
        //$groupUser->hasOne('Group', array(
        //        'local'     =>  'group_id',
        //        'foreign'   =>  'id',
        //        'onDelete'  =>  'CASCADE',
        //        'onUpdate'  =>  'CASCADE'));
        //
        //$groupUser->hasOne('User', array(
        //        'local'     =>  'user_id',
        //        'foreign'   =>  'id',
        //        'onDelete'  =>  'CASCADE',
        //        'onUpdate'  =>  'CASCADE'));
        //
        //
        //die(print_r(Doctrine::getTable('GroupUser')->getExportableFormat()));
        //Doctrine::getTable('GroupUser')->removeColumn('id');
        //Doctrine::getTable('GroupUser')->initDefinition();
        //print_r(Doctrine::getTable('GroupUser')->getIdentifier());
        //die();
        $u = Doctrine::getTable('User')->findOneByUserId(1);
        $u->loadReference('Groups');
        die(print_r($u->toArray(true)));
        
        echo "getTable:\n";
        $gu = Doctrine::getTable('GroupUser')->findOneByUserId(1);
        $gu->loadReference('Groups');
        $gu->loadReference('Users');
        print_r($gu->identifier());
        print_r($gu->toArray());
        die();
        echo "Doctrine_Query:\n";
        $gu = Doctrine_Query::create()->from('GroupUser')->addWhere('GroupUser.user_id = ?')->fetchOne(array(1));
        print_r($gu->identifier());
        
        echo "new GroupUser():\n";
        $gu = new GroupUser();
        
        //print_r($user->identifier());
        //print_r($user->Groups->toArray());
        
        //die(print_r($groupUser->getReferences()));
        
        //print_r($groupUser->toArray());
        print_r(Doctrine::getTable('GroupUser')->getColumns());
        print_r(Doctrine::getTable('User')->getRelations());
        //die();
        
        //$conn = Doctrine::getConnectionByTableName('User');
        //
        //$table = new Doctrine_Table('GroupUser', $conn);
        //
        //$table->setColumn('group_id', 'integer', 4, array(
        //        'unsigned'          =>  true,
        //        'primary'           =>  true,
        //        'notnull'           =>  true,
        //        'autoincrement'     =>  false));
        //
        //$table->setColumn('user_id', 'integer', 4, array(
        //        'unsigned'          =>  true,
        //        'primary'           =>  true,
        //        'notnull'           =>  true,
        //        'autoincrement'     =>  false));
        //
        //$table->getRelationParser()->bind('Group', array(
        //    'type'  =>  Doctrine_Relation::ONE,
        //    'local'     =>  'group_id',
        //    'foreign'   =>  'id',
        //    'onDelete'  =>  'CASCADE',
        //    'onUpdate'  =>  'CASCADE'));
        //
        //$table->getRelationParser()->bind('User', array(
        //    'type'  =>  Doctrine_Relation::ONE,
        //    'local'     =>  'user_id',
        //    'foreign'   =>  'id',
        //    'onDelete'  =>  'CASCADE',
        //    'onUpdate'  =>  'CASCADE'));
        //
        //$table->initDefinition();
        //$table->initIdentifier();
        //
        //$conn->addTable($table);
        //
        //print_r(Doctrine::getTable('GroupUser')->getColumns());
        
        
        //$groupUser = new GroupUser($table);
        
        //print_r($table->getData());
        //echo $table->getComponentName();
        
        
        
        //die(var_dump($g->User));
        //$nTable = Doctrine::getTable('GroupUser');
        //$record = new Doctrine_Record($table);
        //die(var_dump($res = Doctrine::getTable('GroupUser')->findOneByGroupId(2)));
        //print_r($res->toArray());
        //print_r($testTable = Doctrine::getTable('GroupUser')->getColumns());
    }
    
    public function justDoItAction()
    {
        $secSys = Security_System::getInstance();
        
        //if ($secSys->isInstalled()) {
        //    $this->_redirect('/security');
        //}
        
        $exporter = new Doctrine_Export();
        
        $tables = array_values(array_merge(Doctrine::loadModels($secSys->getDir('models'), Doctrine::MODEL_LOADING_CONSERVATIVE),
                              array('Group'.$secSys->getOption('accountTableName'))));
        
        if ($queries = $exporter->exportSortedClassesSql($tables, false)) {
            
            $conn = Doctrine_Manager::getInstance()->getCurrentConnection()->getDbh();
            
            foreach ($queries as $query) {
                    
                $conn->exec($query);
            }
            
            $conn->exec("INSERT INTO `security_option` VALUES ('acl_enabled', 'ACL System', '0', 'Enables/Disables ACL')");
            $conn->exec("INSERT INTO `security_option` VALUES ('system_enabled', 'Security System', '0', 'Enables/Disables the entire system.  This overrides all other enabled values.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('useSecurityErrorController', 'Security Error Controller', '1', 'Enables/Disables the use of the Security module''s error controller for security restrictions.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('activeModelName', 'Active Model Name', 'Security_User', 'The name of the model used with your online user.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('accountTableName', 'Account Table Name', 'User', 'Database table where your accounts are stored.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('identityColumnName', 'Identity Column Name', 'user_email', 'Doctrine aliased column name for identity column. Used to authorize logins.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('identityColumnTitle', 'Identity Column Title', 'Email Address', 'Title to give the identity column.  For use in forms/views.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('credentialColumnName', 'Credential Column Name', 'user_password', 'Doctrine aliased column name for credential column. Used to authorize logins.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('credentialColumnTitle', 'Credential Column Title', 'Password', 'Title to give the credential column.  For use in forms/views.')");
            $conn->exec("INSERT INTO `security_option` VALUES ('credentialColumnTreatment', 'Credential Column Treatment', 'md5(?)', 'Treatment for the credential column during authorization.')");
        }
        
        $this->_redirect('/security');
    }
    
    protected function _generateAcl()
    {
        $gen = new Security_Acl_Generator();
        foreach ($gen->getResources() as $module => $resources) {
            foreach ($resources as $resource) {
                foreach ($gen->getActions($resource) as $action) {
                    echo $module ." ". $resource ." ". $action ."<br>";
                }
            }
        }
    }
    
    public function updateAclAction()
    {
        $gen = new Security_Acl_Generator();
        
        if (!$this->getRequest()->isPost()) {
            
            $modules = array();
            
            foreach ($gen->getResources() as $genModule => $genResources) {
                
                foreach ($genResources as $genResource) {
                    
                    foreach ($gen->getActions($genResource) as $genAction) {
                        
                        if (!$this->_aclExists($genModule, $genResource, $genAction)) {

                            $modules[$genModule]['resources'][$genResource]['privileges'][$genAction]['new'] = true;
                        }
                    }
                }
            }
            
            if (!empty($modules)) {
                $this->view->acl = $modules;
            }
        } else {
            
            $parts = Doctrine_Query::create()
                                     ->select('ap.name')
                                     ->from('AclPart ap INDEXBY ap.name')
                                     ->execute()
                                     ->toArray();
            
            foreach ($gen->getResources() as $genModule => $genResources) {
                
                $module = $this->_addPart($genModule);
                
                foreach ($genResources as $genResource) {
                    
                    $resource = $this->_addPart($genResource);
                    
                    foreach ($gen->getActions($genResource) as $genAction) {
                        
                        $privilege = $this->_addPart($genAction);
                        
                        if (!$this->_aclExists($module->name, $resource->name, $privilege->name)) {
                           
                           $acl = new Acl();
                           $acl->module_id = $module->id;
                           $acl->resource_id = $resource->id;
                           $acl->privilege_id = $privilege->id;
                           $acl->save();
                        }
                    }
                }
            }
        }
    }
    
    protected function _addPart($name)
    {
        if (!isset($this->_parts[$name])) {
            if (!$aclPart = Doctrine::getTable('AclPart')->findOneByName($name)) {
                $aclPart = new AclPart();
                $aclPart->name = $name;
                $aclPart->save();
            }
            $this->_parts[$name] = $aclPart;
        }
        return $this->_parts[$name];
    }
    
    protected function _aclExists($module, $resource, $privilege)
    {
        try {
            Doctrine::getTable('Acl');
        } catch (Doctrine_Exception $e) {
            return;
        }
        // This could be time tested against looping through Security_Acl::getInstance->getAcl()
        return (Doctrine_Query::create()
                                ->from('Acl a')
                                ->innerJoin('a.Module m')
                                ->innerJoin('a.Resource r')
                                ->innerJoin('a.Privilege p')
                                ->addWhere('m.name = ?')
                                ->addWhere('r.name = ?')
                                ->addWhere('p.name = ?')
                                ->fetchOne(array($module, $resource, $privilege))) ? true : false;
    }
    
    protected function _generateForm()
    {
       return new Security_Form_Install();
    }
}