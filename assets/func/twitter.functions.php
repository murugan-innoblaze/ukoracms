<?php
/***********************************************************************/
/******************** GET TWITTER FEED *********************************/
/***********************************************************************/
function getTweetsForUser($username = null){
	$tweets_object = json_decode(@file_get_contents('http://api.twitter.com/1/statuses/user_timeline/' . $username . '.json'));
	return objectToArray($tweets_object);
}
?>