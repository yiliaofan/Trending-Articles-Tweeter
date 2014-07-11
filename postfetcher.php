
	<?php
	

	
	$username = 'dailyHedera';
	$pass = 'password';
	$database = 'dailyHedera';
	$domain = 'dailyHedera.db.9990839.hostedresource.com';
	$total = 0;
	$token = $_GET['token'];

if ($token == '0210d3bb-8c8f-4a54-ab1e-a37589cf14be')
{	
	mysql_connect($domain, $username, $pass);
	@mysql_select_db($database) or die('Failed to connect to database');
	$emptyQ = 'TRUNCATE TABLE  `posts`';
	$query = 'SELECT * FROM `rss feeds`';
	mysql_query($emptyQ);
	$result = mysql_query($query);
	$sub_avg = mysql_result($result, 0);
	$num = mysql_num_rows($result);


	for ($i=(0); $i<$num; $i++){
		unset($doc);
		
		set_time_limit(0);
		
		$feedUrl = mysql_result($result, $i, 'url');
		$subscribers = mysql_result($result, $i, 'count');
		$feedTitle = mysql_result($result, $i, 'title');
		$pubid = mysql_result($result, $i, 'pubid');
		$data = file_get_contents($feedUrl);
		if ($data == TRUE){
			
			try{
				$doc = new SimpleXMLElement($data);
			}
			catch(exception $e){}
		
		if (!empty($doc)){

			$x = $doc->channel->item;
			$url = $x->link;
			$url = parseUrl($url);
			$title = $x->title;
			$title = str_replace("\"", "'", $title);
			// $title = iconv( "UTF-8", "ISO-8859-1//TRANSLIT", $title );
			 $title = "\"".$title."\"";
			 $fql = getFql($url);
			 $shares = $fql[0];
			 $likes = $fql[1];
			 $tCount = getT($url);
			 $total = $total+$likes+$shares+$tCount;

		

	
		
		$insert_query = "INSERT INTO `dailyHedera`.`posts` (`id`, `url`, `pubid`, `title`, `like_count`, `share_count`, `tweet_count`, `rating`, `subscribers`, `tweeted`) VALUES (NULL, '$url', '$pubid', $title, '$likes', '$shares', '$tCount', '0', '$subscribers', false)";
		mysql_query($insert_query) or mysql_error();
			}
		else{
			echo "<br>Cannot parse <strong>".$feedTitle."</strong><br>";
		}
		}
		else{
			echo '<br><strong>'. $feedTitle."</strong> is not reachable.<br>";
		}

	}
	mysql_Close();
	$avg = $total/$num;
	echo '<strong>Done!</strong>';
	include 'tweeter_ex.php';
	}
else{
	echo "Permission Denied";
}
	
	// Convert redirect URL to article's real URL and remove query paremeters
	function parseUrl($old_url) {
			    $headers = @get_headers($old_url);
    $pattern = '/Location\s*:\s*(https?:[^;\s\n\r]+)/i';

    if ($locations = preg_grep($pattern, $headers)) {
        preg_match($pattern, end($locations), $redirect);
        $old_url = $redirect[1];
    } 
	
	
	$parsedURL ='http://'. parse_url($old_url, PHP_URL_HOST).parse_url($old_url, PHP_URL_PATH);
	
	return $parsedURL;
		
		
	}
	// Use Facebook's FQL query api (now outdated) to get the article's share and like count
	function getFql($url){
		
		$fql_query = urlencode('SELECT url, share_count, like_count FROM link_stat WHERE url="'.$url.'"');
		$fql_url = 'https://api.facebook.com/method/fql.query?query=' . $fql_query;
		$result = file_get_contents($fql_url);
		$xml = new SimpleXMLElement($result);
		$shares = $xml->link_stat->share_count;
		$likes = $xml->link_stat->like_count;
		
		$fql = array($shares, $likes);
	
		return $fql;
	}
	
	// Use Twitters open URL API that returns an integer representing how many times a specific URL has been tweeted
	function getT($url){
		
				$tweetUrl = 'ht'.'tp://urls.api.twitter.com/1/urls/count.json?url=' . $url . '&callback=?';
		$JSON = file_get_contents($tweetUrl);
		$tData = json_decode($JSON, true);
		$tCount = $tData['count'];
		
		return intval($tCount);
		
	}
	
	
	
	?>
