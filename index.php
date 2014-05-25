<?php
/*
KeyWord Niche Finder

ver: 1.0.0 

Blake B. Howe
http://blakebbhowe.com
*/


/*
Get search term from user
Get the keywords that exist in google suggest (for the term)

for each google suggest get{

  number of patents with search term
  number of tweets with search term
  number of facebook mentions with search term
  Scoring algorithm 
  Can I  buy the domain? If not store suggestions
}

List all suggestions with popularity score

*/

error_reporting(E_ALL);
require_once('patent_search/PatentSearch.php');

$query = "King kong";
$google_api_key = "ENTER YOUR API KEY HERE";
$starting_result = '1';

//get the number of patents with search term


//of course this email should trigger it  
$psearch= new PatentSearch($query, $google_api_key,$starting_result);
$data =$psearch->get_data();
	
if ($data){
	$psearch->writeSearchResults();
}



// from https://dev.twitter.com/docs/auth/application-only-auth
$consumer_key = '';
$consumer_secret = '';
#https://dev.twitter.com/docs/auth/application-only-auth
#remember bearer tokens never expire
$bearer_token = "";



//Init curl
$request = curl_init();
curl_setopt($request, CURLOPT_SSLVERSION, 3);
curl_setopt($request, CURLOPT_URL, 'https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=block76&count=100');
curl_setopt($request, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$bearer_token));
curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
$result = json_decode($content = curl_exec($request)); curl_close($request);

var_dump($result)



?>
