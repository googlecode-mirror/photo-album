<p>
This script does a simple network communication test to make sure your server can communicate with Flickr.com
</p>
<p>
Now performing tests...
</p>
<?php
$msg = "";
$response = "";

if (function_exists('curl_init')) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://www.flickr.com/");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
} elseif (function_exists('fsockopen')) {
	$fp = fsockopen("www.flilckr.com", 80, $errno, $errstr, 30);
	if (!$fp) {
	    $msg = "$errstr ($errno)<br />\n";
	} else {
	    $out = "GET / HTTP/1.1\r\n";
	    $out .= "Host: www.flickr.com\r\n";
	    $out .= "Connection: Close\r\n\r\n";
	
	    fwrite($fp, $out);
	    while (!feof($fp)) {
	        $response .= fgets($fp, 128);
	    }
	    fclose($fp);
	}
}

if (strpos($response, 'Flickr') === false) {
	$msg = "This server could not communicate with the Flickr.com server.";
}


if ($msg) {
echo "<strong style='color:red;'>One or more problems were found:</strong><br /><br />";
echo $msg;
} else {
echo "<strong style='color:green;'>OK.</strong> No network problems found. This server is able to communicate with the Flickr.com server.";
}
?>