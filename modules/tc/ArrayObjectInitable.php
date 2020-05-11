<?php

/**
 * @desc This trait includes a function to initialize data classes from associative arrays
 * enforcing an object type specification specified in a variable. It is recommended to use this
 * in __construct() of those classes.
 * @author User
 *
 */
trait ArrayObjectInitable
{
    private function init($data, $typeHints = [])
    {
        $reflect = new ReflectionClass(__CLASS__);
        if (is_array($data)) {
            //echo '<pre>'.__CLASS__." conv array: \n";
            foreach ($data as $key => $val) { //this comes from internal API callers usually: mixed array/object content
                //echo 'key/data: ' . $key . ' ' . var_export($val, true) . "\n";
                
                //Only store predefined properties in our objects (don't be lazy, fully model the API!)
                if ( !$reflect->hasProperty($key) ) {
                    echo __CLASS__.' uknown property '.$key.' ignored';
                    continue;
                }
                
                //Check if it's in the type hints array, if so, make it an object
                if (array_key_exists($key, $typeHints)) {
                    $classname = $typeHints[$key];
                    $this->{$key} = new $classname($val); //this should logically call it's own init() via this trait, if necessary
                    continue;
                }
                
                //By default just copy it over
                $this->{$key} = $val;
            }
        }
        elseif ( is_object($data) ) { //if it's an object just go through properties and copy over as much as possible
            foreach($reflect->getProperties() as $reflectprop) {
                $name = $reflectprop->getName();
                if ( property_exists($data,$name) ) {
                    if ( array_key_exists($name,$typeHints) ) {
                        if ( substr($typeHints[$name],-2) == '[]') { //this is an array of objects
                            if (is_array($data->{$name}) ) { //could be empty or malformed, ignore it if it is
                                $classname = substr($typeHints[$name],0,-2);
                                $this->{$name} = [];
                                foreach($data->{$name} as $item) { //we know it's an array
                                    $this->{$name}[] = new $classname($item);
                                }
                            }
                        }
                        else {
                            $classname= $typeHints[$name];
                            $this->{$name} = new $classname($data->{$name});
                            continue;
                        }
                    }
                    //by default just copy it over
                    $this->{$name} = $data->{$name};
                }
            }
        }
        else {
            die('WUT?');
        }
    }
}
