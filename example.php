<?

  require_once "MultiGet_GetInterface.php";
  require_once "MultiGet_Get.php";
  require_once "MultiGet_RequestInterface.php";
  require_once "MultiGet_Request.php";

  $hashTags = array('php', 'javascript');
  $mget = new MultiGet_Get();

  foreach ($hashTags as $tag) {
    $url = 'http://search.twitter.com/search.json?q=' . urlencode($tag) . '&rpp=5&include_entities=true&with_twitter_user_id=true&result_type=mixed';
    $mget->request($url)
    ->on('success', function ($content, $url, $handle) use ($tag) {
      $x = json_decode($content);
      echo '<h1>#' . $tag . '</h1>';
      foreach ($x->results as $i => $tweet) {
        echo $tweet->text . '<br>';
      }
    })
    ->on('error', function ($url, $handle) {
      echo 'error while downloading ' . $url;
    });
  }
  $mget->go();

?>
