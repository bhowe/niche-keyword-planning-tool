backlinkpatrol
==============

SImple top; to see if your links are indexed.

blekkoseach api
===============

Script to poll blekko search api. Need to plugin your own API KEY

Usage
$bs = new BlekkoSearch();
echo "<td>" .$bs->getBlogResults($keyword->text) . "</td>";
echo "<td> " .$bs->getTwitterResults($keyword->text) ."</td>";  
echo "<td>"  .$bs->getFacebookResults($keyword->text) ."</td>";
  
Depreated I think blekko no longer offers free version


Patent Search API
=================
Uses googles  patent search API
$ps = new PatentSearch("super tech");
echo "<td>" .$ps->results($start) . "</td>

Handle Emom API
===============

Uses enom api to look up if a domain is take.
Plugin your enom creditentials.


Todo:

Create a script that uses patent search, enom, twitter, facebook stats , adword data (keyword planning) to find good domains,

Could possibly scrape google suggest, or ubersuggest

Here we go the answer
http://scrape-google-suggest.compunect.com/