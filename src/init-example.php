<?php

	ini_set("log_errors", 1);
	ini_set("error_log", "/the/place/you/log/this.log");
	require 'db.php';
	require 'util.php';
	require 'message.php';
	require 'slack.php';
	header('Content-Type: application/json');

	$GLOBALS['SLACK_CLIENT_ID']		= '###########.############';
	$GLOBALS['SLACK_CLIENT_SECRET'] 	= 'blahblahblahblahblahblahblahblah';
	$GLOBALS['SLACK_VERIFICATION_TOKEN'] 	= '1SEvEnTeeN7ThiRtY3EiGht8';
	$GLOBALS['SLACK_CALLBACK_ID'] 		= 'qb_'; # this is kinda dumb but whatever ¯\_(ツ)_/¯


