<?php
	require 'src/init.php';

	#
	# Expect a code parameter
	#

	$code = $_GET['code'];
	if (!$code) send_response('No code');

	#
	# Call Slack oauth.access. We don't need a token, yet.
	#

	$slack = new Slack('');
	$ret = $slack->call('oauth.access', array(
		'code'			=> $code,
		'client_id'		=> $GLOBALS['SLACK_CLIENT_ID'],
		'client_secret'		=> $GLOBALS['SLACK_CLIENT_SECRET'],
	));
	if (!$ret['ok']) send_response("Error: {$ret['error']}");

	$ret = store_access_token($ret['team_id'], $ret['access_token']);
	if (!$ret) send_response("Error storing token.");
	send_response('Success!');

	function send_response($text) {
		echo $text;
		exit;
	}

