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
     */

    // just comment out the echo or extend the function to your liking
    function verbose($text)
    {
        echo $text;
    }

    /*
     * Returns up to date data to use for this scraping.
     * includes: user agent (in future, rotating user agents), google language and country codes, domains, etc.
     * This function will only work with a valid plan at us-proxies.com
     *
     * You can remove the API parts and hardcore the values if you do not wish to get a plan there and use a different service for IPs
     */
    function get_api_google_suggest($country, $language)
    {
        global $pwd;
        global $uid;
        global $PROXY;
        global $PLAN;
        global $portal;
        $fp = fsockopen("us-proxies.com", 80);
        if (!$fp)
        {
            echo "Unable to connect to google_cc API \n";

            return NULL; // connection not possible
        } else
        {
            $plan_size=$PLAN['total_ips'];
            $country=urlencode($country);
            $language=urlencode($language);
            fwrite($fp, "GET /g_api.php?api=1&uid=$uid&pwd=$pwd&cmd=google_suggest&country=$country&language=$language&plan_size=$plan_size HTTP/1.0\r\nHost: us-proxies.com\r\nAccept: text/html, text/plain, text/*, */*;q=0.01\r\nAccept-Encoding: plain\r\nAccept-Language: en\r\n\r\n");
            stream_set_timeout($fp, 8);
            $res = "";
            $n = 0;
            while (!feof($fp))
            {
                if ($n++ > 4) break;
                $res .= fread($fp, 8192);
            }
            $info = stream_get_meta_data($fp);
            fclose($fp);

            if ($info['timed_out'])
            {
                echo 'API: Connection timed out! \n';

                return NULL; // api timeout
            } else
            {
                $data = extractBody($res);
                $obj = unserialize($data);
                if (isset($obj['error'])) echo $obj['error'] . "\n";
                if (isset($obj['info'])) echo $obj['info'] . "\n";

                return $obj['data'];

                if (strlen($data) < 4) return NULL; // invalid api response
            }
        }
    }

    function rmkdir($path, $mode = 0755)
    {
        if (file_exists($path)) return 1;

        return @mkdir($path, $mode);
    }

    /* Delay (sleep) based on the license size to allow optimal scraping
     *
     * Warning!
     * Do NOT change the delay to be shorter than the specified delay.
     * This function will create a delay based on your total IP addresses.
     *
     */
    function delay_time($reason='ip', $total_threads=1)
    {
        global $PLAN;
        global $api_suggest_data;


        if ($reason == 'ip')
        {
            $d = $total_threads*$api_suggest_data['delay_rotate_us'];
            verbose("wait.. \n");
        }
        if ($reason == 'request')
        {
            $d = $api_suggest_data['delay_query_us'];
            verbose("wait.. \n");
        }
        usleep($d);
    }


    /*
     * By default (no force) the function will load cached data within 24 hours otherwise reject the cache.
     * The time can be increased to reduce IP usage
     */
    function load_cache($keyword, $api_suggest_data, $force_cache,$test_mode)
    {
        global $working_dir;

        if ($force_cache < 0) return NULL;
        $lc = $api_suggest_data['lc'];
        $hash = md5($keyword . "_" . $lc . "_" . $test_mode );

        $file = "$working_dir/$hash.cache";
        $now = time();
        if (file_exists($file))
        {
            $ut = filemtime($file);
            $dif = $now - $ut;
            $hour = (int)($dif / (60 * 60));
            if ($force_cache || ($dif < (60 * 60 * 24)))
            {
                $serdata = file_get_contents($file);
                $serp_data = unserialize($serdata);
                verbose("Cache: loaded file $file for $keyword . File age: $hour hours\n");

                return $serp_data;
            }

            return NULL;
        } else
        {
            return NULL;
        }

    }
    function store_cache($data, $keyword, $api_suggest_data, $test_mode)
    {
        global $working_dir;

        $lc = $api_suggest_data['lc'];
        $hash = md5($keyword . "_" . $lc . "_" . $test_mode);
        $file = "$working_dir/$hash.cache";
        $now = time();
        if (file_exists($file))
        {
            $ut = filemtime($file);
            $dif = $now - $ut;
            if ($dif < (60 * 60 * 24)) echo "Warning: cache storage initated for $keyword which was already cached within the past 24 hours!\n";
        }
        $serdata = serialize($data);
        file_put_contents($file, $serdata, LOCK_EX);
        verbose("Cache: stored file $file for $keyword.\n");
    }


    // check_ip_usage() must be called before first use of mark_ip_usage()
    function check_ip_usage()
    {
        global $PROXY;
        global $working_dir;
        global $ip_usage_data; // usage data object as array

        if (!isset($PROXY['ready'])) return 0; // proxy not ready/started
        if (!$PROXY['ready']) return 0; // proxy not ready/started

        if (!isset($ip_usage_data))
        {
            if (!file_exists($working_dir . "/ipdata.obj")) // usage data object as file
            {
                echo "Warning!\n" . "The ipdata.obj file was not found, if this is the first usage of the rank checker everything is alright.\n" . "Otherwise removal or failure to access the ip usage data will lead to damage of the IP quality.\n\n";
                sleep(2);
                $ip_usage_data = array();
            } else
            {
                $ser_data = file_get_contents($working_dir . "/ipdata.obj");
                $ip_usage_data = unserialize($ser_data);
            }
        }

        if (!isset($ip_usage_data[$PROXY['external_ip']]))
        {
            verbose("IP $PROXY[external_ip] is ready for use \n");

            return 1; // the IP was not used yet
        }
        if (!isset($ip_usage_data[$PROXY['external_ip']]['requests'][20]['ut_google']))
        {
            verbose("IP $PROXY[external_ip] is ready for use \n");

            return 1; // the IP has not been used 20+ times yet, return true
        }
        $ut_last = (int)$ip_usage_data[$PROXY['external_ip']]['ut_last-usage']; // last time this IP was used
        $req_total = (int)$ip_usage_data[$PROXY['external_ip']]['request-total']; // total number of requests made by this IP
        $req_20 = (int)$ip_usage_data[$PROXY['external_ip']]['requests'][20]['ut_google']; // the 20th request (if IP was used 20+ times) unixtime stamp

        $now = time();
        if (($now - $req_20) > (60 * 60))
        {
            verbose("IP $PROXY[external_ip] is ready for use \n");

            return 1; // more than an hour passed since 20th usage of this IP
        } else
        {
            $cd_sec = (60 * 60) - ($now - $req_20);
            verbose("IP $PROXY[external_ip] needs $cd_sec seconds cooldown, not ready for use yet \n");

            return 0; // the IP is overused, it can not be used for scraping without being detected by the search engine yet
        }
    }

    /*
     * Updates and stores the ip usage data object
     * Marks an IP as used and re-sorts the access array
     */
    function mark_ip_usage()
    {
        global $PROXY;
        global $working_dir;
        global $ip_usage_data; // usage data object as array

        if (!isset($ip_usage_data)) die("ERROR: Incorrect usage. check_ip_usage() needs to be called once before mark_ip_usage()!\n");
        $now = time();

        $ip_usage_data[$PROXY['external_ip']]['ut_last-usage'] = $now; // last time this IP was used
        if (!isset($ip_usage_data[$PROXY['external_ip']]['request-total'])) $ip_usage_data[$PROXY['external_ip']]['request-total'] = 0;
        $ip_usage_data[$PROXY['external_ip']]['request-total']++; // total number of requests made by this IP
        // shift fifo queue
        for ($req = 19; $req >= 1; $req--)
        {
            if (isset($ip_usage_data[$PROXY['external_ip']]['requests'][$req]['ut_google']))
            {
                $ip_usage_data[$PROXY['external_ip']]['requests'][$req + 1]['ut_google'] = $ip_usage_data[$PROXY['external_ip']]['requests'][$req]['ut_google'];
            }
        }
        $ip_usage_data[$PROXY['external_ip']]['requests'][1]['ut_google'] = $now;

        $serdata = serialize($ip_usage_data);
        file_put_contents($working_dir . "/ipdata.obj", $serdata, LOCK_EX);

    }

    function new_curl_session($ch = NULL)
    {
        global $PROXY;
        global $api_suggest_data;
        global $test_mode;
        if ($test_mode == 'chrome')
            $default_agent = $api_suggest_data['default_agent_chrome'];
        else
            $default_agent = $api_suggest_data['default_agent'];

        if ((!isset($PROXY['ready'])) || (!$PROXY['ready'])) return $ch; // proxy not ready

        if (isset($ch) && ($ch != NULL))
        {
            curl_close($ch);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $curl_proxy = "$PROXY[address]:$PROXY[port]";
        curl_setopt($ch, CURLOPT_PROXY, $curl_proxy);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_USERAGENT, $default_agent); // firefox
        return $ch;
    }
    function getip()
    {
        global $PROXY;
        if (!$PROXY['ready']) return -1; // proxy not ready

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'http://ipcheck.ipnetic.com/remote_ip.php'); // returns the real IP
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        $curl_proxy = "$PROXY[address]:$PROXY[port]";
        curl_setopt($curl_handle, CURLOPT_PROXY, $curl_proxy);
        $tested_ip = curl_exec($curl_handle);

        if (preg_match("^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}^", $tested_ip))
        {
            curl_close($curl_handle);

            return $tested_ip;
        } else
        {
            $info = curl_getinfo($curl_handle);
            curl_close($curl_handle);

            return 0; // possible error would be a wrong authentication IP or a firewall
        }
    }


    // return 1 if account is ready, otherwise 0
    function get_plan()
    {
        global $uid;
        global $pwd;
        global $PLAN;

        $res = ip_service("plan"); // will fill $PLAN
        $ip = "";
        if ($res <= 0)
        {
            verbose("API error: Proxy API connection failed (Error $res). trying again later..\n\n");

            return 0;
        } else
        {
            ($PLAN['active'] == 1) ? $ready = "active" : $ready = "not active";
            verbose("API success: License is $ready.\n");
            if ($PLAN['active'] == 1) return 1;

            return 0;
        }

        return $PLAN;
    }
    /*
        * This is the API function to retrieve US IP addresses
        * This function handles the API calls "plan" and "rotate"
        *
        * Rotate: On success this function will define the global $PROXY variable, adding the elements ready,address,port,external_ip and return 1
        * On failure the return is 0 or smaller and the PROXY variable ready element is set to "0"
        * It is good practice to use the API response in $PROXY instead of hardcoding connection parameters
        *
        * Plan: On success this function will define the global $PLAN variable, adding the elements active, max_ips, total_ips, protocol, processes and return 1
        * It is good practice to make one call to "plan" upon starting your script to find out about the status and size of the plan
        */
    function extractBody($response_str)
    {
        $parts = preg_split('|(?:\r?\n){2}|m', $response_str, 2);
        if (isset($parts[1])) return $parts[1]; else  return '';
    }
    function ip_service($cmd, $x = "")
    {
        global $pwd;
        global $uid;
        global $PROXY;
        global $PLAN;

        $fp = fsockopen("us-proxies.com", 80);
        if (!$fp)
        {
            echo "Unable to connect to API \n";

            return -1; // connection not possible
        } else
        {
            if ($cmd == "plan")
            {
                fwrite($fp, "GET /api.php?api=1&uid=$uid&pwd=$pwd&cmd=plan&extended=1 HTTP/1.0\r\nHost: us-proxies.com\r\nAccept: text/html, text/plain, text/*, */*;q=0.01\r\nAccept-Encoding: plain\r\nAccept-Language: en\r\n\r\n");

                stream_set_timeout($fp, 8);
                $res = "";
                $n = 0;
                while (!feof($fp))
                {
                    if ($n++ > 4) break;
                    $res .= fread($fp, 8192);
                }
                $info = stream_get_meta_data($fp);
                fclose($fp);

                if ($info['timed_out'])
                {
                    echo 'API: Connection timed out! \n';
                    $PLAN['active'] = 0;

                    return -2; // api timeout
                } else
                {
                    if (strlen($res) > 1000) return -3; // invalid api response (check the API website for possible problems)
                    $data = extractBody($res);
                    $ar = explode(":", $data);
                    if (count($ar) < 4) return -100; // invalid api response
                    switch ($ar[0])
                    {
                        case "ERROR":
                            echo "API Error: $res \n";
                            $PLAN['active'] = 0;

                            return 0; // Error received
                            break;
                        case "PLAN":
                            $PLAN['max_ips'] = $ar[1]; // number of IPs licensed
                            $PLAN['total_ips'] = $ar[2]; // number of IPs assigned
                            $PLAN['protocol'] = $ar[3]; // current proxy protocol (http, socks, ..)
                            $PLAN['processes'] = $ar[4]; // number of available proxy processes
                            if ($PLAN['total_ips'] > 0) $PLAN['active'] = 1; else $PLAN['active'] = 0;

                            return 1;
                            break;
                        default:
                            echo "API Error: Received answer $ar[0], expected \"PLAN\"";
                            $PLAN['active'] = 0;

                            return -101; // unknown API response
                    }
                }

            } // cmd==plan


            if ($cmd == "rotate")
            {
                $PROXY['ready'] = 0;
                fwrite($fp, "GET /api.php?api=1&uid=$uid&pwd=$pwd&cmd=rotate&randomize=0&offset=0 HTTP/1.0\r\nHost: us-proxies.com\r\nAccept: text/html, text/plain, text/*, */*;q=0.01\r\nAccept-Encoding: plain\r\nAccept-Language: en\r\n\r\n");
                stream_set_timeout($fp, 8);
                $res = "";
                $n = 0;
                while (!feof($fp))
                {
                    if ($n++ > 4) break;
                    $res .= fread($fp, 8192);
                }
                $info = stream_get_meta_data($fp);
                fclose($fp);

                if ($info['timed_out'])
                {
                    echo 'API: Connection timed out! \n';

                    return -2; // api timeout
                } else
                {
                    if (strlen($res) > 1000) return -3; // invalid api response (check the API website for possible problems)
                    $data = extractBody($res);
                    $ar = explode(":", $data);
                    if (count($ar) < 4) return -100; // invalid api response
                    switch ($ar[0])
                    {
                        case "ERROR":
                            echo "API Error: $res \n";

                            return 0; // Error received
                            break;
                        case "ROTATE":
                            $PROXY['address'] = $ar[1];
                            $PROXY['port'] = $ar[2];
                            $PROXY['external_ip'] = $ar[3];
                            $PROXY['ready'] = 1;
                            usleep(230000); // additional time to avoid connecting during proxy bootup phase, removing this can cause random connection failures but will increase overall performance for large IP licenses
                            return 1;
                            break;
                        default:
                            echo "API Error: Received answer $ar[0], expected \"ROTATE\"";

                            return -101; // unknown API response
                    }
                }
            } // cmd==rotate
        }
    }

    // obtain a fresh IP through us-proxies.com API
    function rotate_proxy()
    {
        global $PROXY;
        global $ch;
        $max_errors = 3;
        $success = 0;
        while ($max_errors--)
        {
            $res = ip_service("rotate"); // will fill $PROXY
            $ip = "";
            if ($res <= 0)
            {
                verbose("API error: Proxy API connection failed (Error $res). trying again soon..\n\n");
                sleep(21); // retry after a while, maybe a routing failure
            } else
            {
                verbose("API success: Received proxy IP $PROXY[external_ip] on port $PROXY[port]\n");
                $success = 1;
                break;
            }
        }
        if ($success)
        {
            $ch = new_curl_session($ch);

            return 1;
        } else
        {
            return "API rotation failed. Check license, firewall and API credentials.\n";
        }
    }

    // gets one keyword
    function scrape_suggest($keyword, $api_suggest_data)
    {
        global $ch;
        global $test_mode;
        global $test_relevance_filter;

        $data=array('success'=>0, 'data'=>array());
        $google_ip = $api_suggest_data['domain']; // currently ignored, needs to be analyzed
        $hl = $api_suggest_data['lc'];
        if ($test_mode=='firefox')
        {
            // firefox mode allows only language specific lookups and is more outdated
            $domain= $api_suggest_data['domain'];
            $client= $api_suggest_data['client'];
            $keyword_enc=urlencode($keyword);
            $url = "http://$domain/complete/search?q=$keyword_enc&client=$client&hl=$hl";
        } else
        {
            // chrome mode allows country and language specific lookups, it returns additional meta data
            $domain = $api_suggest_data['domain_chrome'];
            $client = $api_suggest_data['client_chrome'];
            $keyword_enc=urlencode($keyword);
            $url = "http://$domain/complete/search?q=$keyword_enc&client=$client&hl=$hl";
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        $htmdata = curl_exec($ch);
        if (!$htmdata)
        {
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            echo "\tError scraping: $error [ $error ]\n";
            sleep(3);
            return $data;
        } else
        {
            if (strlen($htmdata) < 2)
            {
                sleep(3);
                echo "\tError scraping: empty result\n";
                return $data;
            }
        }
        if (($data_ar = json_decode($htmdata, true)) !== null)
        {
            if ($test_mode=='firefox')
            {
                $keywords = $data_ar[1];
                $data['success']=1;
                $data['data'] = $keywords;
            } else
            {
                // in chrome mode we have a lot more data
                $keywords = $data_ar[1];
                $titles = $data_ar[2];
                $meta = $data_ar[4];
                if (isset($meta) && isset($meta['google:suggesttype']))
                {
                    foreach ($meta['google:suggesttype'] as $idx => $meta_type)
                    {
                        if (strtolower($meta_type) != 'query')
                        {
                            echo "removed non query index $idx: $keywords[$idx]\n";
                            unset($keywords[$idx]);
                        }
                    }
                }
                if (isset($meta) && isset($meta['google:suggestrelevance']))
                {
                    foreach ($meta['google:suggestrelevance'] as $idx => $meta_relevance)
                    {
                        if ((int)$meta_relevance < $test_relevance_filter )
                        {
                            echo "filtered out unrelevant index $idx: $keywords[$idx]\n";
                            unset($keywords[$idx]);
                        }
                    }
                }


                $data['success']=1;
                $data['data'] = $keywords;
            }

        }

        return $data;
    }

    // wrapper to get a full set of keywords
    function get_suggests(&$dataset,$keywords,$api_suggest_data,$recursion_level,$max_results=0xffffff)
    {
        global $test_force_cache;
        global $test_mode;
        $rotate_now=0; // set to 1 to force a rotation after launch, even if IP is not marked as overused
        $empty_counter=0; // count empty replies
        $language=$api_suggest_data['lc'];
        $result=array('success'=>0);

        $rcounter=1;
        foreach ($keywords as $keyword)
        {
            verbose("Scraping keyword '$keyword' at recursion level $recursion_level\n");
            $cdata = load_cache($keyword, $api_suggest_data, $test_force_cache,$test_mode); // load results from local cache if available for today
            // check IP usage
            $ip_ready = check_ip_usage(); // test if ip has not been used within the critical time
            // obtain new IP if necessary
            if (!$cdata) // omit all of this if we have a cache
                if ((!$ip_ready || $rotate_now))
                    while (!$ip_ready || $rotate_now) // test if the IP is ready or overused
                    {
                        $ok = rotate_proxy(); // start/rotate to the IP that has not been started for the longest time, also tests if proxy connection is working
                        if ($ok != 1)
                        {
                            echo("Fatal error: proxy rotation failed:\n $ok\n");
                            $result['success']=-1;
                            return $result;
                        }
                        $ip_ready = check_ip_usage(); // test if ip has not been used within the critical time
                        if (!$ip_ready)
                        {
                            echo("Fatal error: No fresh IPs left, wait a while and retry or obtain a larger plan. \n"); // proper error handling relies on exclusive use of the plan and rotation randomization == 0
                            $result['success']=-2;
                            return $result;
                        }
                        else
                        {
                            $rotate_now = 0;
                            delay_time('ip'); // proper delay
                            break; // ip rotated successfully
                        }
                    }
                else
                    delay_time('request');


            if ($cdata)
            {
                // we have the data already in cache
                $result['success']++;
                $dataset[$language][$recursion_level][$keyword]=$cdata['data'];
            } else
            {
                // we have to make a live request
                $scrape_result = scrape_suggest($keyword, $api_suggest_data);
                if ($scrape_result['success'] == 1)
                {
                    if (!($rcounter++%5)) $rotate_now=1;
                    $result['success']++;
                    $result['errors']=0;
                    $dataset[$language][$recursion_level][$keyword]=$scrape_result['data'];
                    mark_ip_usage(); // store IP usage, this is very important to avoid detection and gray/blacklistings
                    $cdata['keyword'] = $keyword;
                    $cdata['cc'] = $api_suggest_data['cc'];
                    $cdata['lc'] = $api_suggest_data['lc'];
                    $cdata['result_count'] = count($scrape_result['data']);
                    $cdata['data']=$scrape_result['data'];
                    store_cache($cdata, $keyword, $api_suggest_data, $test_mode); // store results into local cache
                }  else
                {
                    if (!($empty_counter++%5)) $rotate_now=1;
                    $dataset[$language][$recursion_level][$keyword]=array();
                    if ($result['errors']++ > 10)
                    {
                        echo "More than 10 errors without results in between, hard abort\n";
                        $result['success']=-3;
                        return $result;
                    }

                }
            }

            $num_results=count_results($dataset,$language);
            if ($num_results >= $max_results)
            {
                echo "reached configured max results, ending..\n";
                break;
            }

        }
        return $result;
    }


    // generates a set of keywords for a specific recursion level
    function generate_keywords(&$dataset,$language,$primary_keywords,$recursion_level)
    {
        if ($recursion_level == 0) return $primary_keywords;
        $keywords = array();
        $level=$recursion_level-1;

        foreach ($dataset[$language][$level] as $kw => $results)
        {
            $keywords = array_unique(array_merge($keywords, $results));
        }
        return $keywords;
    }

    // counts all results
    function count_results(&$dataset,$language,$level=-1)
    {
        $num=0;
        if ($level == -1)
            for ($level=0;$level<10;$level++)
            {
                if (!isset($dataset[$language][$level])) break;
                foreach ($dataset[$language][$level] as $kw => $results) $num+=count($results);
            }
        else
            foreach ($dataset[$language][$level] as $kw => $results) $num+=count($results);
        return $num;
    }

    function display_results(&$dataset,$language)
    {
        $mask = "| %-50.50s | %-50.50s |\n";
        $separator=str_repeat("-", 50);

        echo "\nGoogle Suggest Spider results\n";
        for ($level=0;$level<10;$level++)
        {
            if (!isset($dataset[$language][$level])) break;
            $num = count_results($dataset,$language,$level);
            echo "Recursion level $level contains $num keywords:\n";
            printf($mask, 'Keyword', 'Suggests');
            printf($mask, $separator, $separator);

            foreach ($dataset[$language][$level] as $kw => $results)
            {
                $line=1;
                // $mask = "|%30s |%-30s | x |\n"; // allow table corruption
                foreach ($results as $suggest)
                {
                    if ($line++ == 1)
                        printf($mask, $kw, $suggest);
                    else
                        printf($mask, '', $suggest);
                }

                echo "\n";
            }
        }

    }

    function  mutate_keywords(&$primary_keywords)
    {
        $mutation=range('a','z'); // you can change this to add more or less mutation
        $new_keywords=$primary_keywords;
        foreach ($primary_keywords as &$kw)
        {
            foreach ($mutation as $mut)
                $new_keywords[]="$kw $mut";
        }
        $primary_keywords=$new_keywords;
    }

?>