<?php
/*
KeyWord Niche Finder

ver: 1.0.0 

Blake B. Howe
http://blakebbhowe.com
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
