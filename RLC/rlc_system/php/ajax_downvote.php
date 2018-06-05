<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the downvote script for AJAX calls.
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

			// If this is not a signed-in user, no downvote.
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

			// If this is not POST, no downvote.
			// Echo the JSON status error "invalid post" and die.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {			
 echo '{"status":"invalid post"}';
 die();
}
			// If this board does not exist, no downvote.
			// Echo the JSON status error "invalid board" and die.

if ( !file_exists(RLC_SECRET_PATH."/boards/".$_POST["ancestor"]) ) {
 echo '{"status":"invalid board"}';
 die();
}
			// If this comment does not exist, no downvote.
			// Echo the JSON status error "invalid comment" and die.

if ( !file_exists(RLC_SECRET_PATH."/boards/".
	$_POST["ancestor"]."/".$_POST["id"].".txt") ) 
{
 echo '{"status":"invalid comment"}';
 die();
}

			// If everything has checked out, 
			// instantiate and load the comment and downvote.

$comment = new Comment($_POST["ancestor"]);
$comment->load($_POST["id"]);
if ( $comment->downvote($username) ) {

			// Echo the JSON status "OK".
 echo '{"status":"OK"}';
}


?>