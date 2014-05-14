backlinkpatrol
==============

SImple top; to see if your links are indexed.

blekkoseach api
=============

Script to poll blekko search api. Need to plugin your own API KEY

Usage
$bs = new BlekkoSearch();
echo "<td>" .$bs->getBlogResults($keyword->text) . "</td>";
echo "<td> " .$bs->getTwitterResults($keyword->text) ."</td>";  
echo "<td>"  .$bs->getFacebookResults($keyword->text) ."</td>";
  
Depreated I think blekko no longer offers free version
