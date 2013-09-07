<?php
die('KONTAKT UKM NORGE: FILEN M&Aring; BRUKE CURL-KLASSEN!');
function curlURL($url, $timeout=10, $port=false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    // Set a referer
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
    curl_setopt($ch, CURLOPT_USERAGENT, "UKMNorge API");
    
    if($port)
    	curl_setopt($ch, CURLOPT_PORT, $port);
    // Include header in result? (0 = yes, 1 = no)
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
?>