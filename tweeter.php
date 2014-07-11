<?php 


require ("thmOAuth/tmhOAuth.php");
require ("thmOAuth/tmhUtilities.php");

	$username = 'dailyHedera';
	$pass = 'password';
	$db = 'dailyHedera';
	$domain = 'dailyHedera.db.9990839.hostedresource.com';
$tweet_used = (bool)false;
$pub_used = (bool)false;

if ($token == '0210d3bb-8c8f-4a54-ab1e-a37589cf14be')
{

mysql_connect($domain, $username, $pass);
mysql_select_db($db) or die("Failed to connect to database.");
$query = "SELECT * FROM `posts`";
$oldquery = "SELECT * FROM `oldposts`";
$result = mysql_query($query);
$oldresult = mysql_query($oldquery);
$num = mysql_num_rows($result);

$query_last = "SELECT * FROM `oldPosts`";
$result_old = mysql_query($query_last);

for ($i=0; $i<$num; $i++){
	$sub_count = mysql_result($result, $i, 'subscribers');
	$tweets = mysql_result($result, $i, 'tweet_count');
	$shares = mysql_result($result, $i, 'share_count');
	$likes = mysql_result($result, $i, 'like_count');
	$id = mysql_result($result, $i, 'id');
	$rank = ($tweets+$shares+$likes);
	$sql = "UPDATE `dailyHedera`.`posts` SET `rating` =".$rank.  " WHERE `posts`.`id` = ".$id.";";
	mysql_query($sql) or die(mysql_error());
$result = mysql_query($query);	
	
}

// Get averages for algo
$avgQ = "SELECT AVG(rating) FROM `posts`";
$avgData = mysql_query($avgQ) or die(mysql_error());
$avg = mysql_result($avgData, 0);

$avgQS = "SELECT AVG(subscribers) FROM `posts`";
$avgQSData = mysql_query($avgQS);
$avgS = mysql_result($avgQSData, 0);

$avg_tweets_query = "SELECT AVG(tweet_count) FROM `posts`";
$avg_tweets_data = mysql_query($avg_tweets_query);
$avg_tweets = mysql_result($avg_tweets_data, 0);
if ($avg_tweets == 0){
	$avg_tweets = 1;
}


$avg_shares_query = "SELECT AVG(share_count) FROM `posts`";
$avg_shares_data = mysql_query($avg_shares_query);
$avg_shares = mysql_result($avg_shares_data, 0);
if ($avg_shares == 0){
	$avg_shares = 1;
}

$avg_likes_query = "SELECT AVG(like_count) FROM `posts`";
$avg_likes_data = mysql_query($avg_likes_query);
$avg_likes = mysql_result($avg_likes_data, 0);
if ($avg_likes == 0){
	$avg_likes = 1;
}

// Calculate new ratings for each article
for ($c=0; $c<$num; $c++){
	$subscribers = mysql_result($result, $c, "subscribers");
	$like_rating = mysql_result($result, $c, "like_count")*2;
	$share_rating = mysql_result($result, $c, 'share_count')*1.5;
	$tweet_rating = mysql_result($result, $c, "tweet_count")*1;
	$id = mysql_result($result, $c, "id");
	$rating = mysql_result($result, $c, "rating");
	$title = mysql_result($result, $c, "title");
	//$title = iconv( "UTF-8", "ISO-8859-1//TRANSLIT", $title );
	
	
	
	
	$tweetR = ($avg_tweets*$avg)+($tweet_rating+$rating)/($avg_tweets);
	$shareR = ($avg_shares*$avg)+($share_rating+$rating)/($avg_shares);
	$likeR = ($avg_likes*$avg)+($like_rating+$rating)/($avg_likes);
	$newR = ($tweetR+$shareR+$likeR);
	
	//echo "Rating: ".$rating."<br> Like Rating: ".$like_rating."<br> Share Rating: ".$share_rating."<br> Tweet Rating: ".$tweet_rating."<br> Age: ".$age."<br> Average Tweets: ".$avg_tweets."<br> Average Shares: ".$avg_shares."<br> Average Likes: ".$avg_likes."<br> Average Rating: ".$avg;
	

	$new_rating_query = "UPDATE `dailyHedera`.`posts` SET `rating` =".$newR." WHERE `posts`.`id` = ".$id.";";
	mysql_query($new_rating_query) or mysql_error();
	
}


// Order articles by trendiness 
$orderQ = "SELECT * 
FROM  `posts` 
ORDER BY  `posts`.`rating` DESC ";

$b = 0;
$result1 = mysql_query($orderQ) or die(mysql_error());
$t = mysql_result($result1, $b, 'title');
$tweet_link = mysql_result($result1, $b, 'url');
$pub = mysql_result($result1, $b, 'pubid');

$pub_used = check_pub($pub);
$tweet_used = check_tweet($tweet_link);

// Pick best article
do{	
	$t = mysql_result($result1, $b, 'title');
	$tweet_link = mysql_result($result1, $b, 'url');
	$pub = mysql_result($result1, $b, 'pubid');
	$pub_used = check_pub($pub);
	$tweet_used = check_tweet($tweet_link);
	$b++;
	if ($b >= $num){
		die("No new posts available.");
	}
}
while($pub_used==true || $tweet_used==true);

$outclick = "http://www.thedailyhedera.com/click.php?url=".$tweet_link;


// Tweeting and database update section
$short_link= bit_ly($outclick);

$tweet = char_check($t, $short_link);

echo $tweet;
post_tweet($tweet);
postToFB($tweet_link, $t);
$t = str_replace('"', "'", $t);
$t = '"'.$t.'"';

updateDbs($tweet_link, $t, $pub);
refreshPubs();
}
else{
	echo 'Permission Denied';
}

//end of main section

// Check to see if the publisher has been used within last 5 tweets
function check_pub($pub){
	$pubQ = 'SELECT * FROM `pub_check` WHERE `pubid` = '.$pub;
$pub_result = mysql_query($pubQ) or mysql_error();

if(mysql_num_rows($pub_result) == 0){
	
		return FALSE;

	}
	else{
	
		return TRUE;

	}
	echo $pub_used;
}


// Check to see if this article has been tweeted yet
function check_tweet($tweet_link){
	$tweet_query = "SELECT * FROM `tweets` WHERE `url` LIKE ".'"'.$tweet_link.'"';
	$tweet_result = mysql_query($tweet_query) or die(mysql_error());
	
	if(mysql_num_rows($tweet_result)==0){
		
		return FALSE;

	}
	else{
		
		return TRUE;
	}	

}

// Keeps `check_pubs` to 5 rows, deletes oldest
function refreshPubs(){
	$qq = "SELECT * 
FROM  `pub_check` 
ORDER BY  `pub_check`.`id` ASC ";
	$res = mysql_query($qq)or die(mysql_error());
	$rows = mysql_num_rows($res);
	
	if($rows>5){
	$count = $rows-5;
	$r = 0;
	for ($r=0; $r<$count; $r++){

	$pub_row = mysql_result($res, $r, 'id');
	$query = "DELETE FROM `dailyHedera`.`pub_check` WHERE `id` = ".$pub_row;
	mysql_query($query)or die(mysql_error());
	}
	}

	
}
// tweet that bad mother fuckin shit!
function post_tweet($tweet){
	$tmhOAuth = new tmhOAuth(array(
  'consumer_key'    => 'VsdKAiu0Sp30JfdUi3pu5A',
  'consumer_secret' => 'GoFCwcjVdPSf1VeR2dXIzfSTxKJ8uI4ilMFqCCys',
  'user_token'      => '416631658-WE0OwH956uvMQpwQT4dplgKcQ0N5IeWrgALgiXli',
  'user_secret'     => 'KHIkztw6ZdUd9k0bbOJXfgez9xGjtqpL0w7HoJvCk8',
));

$code = $tmhOAuth->request('POST', $tmhOAuth->url('1/statuses/update'), array(
  'status' => $tweet
));

if ($code == 200) {
  echo "Tweet Successful";
$just_ran = (bool)true;
$date = getdate();
$createDate = date("m-d-Y h:i:s", $date[0]); 
} else {
  tmhUtilities::pr($tmhOAuth->response['response']);
}
	
}

// Shortens post url
function bit_ly($url){
	$login = 'flashfad';
	$appkey="R_287943cfc2dea85039add6df6b49fbee";
	$format='txt';
	$connectURL = 'http://api.bit.ly/v3/shorten?login='.$login.'&apiKey='.$appkey.'&uri='.urlencode($url).'&format='.$format;
  return file_get_contents($connectURL);
	
}

// Checks the tweet's lenght and shortens title if its >140 chars
function char_check($title, $link){
	if (strlen($link)+strlen($title)>139){
	$elip = '...';
	//$deficit = (strlen($title)-$available)+4;
	$max = 139 - strlen($link)-strlen($elip);
	//$start = $title-$max;
	$new_title = substr_replace($title, $elip, $max);
}
else {
	$new_title=$title;
}
return $new_title.' '.$link;
}

// Updates Databases `tweets` and `pub_checks` with most recent tweet info in addition to renewing the last posts database
function updateDbs($tweet_link, $t, $pub){ 
	$store_tweet = "INSERT INTO `dailyHedera`.`tweets` (`id`, `pubid`, `url`, `title`, `lastUpdate`) VALUES (NULL, $pub, '$tweet_link', $t, NOW());";
$store_pub = "INSERT INTO `dailyHedera`.`pub_check`(`id`, `pubid`) VALUES (NULL, '$pub');";
$trunc_last = 'TRUNCATE TABLE `oldposts`';
$dupTable_query = 'INSERT oldposts SELECT * FROM `posts`';
mysql_query($trunc_last) or (mysql_error());
mysql_query($store_pub) or die(mysql_error());
mysql_query($store_tweet) or die(mysql_error());
mysql_query($dupTable_query) or die(mysql_error());
	
}

// Post link to Facebook
function postToFB($fb_url, $fb_title){
			$fbapp_id = '249430155111050';
		$fbappsecret_id = 'f8dd3d6244f9009e38202ec283883a3b';
		$app_access_token = '249430155111050|bT3wV796CkbA7ArU5haFIGfZG4k';
		//$access_token = 'AAADi2vvKqooBAG06ZBaFY2DQ9ycWFa8U7k9aZCq2Q4KhP609O5G0DPuJxiXe2iSMlkBAmoF6WghPYW7RlwkKp7VTHpfQZC6hvZBPm9sfQAZDZD';
		$access_token = 'AAADi2vvKqooBAHXoYJyGMIbg4C6gFAXGoG8NRNhyPvnmBONKzwMc4qmY88b5vIY8MdkMC7httImo0AbQ7LBEoJHWE7b5quZCNtsFnovAdvyOnZAjOX';
	
	$url = "https://graph.facebook.com/142672305853940/feed";
	$fields = array (
    'message' => urlencode($fb_title),
    'link' => urlencode($fb_url),
    'access_token' => urlencode($access_token)
	//'app_id' => urldecode($fbapp_id)
);

$fields_string = "";
foreach ($fields as $key => $value):
    $fields_string .= $key . '=' . $value . '&';
endforeach;
rtrim($fields_string, '&');

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, count($fields));
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

$result = curl_exec($ch);
curl_close($ch);
}
	
	

?>