<?

/*
 * Wrapper class around the Patent API
 *  http://code.google.com/apis/patentsearch/v1/
ver: 1.0.0 

settings:
	 Plugin your url, your email, and put on a cron to run once a day
 
Blake B. Howe
http://blakebbhowe.com
*/

class PatentSearch {
	/**
	 * Json only because its easy
	 * @var string
	 */
	var $type = 'json';
	var $api_key = '';
	

	/**
	 * @var string
	 */
	var $query='';
	
	var $start= '';
	
	/**
	 * @var array
	 */
	var $responseInfo=array();
	

	 var $headers = array('Accept-Language: en-us,en;', "Connection: close");

	 
	 var $jsonobject = '';
	
	/**
	* @param string $query optional
	*/
	function PatentSearch($query=false,$key,$start) {
	     if(empty($key)){
            throw new Exception('api can\'t be empty');
        }
        if(empty($query)){
            throw new Exception('query can\'t be empty');
        }

	    $this->api_key = $key;
		$this->query = $query;
		$this->start = $start;
	}
	
	


	
	/**
	* Build and perform the query, return the results.
	* @param $reset_query boolean optional.
	* @return object
	*/
	
	
	function get_data() {
	
	
  	    $query =  $this->query;
		$request = $this->buildQuery($query);

		$this->jsonobject=  $this-> objectify($this->process($request));
		
		return $this->jsonobject;
	}
	
	
	/**
	 * Internal function where all the juicy curl fun takes place
	 * this should not be called by anything external unless you are
	 * doing something else completely then knock youself out.
	 * @access private
	 * @param string $url Required. API URL to request
	 * @param string $postargs Optional. Urlencoded query string to append to the $url
	 */
	function process($url, $postargs=false) {
	

		$ch = curl_init($url);
		 if($postargs !== false) {
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $postargs);
        }
         
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $response = curl_exec($ch);
        $this->responseInfo=curl_getinfo($ch);
		
        curl_close($ch);
         
		
		
        if( intval( $this->responseInfo['http_code'] ) == 200 )
			return $response;    
        else
            return false;
	}
	
	/**
	 * Function to prepare data for return to client
	 * @access private
	 * @param string $data
	 */
	function objectify($data) 
	{
	
	return (object) json_decode($data);
		
	}
	

		
/**
	 * Function to write out the cool/next previous links
	 * @access public
	 * @param int $pageno - index of the pages array
	
*/
	 
function writePagination($start)
{



//if there are no results display no link
$xml = $this->jsonobject;
$d = get_object_vars($xml->responseData->cursor);
$num = $d['estimatedResultCount'];
if (empty($num)) return;

//spit out the pagination

echo '<div class="pagination">';	


//unless its 0 you can always have previous link
if ($start != 0){
	
	$index =  $start - 8;
	
	$request = "?page=" . $index . "&input=" . trim($this->query);
	echo '<a  href="' .$request. '"><strong> Previous Page</strong></a>';
	echo '&nbsp;&nbsp;';
	}
	
	//next link
	
	$index =  $start + 8;
	$request = "?page=" . $index . "&input=" . trim($this->query);
	
	echo '<a  href="' .$request. '"><strong> Next Page</strong></a>';
	echo '&nbsp;&nbsp;';
	echo '<br><br></div>';
			
}
		
		
		
		
	/**
		 *	Writes out the serach results. takes a string of XML from the google API
		 *
		 *	
		 *	@param XML - string -  the xml feed from google
		 *  
		 
		 
		 */
	


function writeSearchResults()
{
		
		
		$xml = $this->jsonobject;
		$output = '';
	
		
		$d = get_object_vars($xml->responseData->cursor);
		
		 
		 
		 if (empty($xml->responseData))
		 {echo '<br><br>No results returned<br><br>';}
	    
		
		
		//if there are no results display no link
		$xml = $this->jsonobject;
		$d = get_object_vars($xml->responseData->cursor);
		$num = $d['estimatedResultCount'];
		if (empty($num)) return;
				
				
		foreach ($xml->responseData as $key)
		{
				
		 if (count($key) > 1)
		 {

		 
			for($i=0;$i< count($key);$i++)
			{
			
			if ( $i&1 )
			{
			$output .= '<div><p>';
			}
			else
			{
             $output .=  '<div class="even"><p>';
			}
				
			  $output .= ' <a   href="'. $key[$i]->unescapedUrl . '" ta rget="_blank">' .$key[$i]->title .  ' </a><br \> <br\> ' ;
			  $output .= $key[$i]->content . '<br />';
	          $output .= '<span>'.$key[$i]->unescapedUrl.'<span>';
              $output .= '</p></div>';
			

			}

		}
		
		
	    
		}	
		  echo $output;	
		  
		
			
}
		

function closeQuery()
{
	$pageindex = 1;

}	

	
function buildQuery(){

	$ip= $_SERVER['REMOTE_ADDR'];
		
	$request  = 'https://ajax.googleapis.com/ajax/services/search/patent?';
		
	if (empty($start)){
		$request .= 'v=1.0&rsz=8&start='. '0'  .'&q=' . urlencode($this->query) . '&key='. $this->api_key . '&userip=' . $ip;;
	}
	else{
		$request .= 'v=1.0&rsz=8&start='. $this-start  .'&q=' . urlencode($this->query) . '&key='.$this->api_key .'&userip=' . $ip;;
	}
	return $request;
	
}
	

 
	
}

?>