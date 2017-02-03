<?php

	require 'src/init.php';

    	# get team info from team id & validate
    	$team = get_team_by_id($_POST['team_id']);
    	if (!$team) {
    		return_text('Unknown team');
    	}
    	if ($_POST['token'] != $GLOBALS['SLACK_VERIFICATION_TOKEN']) {
    		return_text('Invalid token');
    	}

    	$team_id = $_POST['team_id'];
    	$channel_id = $_POST['channel_id'];
    	$user_id = $_POST['user_id'];

    	# get the command and the optional text
    	$post_text = explode(' ', $_POST['text'], 2);
    	$command = $post_text[0];
    	if (isset($post_text[1])) $text = $post_text[1];

    	# create the slack API object
    	$slack = new Slack($team['access_token']);


    	#
    	# do stuff
    	#

    	if ($command === 'start') {

    		# are we in a DM?
    		if ($_POST['channel_name'] === 'directmessage') {
    			return_text("Sorry, you can't start a question box in a DM!");
    		}

    		# is there a box here already?
    		$box = get_questionbox($team_id, $channel_id);
    		if ($box['id']) return_text("There is already a question box here! Use `show` to see the questions.");

    		# no! carry on!
    		$ret = start_questionbox_in_channel($team_id, $channel_id, $user_id);
    		if ($ret) {
    			send_action_complete_message($team, $channel_id, $user_id, 'start');
                return;

    		} else {
    			return_text("The question box failed to create. Try again?");
    		}
    	}

    	if ($command === 'remove') {

    		# is there a box?
    		$box = get_questionbox($team_id, $channel_id);
    		if (!$box) return_text("There isn't a box here to remove. Fancy that!");

    		# are we the creator of this channel's box?
    		if ($user_id !== $box['creator_id']) return_text("This is not your box. Please don't do that.");

    		# confirm!
    		$confirm = send_confirm_action_message('remove');
    		if (!$confirm['ok']) {
    			log_error('confirm_action', $confirm['error']);
    			return_text("Hmm, something went wrong. Apologies! Please try again.");
    		}
    	}

    	if ($command === 'show') {

    		# is there a box?
    		$box = get_questionbox($team_id, $channel_id);
    		if (!$box) return_text("There isn't a box setup here. Make a new one with `start`");
            # and am I the owner? (if so, let me delete questions)
            $owner = ($user_id === $box['creator_id']);

    		# yes!
    		$questions = get_questions_in_box($team_id, $box['id']);
    		if (!$questions) return_text("There aren't any questions. Ask one with `ask`");

            # get the votes, too
            $votes = get_votes_for_box_by_user($team_id, $box['id'], $user_id);

            $confirm = send_confirm_action_message('show', array('questions' => $questions, 'owner' => $owner, 'votes' => $votes));
            if (!$confirm['ok']) {
                log_error('confirm_action', $confirm['error']);
                return_text("Hmm, something went wrong. Apologies! Please try again.");
            }
    	}

    	if ($command === 'ask') {
    		# is there a box?
    		$box = get_questionbox($team_id, $channel_id);
    		if (!$box) return_text("There isn't a box setup here. Make a new one with `start`");

    		# make sure there's a question
    		if (!$text) return_text("That's not much of a question! Try again, please.");

    		# store the question but not visible, then send a confirmation message
            $ask = ask_question($team_id, $box['id'], $text);
            if (!$ask['id']){
                log_error('ask_question', $ask);
                return_text("Oh no, something went wrong. Try again, please!");
            }

    		$confirm = send_confirm_action_message('ask', array('text' => $text, 'question_id' => $ask['id']));
    		if (!$confirm['ok']){
    			log_error('confirm_action', $confirm);
    			return_text("Hmm, something went wrong. Apologies! Please try again.");
    		}
    	}

        if ($command == 'list') {
            # is there a box?
            $box = get_questionbox($team_id, $channel_id);
            if (!$box) return_text("There isn't a box setup here. Make a new one with `start`");

            # and am I the owner? (only the owner can do this)
            $owner = ($user_id === $box['creator_id']);
            if (!$owner) return_text("Oops, you can't do that. Try `/questionbox show`");

            # yes!
            $questions = get_questions_in_box($team_id, $box['id']);
            if (!$questions) return_text("There aren't any questions. Ask one with `ask`");

            send_action_complete_message($team, $channel_id, $user_id, 'list', array('questions' => $questions));
            return;
        }

        if ($command === 'help') {

            $confirm = send_confirm_action_message('help');
            if (!$confirm['ok']) {
                log_error('confirm_action', $confirm['error']);
                return_text("Hmm, something went wrong. Apologies! Please try again.");
            }
        }

        else {
            return_text("Mistake! Error! Malarkey! Please use `/questionbox help` to see the available commands.");
        }

