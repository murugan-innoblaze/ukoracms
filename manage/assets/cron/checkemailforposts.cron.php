<?php

//Open inbox
$imap = imap_open(IMAP_SERVER, IMAP_USERNAME, IMAP_PASSWORD) or die('Cannot imap: ' . imap_last_error());

//this will hold message information
$messages = array(); if(false !== ($emails = imap_search($imap, 'SINCE "' . date('D, j M Y H:i:s O', strtotime('-12 hours')) . '"'))){ rsort($emails); foreach($emails as $email_number){ $details = imap_fetch_overview($imap, $email_number, 0); $messages[$email_number]['details'] = $details[0]; $messages[$email_number]['body'] = imap_fetchbody($imap, $email_number, 1); $messages[$email_number]['subject'] = $details[0]->subject; } }

//close imap
imap_close($imap);

//Prepare Publishing stack
$publish_stack = array(); if(isset($messages) and !empty($messages)){ foreach($messages as $count => $message){ $from_matches = array(); preg_match('/\<([a-z0-9@\.\-_]+)\>/', $message['details']->from, $from_matches); if($message['details']->seen == 0 and isset($from_matches[1]) and validateAdminEmail($from_matches[1])){ $publish_stack[$count] = $message; $matches = array(); preg_match('/\[([a-z\,\s\.\']+)\]/i', $message['details']->subject, $matches); if(isset($matches[1]) and !empty($matches[1])){ $platforms = explode(',', $matches[1]); $publish_stack[$count]['platforms'] = $platforms; $publish_stack[$count]['details']->subject = $publish_stack[$count]['subject'] = preg_replace('/\[([a-z\,\s\.\']+)\]/i', '', $message['details']->subject); } } } }

//Publish the stack
if(isset($publish_stack) and !empty($publish_stack)){
	foreach($publish_stack as $publish_item){

		//Publish notification or news
		if(strlen($publish_item['body']) <= TWITTER_STATUS_LIMIT){
			postSiteNotification($publish_item['subject'], $publish_item['body']); 
		}else{
			$news_id = postSiteNews($publish_item['subject'], $publish_item['body']);
			$news_content_link = 'http://www.' . HOST_NAME . '/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . prepareStringForUrl($publish_item['subject']) . '-' . convertNumber($news_id) . '/';
			$publish_item['body'] = 'Just posted: ' . $news_content_link;
		}

		//Publish to facebook
		if((isset($publish_item['platforms']) and !empty($publish_item['platforms']) and in_array('facebook', $publish_item['platforms'])) and false !== getSiteData('facebook_access_token') and false !== getSiteData('facebook_user_id')){
			$Facebook = new Facebook($this->db);
			$Facebook->streamPublish($publish_item['body']);
		}
				
		//Publish to twitter
		if((isset($publish_item['platforms']) and !empty($publish_item['platforms']) and in_array('twitter', $publish_item['platforms'])) and false !== ($twitter_oauth_token = getSiteData('twitter_oauth_token')) and false !== ($twitter_oauth_token_secret = getSiteData('twitter_oauth_token_secret'))){
			$TwitterOAuth = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $twitter_oauth_token, $twitter_oauth_token_secret);
			$TwitterOAuth->get('account/verify_credentials');
			$TwitterOAuth->post('statuses/update', array('status' => $publish_item['body']));
		}

	}
}

?>