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
class MultiGet_Request 
{

    /**
     * @var string
     **/
    public $url;

    /**
     * @var array|string
     **/
    public $postData;

    /**
     * @var array
     * @see curl_setopt()
     **/
    public $curlOptions;

    /**
     * CURL handle
     * @var resource
     **/
    public $handle = null;

    /**
     * CURL multi handle
     * @var resource
     **/
    public $multiHandle = null;

    /**
     * The array of callbacks
     * @var array
     **/
    private $listeners = array();

    /**
     * @param string $url
     * @param array|string $postData
     * @param array $curlOptions
     **/
    public function __construct($url, $postData = null, $curlOptions = null) 
    {
        $this->url = $url;
        $this->postData = $postData;
        $this->curlOptions = $curlOptions;
    }

    /**
     * @param string $eventType
     * @param callable $callback
     **/
    public function on($eventType, $callback) 
    {
        if ( ! in_array($eventType, MultiGet::EVENTTYPES) ) {
            throw new Exception('The event type "'.$eventType.'" is not a valid event!');
        }
        $this->listeners[] = array('eventType' => $eventType, 'callback' => $callback);
        return $this;
    }

    /**
     * Emits events
     * @param string $eventType
     * @param mixed ...
     *  Variable arguments supplied to the callback functions.
     **/
    public function emit($eventType/*, $arg1, $arg2, ... */) 
    {
        if ( ! in_array($eventType, MultiGet::EVENTTYPES) ) {
            throw new Exception('The event type "'.$eventType.'" is not a valid event!');
        }
        $args = array_slice(func_get_args(), 1);
        $candidates = array_slice($this->listeners, 0);
        for ($i = 0; $i < count($candidates); $i++) {
          if ($candidates[$i]['eventType'] == $eventType) {
            call_user_func_array($candidates[$i]['callback'], $args);
          }
        }
        return $this;
    }
}

?>
