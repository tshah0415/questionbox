<?php
	require 'src/init.php';

	# unpack after your vacation
	$payload = $_POST['payload'];
	$json = json_decode($payload, true);

	# get team info from team id & validate
	$team = get_team_by_id($json['team']['id']);
	if (!$team) {
		return_text('Unknown team');
	}
	if ($json['token'] != $GLOBALS['SLACK_VERIFICATION_TOKEN']) {
		return_text('Invalid token');
	}
	if (!check_callback_id($json['callback_id'])) {
		return_text('Invalid request');
	}

	#
	# figure out what to do
	#

	$channel_id = $json['channel']['id'];
	$action = $json['actions'][0];
	$name = $action['name'];
	$value = $action['value'];

	switch ($name) {
		case 'remove':
			if ($value === 'confirm') {
				# get the box
				$box = get_questionbox($team['team_id'], $channel_id);
				if (!$box) return_text("Whoops, something is awry! Try again?");

				# kill it
				$remove = remove_questionbox_from_channel($team['team_id'], $channel_id);
				if (!$remove) return_text("Whoops, something is awry! Try again?");

				# and its questions
				delete_all_questions_in_box($team['team_id'], $box['id']);

				# and its votes
				remove_votes_in_box($team['team_id'], $box['id']);

				return_text("It has been done. There is no longer a question box in this channel.");
			}
			if ($value == 'cancel') {
				return_text("OK, I didn't do anything. Your box remains as boxy as ever.");
			}

		case 'ask':
			if ($value === 'yes_anon' || $value == 'yes_named') {
				list($box, $question) = get_question_from_id($team['team_id'], $channel_id, $json);

				$asker = ($value === 'yes_named') ? ($json['user']['id']) : '';
				$ask = ask_question($team['team_id'], $box['id'], $question, $asker, time(), $question['id']);
				if (!$ask['id']){
					log_error('ask_question', $ask);
					return_text("Darn, I couldn't add your question. Please try again! (press tab)");
				}

				#
				# update the text so the buttons go away
				#

				return send_confirm_action_message('asked', array('question' => $question['text']));
			}
			if ($value === 'no_ask') {
				return_text("OK, I didn't add your question.");
			}

		case 'vote':
			list($box, $question) = get_question_from_id($team['team_id'], $channel_id, $json);

			# I voted!
			$vote = vote_for_question($team['team_id'], $box['id'], $question['id'], $json['user']['id']);
			if (!$vote) {
				log_error('vote', $vote);
				return_text("Hanging Chad! I didn't get your vote, sorry. Try again?");
			}

			#
			# now resend the question list, and get our list of votes so we can disable another vote on those
			#

			$owner = ($json['user']['id'] === $box['creator_id']) ? $json['user']['id'] : "";
			$questions = get_questions_in_box($team['team_id'], $box['id']);
	    		if (!$questions) $questions = array();
	    		$votes = get_votes_for_box_by_user($team['team_id'], $box['id'], $json['user']['id']);

	            	send_confirm_action_message('show', array('questions' => $questions, 'votes' => $votes, 'owner' => $owner, 'text' => "*Yay, you voted! Good vote, A+*\n"));

	        case 'delete':
	        	list($box, $question) = get_question_from_id($team['team_id'], $channel_id, $json);

	        	# are we allowed to do this? (we shouldn't have seen the button)
	        	if ($json['user']['id'] !== $box['creator_id']) return_text("Uhh, you can't do that. Be nice!");

	        	# DESTROY
	        	$delete = delete_question($team['team_id'], $box['id'], $question['id']);
	        	if (!$delete) {
	        		log_error('delete', $delete);
	        		return_text("I have failed! Could not delete, I am sorry. Try again?");
	        	}

	        	# and its votes
	        	remove_votes_for_question($team['team_id'], $box['id'], $question['id']);

	        	#
	        	# now resend the question list
	        	#

	        	$owner = ($json['user']['id'] === $box['creator_id']) ? $json['user']['id'] : "";
	        	$questions = get_questions_in_box($team['team_id'], $box['id']);
	    		if (!$questions) $questions = array();
	    		$votes = get_votes_for_box_by_user($team['team_id'], $box['id'], $json['user']['id']);

	            	send_confirm_action_message('show', array('questions' => $questions, 'owner' => $owner, 'votes' => $votes));

		default:
			# code...
			break;
	}

	function get_question_from_id($team_id, $channel_id, $json) {

		#
		# get the question from the json response
		#

		$user_id = $json['user']['id'];
		$question_id = substr($json['callback_id'], 3);

		# verify the box, then update the question row's date_create so it's visible
		$box = get_questionbox($team_id, $channel_id);
		if (!$box['id']){
			log_error('ids', array($team_id, $channel_id));
			log_error('get_questionbox', $box);
			return_text('Huh, something is weird. Try again?');
		}

		$question = get_question_in_box($team_id, $box['id'], $question_id);
		if (!$question['id']){
			log_error('get_question', $question);
			return_text('Huh, something is weird. Try again?');
		}

		return array($box, $question);
	}


