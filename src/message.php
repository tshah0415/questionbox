<?php
	function send_action_complete_message($team, $channel_id, $user_id, $type, $extras=array()) {
		$args = array(
			'channel' 	=> $channel_id,
			'username' 	=> 'Question Box',
			'link_names' 	=> true,

		);

 		if ($type === 'start') {
			$args['text'] = "Yoohoo! <@$user_id> created a question box in this channel!\nType `/questionbox help` to find out more!";
		} else if ($type === 'remove') {
			$args['text'] = "<@$user_id> has removed the existing question box from this channel.";
		} else if ($type === 'list') {

			$questions = $extras['questions'];
			$count = count($questions);
			$attachments = array();

			# introduce the question box
			$text = ":wave: *Hi there! There is a question box in this channel! I hope you enjoy that fact, and rejoice* :tada:\n";
			# now tell people what to do about it
			$text .= "\n:speaking_head_in_silhouette: To ask a question of your own, use `/questionbox ask`\n:point_up: To vote for questions you like, enter `/questionbox show`\n\n";
			if (!$count) {
				$text .= "The questionbox is empty. Please, I beg you, someone ask a question! :tired_face:";
			} else {
				$count_text = $count > 1 ? "are $count questions" : "is one question";
				$text .= "There $count_text in the question box. Please, for your enjoyment:";
			}


			foreach ($questions as $question) {
				if (!$question['id']) continue;

				$q_text = "{$question['text']}";
				if ($question['user_id']) $q_text .= " [asked by <@{$question['user_id']}>]";

				# show the vote counts if there are any
				$count_text = ($question['vote_count'] != 1) ? "votes" : "vote";
				if ($question['vote_count'] > 0) $q_text .= " - {$question['vote_count']} $count_text";

				$attachments[] = array(
					'text'		=> $q_text,
					'fallback'	=> $q_text,
				);
			}
			$args['text'] = $text;
			$args['attachments'] = json_encode($attachments);
		}

		$slack = new Slack($team['access_token']);
		$slack->call('chat.postMessage', $args);
	}

	function send_confirm_action_message($type, $extras=array()) {

		$args = array(
			'username'	=> 'Question Box',
			'response_type' => 'ephemeral',
		);

		if ($type === 'ask') {
			$text = "Ready to ask this question? You can do so anonymously, if you wish.";
			$confirm_anon_button = array(
				'name'	=> 'ask',
				'text'  => 'Ask anonymously',
				'type'  => 'button',
				'value' => 'yes_anon',
				'style' => 'primary',
			);
			$confirm_user_button = array(
				'name'	=> 'ask',
				'text'	=> 'Ask as myself',
				'type' 	=> 'button',
				'value' => 'yes_named',
			);
			$cancel_button = array(
				'name'  => 'ask',
				'text'	=> 'Forget it',
				'type'  => 'button',
				'value' => 'no_ask',
				'style' => 'danger',
			);

			$attachment = array(
				'fallback' 	=> 'Oh no! This only works when buttons work!',
				'callback_id'	=> "qb_{$extras['question_id']}",
				'text'		=> "{$extras['text']}",
				'actions' 	=> array($confirm_anon_button, $confirm_user_button, $cancel_button),
			);
			$args['attachments'] = array($attachment);

		} else if ($type === 'remove') {
			$text = "Are you sure you want to remove the question box in this channel? This will delete all questions and their votes.";
			$confirm_button = array(
				'name'	=> 'remove',
				'text'	=> "I'm Sure",
				'type'	=> 'button',
				'value' => 'confirm',
				'style' => 'primary',
			);
			$cancel_button = array(
				'name'	=> 'remove',
				'text'	=> "Cancel",
				'type'	=> 'button',
				'value'	=> 'cancel',
				'style' => 'danger',
			);

			$attachment = array(
				'fallback' 	=> 'Oh no! This only works when buttons work!',
				'callback_id'	=> 'qb_102091',
				'actions' 	=> array($confirm_button, $cancel_button)
			);
			$args['attachments'] = array($attachment);

		} else if ($type === 'show') {

			$questions = $extras['questions'];
			$count = count($questions);
			$owner = $extras['owner'];
			$attachments = array();
			if (isset($extras['text'])) {
				$text = $extras['text'] . "\n";
				if (!$count) {
					$text .= "That was the only question in the box!";
				} else {
					$text .= "Here are the questions again:";
				}
			} else {
				if (!$count) {
					$text = "There aren't any questions in the box. Ask one! `/questionbox ask [your question]`";
				} else {
					$count_text = $count > 1 ? "are $count questions" : "is one question";
					$text = "There $count_text in the question box. Please, for your enjoyment:";
				}
			}

			foreach ($questions as $question) {
				if (!$question['id']) continue;

				$q_text = "{$question['text']}";
				if ($question['user_id']) $q_text .= " [asked by <@{$question['user_id']}>]";

				#
				# make buttons and send back the question id so we can take action on it.
				# but check that we haven't voted for this question already, and don't let us vote if we have!
				#

				$actions = array();

				if (!in_array($question['id'], $extras['votes'])) {
					$actions[] = array(
						'name'  => 'vote',
						'text'  => 'Vote up',
						'type'  => 'button',
						'value' => 'vote',
					);
				}
				if ($owner) {
					$actions[] = array(
						'name'  => 'delete',
						'text'  => 'Delete',
						'type'  => 'button',
						'value' => 'delete',
						'style'	=> 'danger',
					);
				}


				$attachments[] = array(
					'text'		=> $q_text,
					'fallback'	=> $q_text,
					'callback_id'	=> "qb_{$question['id']}",
					'actions'	=> $actions,
				);
			}
			$args['attachments'] = $attachments;

		} else if ($type === 'help') {

			$text = "Hello! Question Box is a bot that allows you to gather questions in a Slack channel. People can ask anonymously if they'd like, and vote on other questions people have asked.\nWow! So fun! Here's what you can do:\n\n";

			$text .= ":seedling: `start` will create a new Question Box in whichever channel you are in.\n";
			if ($box) $text .= "_There's already a box in here, though. Go start one somewhere else!_\n";

			$text .= ":thinking_face: `ask` to add a question of your own. You can ask anonymously or as yourself. You are you, after all.\n";
			$text .= ":poodle: `show` will present you with all the questions and allow you to vote on the ones you like.";
			$text .= " (If you have created a box, you have the ability to delete its questions, too)\n";
			$text .= ":mega: `list` allows the creator of the box to publish the list of questions, along with their vote counts.\n";
			$text .= ":hole: `remove` empties and removes the current box from the channel. It can be restarted anew, but all the questions will disappear!\n";
			$text .= ":sos: `help` will show a...wait how did you get here?";

		} else if ($type == 'asked') {
			$text = "Great question! You rule! Added to the question box!";
			$attachment = array('text' => $extras['question']);
			$args['attachments'] = array($attachment);
		}

		return_text($text, $args);
	}




