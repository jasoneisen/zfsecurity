<?php
 
class App_Form_Doctrine extends Zend_Form
{
    /**
     * All of doctrines supported column types
     */
    protected $_fieldTypes = array('boolean'    =>  'Checkbox',
                                   'integer'    =>  'Text',
                                   'float'      =>  'Text',
                                   'decimal'    =>  'Text',
                                   'string'     =>  array('Text', 'Textarea'),
                                   'time'       =>  'Text',
                                   'date'       =>  'Text',
                                   'enum'       =>  'Select',
                                   'array'      =>  null,
                                   'object'     =>  null,
                                   'blob'       =>  null,
                                   'clob'       =>  null,
                                   'timestamp'  =>  null,
                                   'gzip'       =>  null);
    
    protected $_ignoreColumns = array('submit', '_method');
    
    protected $_ignoreRelations = array();
    
    protected $_columnElements = array();
    
    protected $_withRelations = true;
    
    public function generateFromTable($table, $withRelations = null)
    {
        if (null !== $withRelations) {
            $this->_withRelations = $withRelations;
        }
        
        if (!$table instanceof Doctrine_Table) {
            $table = Doctrine::getTable($table);
        }
        
        $tableName = $table->getOption('name');
        $tableLabel = ($table->getOption('comment')) ? $table->getOption('comment') : $tableName;
        
        if ($this->_withRelations && $relations = $table->getRelations()) {
            foreach ($relations as $alias => $relation) {
                $this->_ignoreColumns[] = $relation->getLocal();
                $definition = $relation->toArray();
                if (!empty($definition['refTable'])) {
                    $this->_ignoreRelations[] = $definition['refTable']->getOption('name');
                }
            }
        }
        
        $primary = $table->getIdentifier();
        $elements = array();
        $elementOrder = 0;
        $displayGroupOrder = 1;
        
        foreach ($table->getColumns() as $name => $definition) {
            
            if (in_array($name, (array) $primary)) {
                continue;
            }
            if (in_array($name, $this->_ignoreColumns)) {
                $relationOrder[$name] = $elementOrder++;
                continue;
            }
            
            if ($element = $this->_generateElement($name, $definition)) {
                
                $element->setOrder($elementOrder++);
                $this->addElement($element);
                $elements[] = $name;
            }
        }
        
        if (true === $this->_withRelations) {
        
            foreach ($relations as $alias => $relation) {
                
                if (in_array($alias, $this->_ignoreRelations)) {
                    continue;
                }
                
                $rTable = $relation->getTable();
                $rIdentifier = $rTable->getIdentifier();
                
                if (is_array($rIdentifier))
                    continue;
                
                // One to Many
                if ($relation instanceof Doctrine_Relation_LocalKey) {
                    
                    $options = array('' => '------');
                    foreach ($rTable->findAll() as $row) {
                        $options[$row->$rIdentifier] = (string) $row;
                    }
                    
                    $elements[] = $alias;
                    $this->addElement('select', $alias, array('label' => $alias,
                                                              'multiOptions' => $options,
                                                              'order' => $relationOrder[$relation->getLocal()]));
                
                // Many to One
                } elseif ($relation instanceof Doctrine_Relation_ForeignKey) {
                
                    // I don't think we need this
                    $this->_ignoreRelations[] = $alias;
                
                // Many to Many OR Nested
                } elseif ($relation instanceof Doctrine_Relation_Association ||
                          $relation instanceof Doctrine_Relation_Nest) {
                    
                    $options = array();
                    foreach ($rTable->findAll() as $row) {
                        $options[$row->$rIdentifier] = (string) $row;
                    }
                    
                    $legend = ($relation instanceof Doctrine_Relation_Nest) ? 'Parent '. $alias : $alias;
                    
                    $this->addElement('multiCheckbox', $alias, array('multiOptions' => $options));
                    $this->addDisplayGroup(array($alias), $alias.'group', array('legend' => $legend,
                                                                                'order' => $displayGroupOrder++));
                    
                }
            }
        }
        
        $this->addDisplayGroup($elements, $tableName, array('legend' => $tableLabel, 'order' => 0));
        $this->addElement('submit', 'submit', array('label' => 'Submit', 'order' => $displayGroupOrder));
        return $this;
    }
    
    public function generateFromRecord(Doctrine_Record $record, $withRelations = null)
    {
        $this->generateFromTable($record->getTable(), $this->_withRelations);
        $this->populate($record);
        return $this;
    }
    
    public function populate($values)
    {
        if ($values instanceof Doctrine_Record) {
            
            $record = $values;
            $values = $record->toArray();
            
            foreach ($record->getTable()->getRelations() as $alias => $relation) {
                
                $identifier = $relation->getTable()->getIdentifier();
                
                if ($relation instanceof Doctrine_Relation_LocalKey) {
                    
                    parent::populate(array($alias => $record->$alias->$identifier));
                    
                } elseif ($relation instanceof Doctrine_Relation_Association ||
                    $relation instanceof Doctrine_Relation_Nest) {
                    
                    $options = array();
                    foreach ($record->$alias as $option) {
                        $options[] = $option->$identifier;
                    }
                    parent::populate(array($alias => $options));
                }
                unset($values[$alias]);
            }
        }
        parent::populate($values);
    }
    
    // This is necessary until $record->synchronizeWithArray() does link() and unlink()
    public function save(Doctrine_Record $record)
    {
        $relations = $record->getTable()->getRelations();
        
        foreach ($this->getElements() as $name => $element) {
            
            if (!in_array($name, $this->_ignoreColumns) &&
                !in_array($name, array_keys($relations))) {
                    
                $record->$name = $element->getValue();
            }
        }
        
        try {
            Doctrine_Manager::connection()->beginTransaction();
            
            foreach ($relations as $alias => $relation) {
                
                if (!in_array($alias, $this->_ignoreRelations)) {
                    
                    if ($relation instanceof Doctrine_Relation_LocalKey) {
                        $column = $relation->getLocal();
                        $record->$column = $this->getElement($alias)->getValue();
                        unset($relations[$alias]);
                    }
                }
            }
            
            $record->save();
            
            foreach ($relations as $alias => $relation) {
                
                if (!in_array($alias, $this->_ignoreRelations)) {
                    
                    $record->unlink($alias);
                    $record->link($alias, $this->getElement($alias)->getValue());
                }
            }
            
            return Doctrine_Manager::connection()->commit();
            
        } catch (Exception $e) {
            Doctrine_Manager::connection()->rollback();
            $this->addErrorMessage($e->getMessage());
            return false;
        }
    }
    
    public function ignoreRelation($name, $ignore = true)
    {
        if (false === $ignore && in_array($name, $this->_ignoreRelations)) {
            foreach(array_keys($this->_ignoreRelations, $name) as $key) {
                unset($this->_ignoreRelations[$key]);
            }
        } elseif (true === $ignore && !in_array($name, $this->_ignoreRelations)) {
            $this->_ignoreRelations[] = $name;
        }
    }
    
    public function ignoreRelations($relations = array(), $ignore = true)
    {
        if (empty($relations)) {
            if (true === $ignore) {
                $this->_withRelations = false;
            } else {
                $this->_ignoreRelations = array();
                $this->_withRelations = true;
            }
        } else {
            foreach ($relations as $relation) {
                $this->ignoreRelation($relation, $ignore);
            }
        }
    }
    
    public function ignoreColumn($name, $ignore = true)
    {
        if (false === $ignore && in_array($name, $this->_ignoreColumns)) {
            foreach(array_keys($this->_ignoreColumns, $name) as $key) {
                unset($this->_ignoreColumns[$key]);
            }
        } elseif (true === $ignore && !in_array($name, $this->_ignoreColumns)) {
            $this->_ignoreColumns[] = $name;
        }
    }
    
    public function ignoreColumns($columns = array(), $ignore = true)
    {
        if (empty($columns)) {
            if (false === $ignore) {
                $this->_ignoreColumns = array();
            }
        } else {
            foreach ($columns as $column) {
                $this->ignoreColumn($column, $ignore);
            }
        }
    }
 
    protected function _generateElement($name, $definition)
    {
        if (empty($this->_columnElements[$name]) || !$type = $this->_columnElements[$name]) {
            
            if (!isset($this->_fieldTypes[$definition['type']]) ||
                null === $this->_fieldTypes[$definition['type']]) {
            
                return null;
            }
            
            if ($definition['type'] == 'string') {
                
                $key = (empty($definition['length']) || $definition['length'] > 255) ? 1 : 0;
                $type = $this->_fieldTypes[$definition['type']][$key];
            } else {
                $type = $this->_fieldTypes[$definition['type']];
            }
        }
        
        if (empty($definition['comment'])) {
            
            $filter = new Zend_Filter_Word_UnderScoreToSeparator();
            $label = ucfirst($filter->filter($name));
        } else {
            $label = $definition['comment'];
        }
        $class = 'Zend_Form_Element_' . $type;
 
        $element = new $class($name, array(
            'required' => (!empty($definition['notnull']) ? $definition['notnull'] : false),
            'label' => $label,
            ));
 
        if ($element instanceof Zend_Form_Element_Multi && !empty($definition['values'])) {
 
            $element->addMultiOptions($definition['values']);
        }
        return $element;
    }
}