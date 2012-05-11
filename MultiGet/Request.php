<?php


class MultiGet_Request {// similar to EventEmitter !?

    public $url;
    public $postData;
    public $curlOptions;
    public $handle = null;
    public $multiHandle = null;

    public function __construct($url, $postData = null, $curlOptions = null) {
        $this->url = $url;
        $this->postData = $postData;
        $this->curlOptions = $curlOptions;
    }

    private $listeners = array();

    public function on($eventType, $callback) {
        $this->listeners[] = array('eventType' => $eventType, 'callback' => $callback);
        return $this;
    }

    public function emit($eventType/*, $arg1, $arg2, ... */) {
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
