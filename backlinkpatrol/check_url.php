<?php

/*
 *  Checks to see if a URL is indexed in google
 *  Keep in mind this will get your IP banned or a captcha throw up if not ran slow.
 *  It does randomize agents fo r mutiple urls.
ver: 1.0.0 
settings:
	Plugin url you want checked
	Blake B. Howe
http://blakebbhowe.com
*/
	
	$url = ($_GET['url']) ? $_GET['url'] : $_POST['url'];
	$email = ($_GET['email']) ? $_GET['email'] : $_POST['email'];
	$agents[] ='Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; WOW64; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; Media Center PC 5.0';
	$agents[] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0';
	$agents[] = 'Opera/9.63 (Windows NT 6.0; U; ru) Presto/2.1.1';
	$agents[] = 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5';
	$agents[] = 'Mozilla/5.0 (X11; U; Linux i686 (x86_64); en-US; rv:1.8.1.18) Gecko/20081203 Firefox/2.0.0.18';
	$agents[] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.16) Gecko/20080702 Firefox/2.0.0.16';
	$agents[] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-us) AppleWebKit/525.27.1 (KHTML, like Gecko) Version/3.2.1 Safari/525.27.1';
    $homepage = "www.google.com";
    $urls = split("\n", $url);
	
	foreach($urls as $u){
	
		$query_url = "http://www.google.com/search?hl=en&q=site:".urlencode(trim($u))."&btnG=Search&meta=&aq=f&oq=";
	    $randomagent = array_rand($agents);
		sleep(rand(1, 5));
		get_data($homepage,$randomagent);
		sleep(rand(1, 15));
		$page = get_data($query_url,$randomagent);
		if (preg_match("/did not match any documents/", $page)) {
			$message .= $u ."\n\n";
		}
	}
	
	if (trim($message) === '') {
		$message = 'All of these URLS were indexed \n\n ';
	}
	else{
		$message = "Here you go these URLS were not indexed. \n\n" . $message;
	}
	
	mail($email, 'Link Report', $message);
	exit;
	
	function get_data($url,$agent,$timeout = 5)
	{
	  $ch = curl_init();
	  $timeout = 5;
	  curl_setopt($ch,CURLOPT_URL,$url);
	  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	  curl_setopt($ch, CURLOPT_USERAGENT, $agent);
	  $data = curl_exec($ch);
	  curl_close($ch);
	  return $data;
	}
		


	
?>
