<?php

class SetupController extends Zend_Controller_Action
{
    public function indexAction()
    {
        if ($do = $this->getRequest()->getParam('do')) {
            
            $path = dirname(dirname(dirname(dirname(__FILE__))));
            
            switch($do) {
                
                case 'models':
                    
                    $modelDir   = $path . '/models';
                    $schema     = $path . '/data/schema.yml';
                    
                    try {
                        
                        Doctrine::generateModelsFromYaml($schema, $modelDir);
                        
                    } catch (Exception $e) {
                        
                        $this->view->error = $e->getMessage();
                        break;
                    }
                    
                    $this->view->message = "Models successfully generated";
                    break;
                    
                case 'sql':
                    
                    $modelDir = $path . '/models';
                    
                    $sql = Doctrine::generateSqlFromModels($modelDir);
                    $pdo = Doctrine_Manager::connection()->getDbh();
                    
                    try {
                        
                        $pdo->exec($sql);
                        
                    } catch (Exception $e) {
                        
                        $this->view->error = $e->getMessage();
                        break;
                    }
                    
                    $this->view->message = "Models successfully generated";
                    break;
                
                case 'populate':
                    
                    $fixture = $path . '/data/fixture.yml';
                    
                    $data = new Doctrine_Data();
                    
                    try {
                        
                        $data->importData($fixture, 'yml', array('User'));
                        $data->importData($fixture, 'yml', array('Post'));
                        $data->importData($fixture, 'yml', array('Comment'));
                        
                    } catch (Exception $e) {
                        
                        $this->view->error = $e->getMessage();
                        break;
                    }
                    
                    $this->view->message = "Database successfully populated";
                    break;
            }
        }
    }
}