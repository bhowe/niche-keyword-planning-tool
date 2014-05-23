<?php
/*
KeyWord Niche Finder

ver: 1.0.0 

Blake B. Howe
http://blakebbhowe.com
*/

error_reporting(E_ALL);
require_once('patent_search/PatentSearch.php');

$query = "King kong";
$api_key = "PLUGIN IN KEY";
//of course this email should trigger it  
$psearch= new PatentSearch($query, $api_key);
$data =$psearch->get_data();
	
if ($data){
	print_r($data);
}


?>
