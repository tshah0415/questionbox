<?php
    
    require 'conf.php';

    # make a global db object for the querying
    $db = mysqli_connect(kQBoxMySqlHost, kQBoxMySqlUsername, kQBoxMySqlPassword, kQBoxMySqlDatabase);

    function db_query($query) {
        global $db;

        $ret = mysqli_query($db, $query);
        if (!$ret) return $ret;
        if ($ret === true) return $ret;

        $output = [];
        while ($row = mysqli_fetch_assoc($ret)) {
            $output[] = $row;
        }

        return $output;
    }

    function get_team_by_id($id) {
        $id = mysql_escape_string($id);
        $query = "select * from teams where team_id='$id'";
        return db_query($query)[0];
    }

    function start_questionbox_in_channel($team_id, $channel_id, $creator_id) {
        $date_create = time();
        $team_id = mysql_escape_string($team_id);
        $channel_id = mysql_escape_string($channel_id);

        $query = "insert into question_boxes (
            `team_id`,
            `creator_id`,
            `channel_id`,
            `date_refreshed`
        ) values (
            '$team_id',
            '$creator_id',
            '$channel_id',
            $date_create
        )";
        return db_query($query);
    }

    function remove_questionbox_from_channel($team_id, $channel_id) {
        $team_id = mysql_escape_string($team_id);
        $channel_id = mysql_escape_string($channel_id);
        $query = "delete from question_boxes where team_id='$team_id' AND channel_id='$channel_id'";
        return db_query($query);
    }

    function get_questionbox($team_id, $channel_id) {
        $team_id = mysql_escape_string($team_id);
        $channel_id = mysql_escape_string($channel_id);
        $query = "select * from question_boxes where team_id='$team_id' and channel_id='$channel_id'";
        return db_query($query)[0];
    }

    function get_questions_in_box($team_id, $box_id) {
        $team_id = mysql_escape_string($team_id);
        $query = "select * from questions where team_id='$team_id' and box_id=$box_id AND date_create>0 order by vote_count desc";
        return db_query($query);
    }

    function get_question_in_box($team_id, $box_id, $question_id) {
        $team_id = mysql_escape_string($team_id);
        $query = "select * from questions where team_id='$team_id' and box_id=$box_id and id=$question_id";
        return db_query($query)[0];
    }

    function vote_for_question($team_id, $box_id, $question_id, $user_id) {

        # first update the question row
        $team_id = mysql_escape_string($team_id);
        $user_id = mysql_escape_string($user_id);
        $question_query = "update questions set vote_count=vote_count+1 where team_id='$team_id' AND box_id=$box_id AND id=$question_id";
        if (db_query($question_query)) {
            # then add a vote row
            $vote_query = "insert into votes (
                `team_id`,
                `box_id`,
                `question_id`,
                `user_id`
            ) values (
                '$team_id',
                $box_id,
                $question_id,
                '$user_id'
            )";
            return db_query($vote_query);
        }
        return false;
    }

    function get_votes_for_box_by_user($team_id, $box_id, $user_id) {
        $team_id = mysql_escape_string($team_id);
        $user_id = mysql_escape_string($user_id);

        $query = "select * from votes where team_id='$team_id' AND box_id=$box_id AND user_id='$user_id'";
        $ret = db_query($query);
        
        # just make it a list of ids so it's easy to check
        $votes = array();
        foreach ($ret as $vote) {
            $votes[] = $vote['question_id'];
        }
        return $votes;
    }

    function remove_votes_in_box($team_id, $box_id) {
        $team_id = mysql_escape_string($team_id);
        $query = "delete from votes where team_id='$team_id' AND box_id=$box_id";
        return db_query($query);
    }

    function remove_votes_for_question($team_id, $box_id, $question_id) {
        $team_id = mysql_escape_string($team_id);
        $query = "delete from votes where team_id='$team_id' AND box_id=$box_id AND question_id=$question_id";
        return db_query($query);
    }

    # either update an existing question with date_create, possibly user id, or add a new, non-visible one
    function ask_question($team_id, $box_id, $text, $user_id="", $date_create=0, $question_id=0) {
        $team_id = mysql_escape_string($team_id);
        $channel_id = mysql_escape_string($channel_id);
        $text = mysql_escape_string($text);
        if ($user_id) $user_id = mysql_escape_string($user_id);

        if ($date_create){
            $query = "update questions set user_id='$user_id', date_create=$date_create where team_id='$team_id' AND box_id=$box_id AND id=$question_id";
        } else {
            $query = "insert into questions (
                `box_id`,
                `team_id`,
                `text`,
                `user_id`,
                `date_create`,
                `vote_count`
            ) values (
                $box_id,
                '$team_id',
                '$text',
                '',
                0,
                0
            )";
        }

        $ret = db_query($query);
        if ($ret){
            global $db;
            $id = $question_id ?: mysqli_insert_id($db);
            $select_query = "select * from questions where id=$id";
            return db_query($select_query)[0];
        } else {
            return $ret;
        }
    }

    function delete_question($team_id, $box_id, $question_id) {
        $team_id = mysql_escape_string($team_id);
        $query = "delete from questions where team_id='$team_id' AND box_id=$box_id AND id=$question_id";
        return db_query($query);
    }

    function delete_all_questions_in_box($team_id, $box_id) {
        $team_id = mysql_escape_string($team_id);
        $query = "delete from questions where team_id='$team_id' AND box_id=$box_id";
        log_error($query);
        return db_query($query);
    }

    function store_access_token($team_id, $access_token) {
        $team_id = mysql_escape_string($team_id);
        $access_token = mysql_escape_string($access_token);
        $query = "insert into teams (
            `team_id`,
            `access_token`
        ) values (
            '$team_id',
            '$access_token'
        ) on duplicate key update `access_token`='$access_token'";
        return db_query($query);
    }
