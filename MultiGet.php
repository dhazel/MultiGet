<?php
/**
 * @file
 * Part of a refactoring of https://github.com/Yaffle/MultiGet.
 *
 */


/**
 * $mget = new MultiGet(3);
 * $url = '...';
 * $userVar = '...';
 * $curlOptions = array(...);
 * $mget->request($url, $postFields)
 * ->setCurlOptions($curlOptions)
 * ->on('success', function ($content, $url, $handle) use ($userVar) {
 * 
 * })
 * ->on('error', function ($url, $handle) {
 * 
 * })
 * ->on('complete', function ($content, $url, $handle) {
 * 
 * });
 * 
 * $mget.go();
 **/
class MultiGet 
implements MultiGetInterface
{
    /**
     * The maximum number of requests to process simultaneously
     * @var int
     **/
    public $maxRequests;

    /**
     * @var array
     * @see curl_setopt()
     **/
    public $curlOptions;

    /**
     * @var array
     **/
    private $requests;

    /**
     * Used when count(requests) > maxRequests. 
     * Note: order of loading is not guaranteed.
     * @var array
     **/
    private $queue = array();

    /**
     * @var array
     **/
    private $timeouts = array();

    /**
     * "success" event
     **/
    const SUCCESS = 'success';

    /**
     * "error" event
     **/
    const ERROR = 'error';

    /**
     * "complete" event
     **/
    const COMPLETE = 'complete';

    /**
     * Used for type-checking
     * @var array
     **/
    public static $EVENTTYPES = array(
        'SUCCESS' = self::SUCCESS,
        'ERROR' => self::ERROR,
        'COMPLETE' => self::COMPLETE,
    );

    /**
     * @param int $maxRequests
     **/
    public function __construct($maxRequests = 4) 
    {
        $this->maxRequests = $maxRequests;
        $this->requests = array();
    }

    /**
     * @param MultiGet_Request $x
     **/
    private function _request(MultiGet_Request $x) 
    {
        if (count($this->requests) < $this->maxRequests) {
            $multiHandle = curl_multi_init();
            $handle = curl_init();
           
            curl_setopt($handle, CURLOPT_URL, $x->url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
            if ($x->postData !== null) {
                curl_setopt($handle, CURLOPT_POST, TRUE);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $x->postData);
            }

            curl_setopt($handle, CURLOPT_HEADER, TRUE);
            curl_setopt($handle, CURLOPT_TIMEOUT, 45);//!!!?
            
            if ($this->curlOptions) {
                curl_setopt_array($handle, $this->curlOptions);
            }
            if ($x->curlOptions) {
                curl_setopt_array($handle, $x->curlOptions);
            }

            curl_multi_add_handle($multiHandle, $handle);

            $x->multiHandle = $multiHandle;
            $x->handle = $handle;
            $this->requests[] = $x;
        } else {
            $this->queue[] = $x;
        }
    }

    /**
     * @param array $curlOptions
     **/
    public function setCurlOptions(array $curlOptions) 
    {
        $this->curlOptions = $curlOptions;
    }

    /**
     * @param string $url
     * @param array|string $postFields
     **/
    public function request($url, $postFields = null) 
    {
        $postData = null;
        if (isset($postFields) && is_array($postFields)) {
            $postData = '';
            foreach ($postFields as $k => $v) {
                $postData .= ($postData !== '' ? '&' : '');
                $postData .= urlencode($k) . '=' . urlencode($v);
            }
        } else {
          $postData = $postFields;
        }
        $x = new MultiGet_Request($url, $postData, $curlOptions);
        $this->_request($x);
        return $x;
    }
    
    /**
     * Timeouts are callable functions that will run prior to the processing
     *  of requests.
     * @param callable $callback
     * @param int $delay
     *  The delay period, in microseconds.
     **/
    public function setTimeout($callback, $delay) 
    {
        $this->timeouts[] = array(
            'callback' => $callback,
            'delay' => $delay,
            'from' => microtime(true)
        );
    }

    /**
     * Processes the requests.
     *
     * When this completes, all requests will have been processed.
     **/
    public function go() 
    {
        while (count($this->requests) > 0 || count($this->timeouts) > 0) {
        
        //echo count($this->requests) . ' / ' . count($this->timeouts) . " - r/t \n";
            // check timeouts...
            $i = 0;
            $ts = microtime(true);
            while ($i < count($this->timeouts)) {
                $t = $this->timeouts[$i];
                if ($t['from'] + $t['delay'] < $ts) {
                    $t = $this->timeouts[$i];
                    array_splice($this->timeouts, $i, 1);

                    $args = array();//?
                    call_user_func_array($t['callback'], $args);

                    usleep(1000);
                    continue 2; //!
                }
                $i++;
            }
            //...

            $active = 1;
            while ($active > 0) {
                $i = count($this->requests);
                while ($i > 0 && $active > 0) {
                    $i--;
                    $mrc = curl_multi_exec($this->requests[$i]->multiHandle, $active);
                }
                if ($active > 0) {
                    usleep(1000);
                    continue 2; //!
                }
            }

            $completed = $this->requests[$i];
            $content = curl_multi_getcontent($completed->handle);
            
            // http://stackoverflow.com/questions/4017911/curl-and-redirects-returning-multiple-headers
            $curlInfo = curl_getinfo($completed->handle);
            $headerSize = $curlInfo["header_size"];
            $responseHeaders = substr($content, 0, $headerSize);
            $content = substr($content, $headerSize);
            //?

            curl_multi_remove_handle($completed->multiHandle, $completed->handle);
            curl_multi_close($completed->multiHandle);

            $this->requests[$i] = $this->requests[count($this->requests) - 1];
            array_pop($this->requests);

            if ((count($this->requests) < $this->maxRequests) && (count($this->queue) > 0)) {
                $x = array_pop($this->queue);
                $this->_request($x);      
            }

            if (curl_error($completed->handle)) {
                $completed->emit(self::ERROR, curl_error($completed->handle), $completed->url, $completed->handle);
            } else {
                $completed->emit(self::SUCCESS, $content, $completed->url, $completed->handle);          
            }
            $completed->emit(self::COMPLETE, $content, $completed->url, $completed->handle, $responseHeaders);

            curl_close($completed->handle);
        }
    }
}

?>
