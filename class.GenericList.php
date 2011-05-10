<?php
require_once 'class.BasicList.php';
namespace de\hamsta\runtimecache;

/**
 * Description of genericList
 *
 * @author thomas
 */
class GenericList extends BasicList {

    /**
     * Create a List with Validation for Class of '$object'
     * @param \stdClass $object
     */
    public function __construct($class) {
        parent::__construct();
        $this->setValueValidationClass($this->getInitializationClassName($class));
    }

    private function getInitializationClassName($var){
        switch(true){
            case \is_object($var):
                $className = \get_class($var);
                break;
            case \is_string($var):
                $className = $var;
                break;
            default:
                $className = false;
                break;
        }
        return $className;
    }
}