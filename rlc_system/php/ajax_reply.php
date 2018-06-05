<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the reply script for AJAX calls.
 *
 * Aaron Mitchell 2018
 * meotterpaaronmitchellottercom
 * (change the aquatic mammal instances
 *  into the appropriate symbols for an email address)
 *
 * MIT License
 *
**/

@ include("Constants.php");	
if ( !defined('RLC_SECRET_PATH') ) {
 define('RLC_SECRET_PATH', dirname(__DIR__));
}
 
	// RLC_SECRET_PATH is the absolute path
	// to the folder with your commentboards, comments, and commenters.

require_once("scoring.php");
require_once("rendering.php");
require_once("commenter.php");
require_once("comment.php");
require_once("commentboard.php");

			// If this is not a signed-in user, no reply.
			// Echo the JSON status error "invalid user" and die.

$commenter = new Commenter();
if ( !isset($_COOKIE["rlc_commenter"]) 
 || !isset($_COOKIE["rlc_commenter_id"])
 || !($commenter->confirm(
	$_COOKIE["rlc_commenter"], $_COOKIE["rlc_commenter_id"], true)) ) 
{
 echo '{"status":"invalid user"}';
 die();
}
$username = $commenter->username;

			// If this is not POST, no reply.
			// Echo the JSON status error "invalid post" and die.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {			
 echo '{"status":"invalid post"}';
 die();
}
			// If this board does not exist, no reply.
			// Echo the JSON status error "invalid board" and die.

if ( !file_exists(RLC_SECRET_PATH."/boards/".$_POST["ancestor"]) ) {
 echo '{"status":"invalid board"}';
 die();
}
			// If the comment to reply to does not exist, no reply.
			// Echo the JSON status error "invalid comment" and die.

if ( !file_exists(RLC_SECRET_PATH."/boards/".
	$_POST["ancestor"]."/".$_POST["id"].".txt") ) 
{
 echo '{"status":"invalid comment"}';
 die();
}

			// If everything has checked out, 
			// instantiate the board and make reply.

$commentBoard = new CommentBoard($_POST["ancestor"]);
if ( $id = $commentBoard->make_reply(
		$_POST["id"], $_POST["reply"], $username) ) {

			// Prepare an array for the AJAX response
			// with a quick random temporary id for a container.

 $tempId = substr(str_shuffle(str_repeat(
	  '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
           mt_rand(1,20))),1,20);

 $comment = new Comment($_POST["ancestor"]);
 $comment->load($id);
 $renderedComment = $comment->comment;
 $response["status"] = "OK";
 $response["parent"] = $tempId;
 $response["children"][0]["parent"] = $tempId;
 $response["children"][0]["id"] = $id;
 $response["children"][0]["netVotes"] = 0;
 $response["children"][0]["userVote"] = 0;
 $response["children"][0]["comment"] = $renderedComment;
 $response["children"][0]["commenter"] = $username;
 $jsonResponse = json_encode($response);


			// Echo the JSON response for JavaScript.
 echo $jsonResponse;
}


?>