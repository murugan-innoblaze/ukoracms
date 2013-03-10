<?php
function printFeedType($feed_type = null){
	if(empty($feed_type)){ return 'RSS'; }
	switch(true){
		case(substr(strtolower($feed_type), 0, 3) == 'rss'):
			return 'RSS';
		break;
		case(substr(strtolower($feed_type), 0, 3) == 'rdf'):
			return 'RDF';
		break;
		case(substr(strtolower($feed_type), 0, 3) == 'fee'):
			return 'Atom';
		break;
		default:
			return 'RSS';
		break;
	}
}

function prepareSearch($search){
	return trim(stripslashes(str_ireplace(array('rss', 'feeds', 'feed', 'com'), array('', '', ''), $search)));
}
?>