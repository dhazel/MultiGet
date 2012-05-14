<?php
/**
 * @file
 * Part of a refactoring of https://github.com/Yaffle/MultiGet.
 *
 */


/**
 *
 **/
interface MultiGet_GetInterface 
{
    /**
     * @param string $url
     * @param array|string $postFields
     **/
    public function request($url, $postFields = null);
        
    /**
     * Timeouts are callable functions that will run prior to the processing
     *  of requests.
     * @param callable $callback
     * @param int $delay
     *  The delay period, in microseconds.
     **/
    public function setTimeout($callback, $delay);

    /**
     * Processes the requests.
     *
     * When this completes, all requests will have been processed.
     **/
    public function go();
}

?>
