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

$patent_query = "King kong";
$google_api_key = "ENTER YOUR API KEY HERE";
$starting_result = '1';

//of course this email should trigger it  
$psearch= new PatentSearch($patent_query, $google_api_key,$starting_result);
$data =$psearch->get_data();
	
if ($data){
	$psearch->writeSearchResults();
}


?>
