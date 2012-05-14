<?php
/**
 * @file
 * Part of a refactoring of https://github.com/Yaffle/MultiGet.
 *
 */




/**
 * This may be similar to Node.js EventEmitter
 *
 **/
interface MultiGet_RequestInterface 
{
    /**
     * @param string $eventType
     * @param callable $callback
     **/
    public function on($eventType, $callback);

    /**
     * Emits events
     * @param string $eventType
     * @param mixed ...
     *  Variable arguments supplied to the callback functions.
     **/
    public function emit($eventType/*, $arg1, $arg2, ... */);
}

?>
