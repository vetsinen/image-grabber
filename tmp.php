<?php
$url = 'https://geeksforgeeks.org';

function normalize($url)
{
    if (strpos($url, "https://")===false && strpos($url, "http://")===false) {
        $url = "https://{$url}";
    }
    return $url;
};
echo normalize('http://mozilla.org');

// Original PHP code by Chirp Internet: www.chirp.com.au
// Please acknowledge use of this code by including this header.
//$input = @file_get_contents($url) or die("Could not access file: $url");
//$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
//if (preg_match_all("/$regexp/siU", $input, $matches, PREG_SET_ORDER)) {
//    foreach ($matches as $match) {
//        var_dump( $match[2]); //= link address
//        // $match[3] = link text
//    }
//}


//function normalize(string $url, string $domain = "pizza33.ua"): string
//{
//
//    if (!(strpos($url, "https://") || strpos($url, "http://"))) {
//        $url =  "https://{$domain}{$url}";
//    }
//    if (!parse_url($url, PHP_URL_PATH)) $url = $url . '/';
//    return $url;
//}

//echo normalize('/css/images/pere.svg"');
//$pageImages = ['/'=>[],"contacts"=>[]];
//var_dump($pageImages["/"]);
//var_dump(key_exists('/',$pageImages));

// Use parse_url() function to parse the URL
// PHP_URL_HOST, PHP_URL_PORT, PHP_URL_USER, PHP_URL_PASS, PHP_URL_PATH, PHP_URL_QUERY or PHP_URL_FRAGMENT
//var_dump(parse_url($url));
//var_dump(parse_url($url, PHP_URL_SCHEME));
//$a =  parse_url($url, PHP_URL_PATH);