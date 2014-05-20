<?php
    /* License:
       Open source for private and commercial use.
       This source code is free to use and modify as long as this comment stays untouched on top.
       URL of original source code: http://scrape-google-suggest.compunect.com/
       Author of original source code: http://www.compunect.com
       Under no circumstances and under no legal theory, whether in tort (including negligence), contract, or otherwise, shall the Licensor be liable to anyone for any direct, indirect, special, incidental, or consequential damages of any character arising as a result of this License or the use of the Original Work including, without limitation, damages for loss of goodwill, work stoppage, computer failure or malfunction, or any and all other commercial damages or losses. This limitation of liability shall not apply to the extent applicable law prohibits such limitation.
       Exceptions:
       Public redistributing modifications of this source code project is not allowed without written agreement.
       Using this work for private and commercial projects is allowed, redistributing it is not allowed without our written agreement.
       In simple words: You may power your project with this code or a customized version of it, but you may not redistribute the code. Also any legal consequences are your own problem.

       If you want to hire me for customization or a similar project please write an email to develop@compunect.com
     */
    /*
     * This scraper is meant to be run from a console (either by making it shell executable (#!) or launching it through php
     * It is not recommended to run any scraping activity through a web browser.
     * Instead of this your web script should interface through a database or other way asynchronously
     *
     * This script can be used in different modes to find keyword autocompletion and suggestions and is best suited for long-tail keywords
     * Spidering:
     * By setting a recursion spide depth higher than 0 you activate the Spider mode, that's the actual heart of this tool.
     * Recursion of 2 means that the script will get all suggests for your keyword phrase. Then it will get all suggests for all suggests results, then it will get suggests for all results of all results
     * So even a small recursion depth ($test_spider_depth) can lead to a large amount of keywords, keep that in mind before entering any larger number there.
     * A recursion of 10 leads to 10 billion keywords in an optimal case, more than what makes sense and most of them would be off topic (and repetitive)
     * A recursion of 3 already leads to up to 1000 keywords and will take its time to complete.
     * Instead of using a large recursion depth better use different keywords
     *
     * IP rotation rate: Mid term results show stable spidering is possible at a rotation rate of 100 queries per hour per IP address when rotating once after every 5 requests and keeping a delay between requests
     * It might be required to lower that rate, keep yourself updated by checking back here regularly to avoid getting blocked.
     *
     * As there is local caching it is no problem to interrupt the spider, it will continue where it was stopped
     *
     */
    /*
     * Possible uses:
     * Keyword research: this tool can easily expand keywords and find new ones. And the best: These are all keywords which are strongly organic search related!
     * Long-tail keyword research: Money can be made when it comes to phrases. Phrases are searched by people just as keywords but compared to a high quality keyword it is easy to rank for a phrase or pay for a phrase ad.
     * Event logging: The results of a country, city or public person will change over the time and reflect what people were interested and talking about. (Just think Obama,Putin,EU,terror during Iraq, Crimea, Georgia, etc)
     * News: Your news blog or website can benefit from targeting exactly what people are most interested in. Using this spider you can find thousands of highly relevant phrases related to a particular news topic.
     * Research: From behaviour research, to your own Google Zeitgeist study or common typographical errors. This spider allows you to research the searching behaviour of internet users
     * There are countless ways this data can be exploited for your website, service or SEO.
     */


    error_reporting(E_ALL & ~E_NOTICE);

    require_once "functions-sgs.php";

    // ************************* Configuration variables *************************
    // Your api credentials, you need a plan at us-proxies.com to use this feature
    // You may use another service but this project was built around the us-proxies service, other services will likely require more work
    $pwd = US-PROXIES.COM-API-KEY;
    $uid = API-USER-ID;

    $working_dir = './local_cache_sgs';
    $test_keywords = 'Hairdryer';
    $test_spider_depth = 1;         // Recursion depth when operating in spider mode. Set to 0 to disable spider recursion ! Value 5 already leads to up to 100,000 results. Be cautious.
    $test_mutate_keyword = 1;       // Generates virtual phrases by adding the letters a-z to your primary keyword(s), this leads to ~300 close related phrases in recursion level 0
    $test_mode = 'chrome';          // 'firefox' or 'chrome' , chrome is the newer approach
    //$test_mode = 'firefox';       // 'firefox' or 'chrome' , chrome is the newer approach
    $test_relevance_filter=300;     // when using test_mode=chrome it is possible to filter for the suggest relevance. Perfect relevance is 1000 but even 550 is usually still a good and relevant keyword
                                    // however, by changing the relevance it is possible to tweak the number of results.
    $test_maxresults = 5000;        // stop working after reaching this count of results (or no results left).

    $test_country = "global";       // Currently not in use
    $test_language = "English";     // Language to use, non English is mainly useful if your keyword is in another language as well but can also make a difference otherwise.
    $test_force_cache=0;            // forces to load from cache even if cached result is too old


    $PROXY = array();               // after the rotate api call this variable contains these elements: [address](proxy host),[port](proxy port),[external_ip](the external IP),[ready](0/1)
    $PLAN = array();                // after the plan api call this variable contains the PLAN details about ip count, processes, protocol, etc
    $dataset = array();             // this is our main data container it will contain all our results

    $primary_keywords = explode(',', $test_keywords);
    if (!count($primary_keywords)) die ("Error: no keywords defined.\n");
    if (!rmkdir($working_dir)) die("Failed to create/open $working_dir\n");

    $ready = get_plan();
    if (!$ready) die("The specified API credentials for user $uid are not active or invalid. \n");
    if ($PLAN['protocol'] != "http") die("Wrong proxy protocol configured, switch to HTTP and retry. \n");

    // Query API to get proper codes and domains for country and language selection
    $api_suggest_data = get_api_google_suggest($test_country, $test_language); // has to be global reachable
    if (!$api_suggest_data) die("Invalid country/language specified.\n");


    $dataset=array();

    $ch = new_curl_session(); // $ch is the cURL handler for our requests
//var_dump($country_data);die();

    $language=$api_suggest_data['lc'];
    $empty_counter=0; // counts empty responses

    //spidering if configured
    $start_time=time();
    for ($recursion_level=0;$recursion_level<=$test_spider_depth;$recursion_level++)
    {
        $keywords = generate_keywords($dataset,$language,$primary_keywords,$recursion_level);
        $data=get_suggests($dataset,$keywords,$api_suggest_data,$recursion_level,$test_maxresults);
        if ($data['success'] >= 1)
        {

            //$dataset[$language][$recursion_level][$keyword]=$suggests[]
        } else
            if ($data['success'] == 0)
            {
                // empty result, count
                $empty_counter++;
            } else
                if ($data['success'] < 0)
                {
                    echo "hard error requires stop\n";
                    break;
                }
        $spent = time()-$start_time;
        $time_str="$spent seconds";
        if ($spent > 3600)
            $time_str=(int)($spent/60)." minutes";
        $num=count_results($dataset,$language);

        if ($num >= $test_maxresults)
        {
            break;
        } else
            verbose("Spider status: Recursion level $recursion_level, Total keywords: $num keywords, Time spent: $time_str\n");
    }
    $recursion_level--;
    $num=count_results($dataset,$language);
    verbose("\n\nFinished\nRecursion level reached: $recursion_level, Total keywords: $num keywords, Time spent: $time_str\n");
    display_results($dataset,$language);

?>