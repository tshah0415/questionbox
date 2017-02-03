<?php
	function return_text($msg='', $extras=array()) {
		$text = array('text'=>$msg);
		$text = array_merge($text, $extras);
		echo json_encode($text);
		exit;
	}

	function log_error($msg, $obj=null) {
		$output = $obj ? print_r($obj, true) : '';
		error_log("Error $msg:\n$output");
	}

	function check_callback_id($callback_id) {
		return substr($callback_id, 0, 3) === $GLOBALS['SLACK_CALLBACK_ID'];
	}

