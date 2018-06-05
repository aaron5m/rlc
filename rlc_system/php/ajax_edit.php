<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the edit script for AJAX calls.
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

			// If this is not a signed-in user, no edit.
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

			// If this is not POST, no edit.
			// Echo the JSON status error "invalid post" and die.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {			
 echo '{"status":"invalid post"}';
 die();
}
			// If this board does not exist, no edit.
			// Echo the JSON status error "invalid board" and die.

if ( !file_exists(RLC_SECRET_PATH."/boards/".$_POST["ancestor"]) ) {
 echo '{"status":"invalid board"}';
 die();
}
			// If this comment does not exist, no edit.
			// Echo the JSON status error "invalid comment" and die.

if ( !file_exists(RLC_SECRET_PATH."/boards/".
	$_POST["ancestor"]."/".$_POST["id"].".txt") ) 
{
 echo '{"status":"invalid comment"}';
 die();
}
			// If everything has checked out so far,
			// instantiate and load the comment.
 
$comment = new Comment($_POST["ancestor"]);
$comment->load($_POST["id"]);
					
			// If the commenter who made the comment
			// is not this user, no edit.
			// Echo the JSON status error "invalid user" and die.

if ( $comment->commenter != $_COOKIE["rlc_commenter"] ) {
 echo '{"status":"invalid user"}';
 die();
}

			// Edit the comment
			// and prepare an array for the AJAX response.

$comment->edit($_POST["edit"]);
$response["status"] = "OK";
$response["id"] = $_POST["id"];
$response["html"] = $comment->comment;
$jsonResponse = json_encode($response);

			// Echo the JSON response for JavaScript.
echo $jsonResponse;



?>