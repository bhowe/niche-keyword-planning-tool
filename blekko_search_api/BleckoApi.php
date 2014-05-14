<?



/**
 * Wrapper class around the Blecko API
 *  Need blogs, facebook, and twitter
 */
class BlekkoSearch {
	/**
	 * Json only because its easy
	 * @var string
	 */
	var $type = 'json';
	var $apikey = '******';
	var $facebookurl = '';
	var $twitterurl = '';
	var $blogurl = '';
	
	

	/**
	 * Internal function where all the juicy curl fun takes place
	 * this should not be called by anything external unless you are
	 * doing something else completely then knock youself out.
	 * @access private
	 * @param string $url Required. API URL to request
	 * @param string $postargs Optional. Urlencoded query string to append to the $url
	 */
	function process($url) {
			
		
		// sendRequest
		// note how referer is set manually
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, 'www.psssoftware.net');
		$body = curl_exec($ch);
		curl_close($ch);
		
		// now, process the JSON string
		$json = json_decode($body);
		// now have some fun with the results...
		
		//var_dump($json);
		
		return $json;	
		
	}




function getBlogResults($query)
{

$q = str_replace(' ','+',$query);

$q = '"' . $q . '"';

	
	 $url = 'http://blekko.com/ws/?q='.$q .'+/json+/blogs&auth=' . $apikey;
	 
	// echo $url . "</br>";
	 
     $blogurl = '<a href = "http://blekko.com/ws/?q='.$q .'+/blogs">Blog Results</a>';
     $json = $this->process($url);
     sleep(1);
	return $json->universal_total_results;

}


function getTwitterResults($query)
{

	 $q = str_replace(' ','+',$query);
     $q = '"' . $q . '"';
	 $url = 'http://blekko.com/ws/?q='.$q .'+/json+/twitter.com&auth=' . $apikey;
     $blogurl = '<a href = "http://blekko.com/ws/?q='.$q .'+/twitter.com">Twitter Results</a>';
     $json = $this->process($url);
     sleep(1);
	return $json->universal_total_results;




}

function getFacebookResults($query)
{

      $q = str_replace(' ','+',$query);
     $q = '"' . $q . '"';
	 $url = 'http://blekko.com/ws/?q='.$q .'+/json+/facebook.com&auth=' . $apikey;
     $blogurl = '<a href = "http://blekko.com/ws/?q='.$q .'+/facebook.com">Facebook</a>';
     $json = $this->process($url);
     sleep(1);
	return $json->universal_total_results;

}

	
 
	
}