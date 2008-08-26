<?php

class Security_AccountsController extends Security_Controller_Action_Backend
{
    public function init()
    {
        parent::init();
        $this->view->identityLabel = $this->_secParam('loginIdentityLabel');
        $this->view->identityColumn = $this->_secParam('loginIdentityColumn');
    }
    
    public function indexAction()
    {
        $this->view->accounts = Doctrine_Query::create()
                                                ->from($this->_secParam('accountTableClass') .' a')
                                                ->orderby('a.'.$this->_secParam('loginIdentityColumn'))
                                                ->execute();
    }
    
    public function showAction()
    {
       $this->view->account = $this->_getAccount($this->_getParam('id'));
    }
    
    public function newAction()
    {
       $this->view->form = $this->_getForm('post');
    }
    
    public function createAction()
    {
        $form = $this->_getForm('post');
        
        if ($form->isValid($this->getRequest()->getPost())) {
        
            if ($account = $this->_saveAccount($form->getValues())) {
            
                $this->getHelper('Redirector')->gotoRoute(array('id'=>current($account->identifier())), 'security_account_path');
            }
        }
        
        $this->_setForm($form);
        $this->_forward('new');
    }
    
    public function editAction()
    {
       $this->view->form = $this->_getForm('put');
    }
    
    public function updateAction()
    {
       $form = $this->_getForm('put');
        
        if ($form->isValid($this->getRequest()->getPost())) {
        
            if ($account = $this->_saveAccount($form->getValues(), $this->getRequest()->getParam('id'))) {
            
                $this->getHelper('Redirector')->gotoRoute(array('id'=>current($account->identifier())), 'edit_security_account_path');
            }
        }
        
        $this->_setForm($form);
        $this->_forward('edit');
    }
    
    public function destroyAction()
    {
       
    }
    
    protected function _generateForm()
    {
        $identifier = Doctrine::getTable($this->_secParam('accountTableClass'))->getIdentifier();
        $identityColumn = $this->_secParam('loginIdentityColumn');
                                
        $form = new Security_Form_Account();
        
        if (!$this->getRequest()->isPost()
                && ($id = $this->getRequest()->getParam('id'))
                && $account = $this->_getAccount($id)) {
            
            $form->getSubForm('account')->getElement('identity')->setValue($account->{$identityColumn});
            
            if ($groups = Security_Acl::getInstance()->getGroups()) {
            
                $populate = array();
                
                foreach ($groups as $group) {
                    
                    if (isset($account->Groups[$group->id])) {
                        
                        $populate[$group->id] = true;
                    }
                }
                
                $form->populate(array('groups'=>$populate));
            }
        }
        
        return $form;
    }
    
    protected function _saveAccount($data, $account = null)
    {
        $tableName = $this->_secParam('accountTableClass');
        $identityColumn = $this->_secParam('loginIdentityColumn');
        $credentialColumn = $this->_secParam('loginCredentialColumn');
        $accountIdentifier = Doctrine::getTable($tableName)->getIdentifier();
        
        $groupLink = 'Group' . $tableName;
        $localColumn = ($accountIdentifier == 'id') ? strtolower($tableName) .'_id' : $accountIdentifier;
        
        if ($account === null) {
            
            // This is the only way to set the credential using treatment
            $account = new $tableName();
            $account->$identityColumn = $data['account']['identity'];
            $account->save();
            
        } elseif ($account instanceof Doctrine_Record
                 || (is_numeric($account) && $account = $this->_getAccount($account))) {
            
            $account->unlink('Groups');
            $account->save();
            
        } else {
            return false;
        }
        
        $query = Doctrine_Query::create()
                                 ->update($tableName.' a')
                                 ->set('a.'. $identityColumn, '?', $data['account']['identity'])
                                 ->addWhere('a.'. $accountIdentifier .' = ?', current($account->identifier()));
        
        if (!empty($data['account']['credential'])) {
            
            if ($treatment = $this->_secParam('loginCredentialTreatment')) {
                
                $query->set('a.'. $credentialColumn, $treatment, $data['account']['credential']);
            } else {
                
                $query->set('a.'. $credentialColumn, '?', $data['account']['credential']);
            }
        }
        
        $query->execute();
        
        if (!empty($data['groups'])) {

            $account->link('Groups', array_keys(array_filter($data['groups'])));
        }
        
        $account->refresh();
        
        return $account;
    }
    
    protected function _getAccount($id)
    {
        $tableName = $this->_secParam('accountTableClass');
        $identifier = Doctrine::getTable($tableName)->getIdentifier();
        
        
        return Doctrine_Query::create()
                               ->from($tableName .' a')
                               ->leftJoin('a.Groups g INDEXBY g.id')
                               ->addWhere('a.'. $identifier .' = ?')
                               ->fetchOne(array($id));
    }
}