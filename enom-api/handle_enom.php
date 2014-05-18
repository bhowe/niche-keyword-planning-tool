<?php

/*
 * Contacts enom API checks to see if a domain is registered or not.
ver: 1.0.0 

settings:
	 Plugin your username and password for enom
 
Blake B. Howe
http://blakebbhowe.com
*/

error_reporting(E_ALL);
ini_set('display_errors', '1');


require_once('class.EnomService.php');


$enom = new EnomService('**************', '*********************', false, false);

$domain = 'psssoftware.com';

echo checkdomain($domain,'com');
echo checkdomain($domain,'net');
echo checkdomain($domain,'org');
       

function makedomain($url)
{
  $theurl = str_replace(" ", '',$url);
  $theurl = strtolower($theurl);
  return $theurl;

}

function checkdomain($url,$ext)
{
  
  global $enom;
  
  $result = $enom->checkDomain($url, $ext, true);   // This enables domain spinner
  
 
  $return_value = $url . '.' . $ext;
  if ($result[$return_value]){
  	return $return_value;}
  else{
  	return 'Already taken';}
  

}


?>
