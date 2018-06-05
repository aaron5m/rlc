<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the un-ban script for AJAX calls.
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

			// If this is not a signed-in moderator, 
			// no un-ban allowed.
			// Echo JSON status error "invalid user" and die.

$commenter = new Commenter();
if ( !isset($_COOKIE["rlc_commenter"]) 
 || !isset($_COOKIE["rlc_commenter_id"])
 || !($commenter->confirm(
	$_COOKIE["rlc_commenter"], $_COOKIE["rlc_commenter_id"], true))
 || !($commenter->isMod) ) 
{
 echo '{"status":"invalid moderator"}';
 die();
}
			// If this is not POST, no un-ban allowed.
			// Echo JSON status error "invalid post" and die.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 echo '{"status":"invalid post"}';
 die();
}
			// If this commenter does not exist, no un-ban allowed.
			// Echo JSON status error "invalid commenter" and die.

if ( !file_exists(RLC_PATH."/commenters/".$_POST["commenter"].".txt") ) {
 echo '{"status":"invalid commenter"}';
 die();
}

			// If everything has checked out so far, 
			// instantiate and load the commenter.

$commenter = new Commenter();
$commenter->load($_POST["commenter"]);

			// If restore has been passed as 1 restore comments
			// with the un-ban. Otherwise, just un-ban.

if ( isset($_POST["restore"]) && intval($_POST["restore"]) === 1 ) {
 $restore = TRUE;
} else {
 $restore = FALSE;
}
$commenter->undo_ban($restore);
					
					// Echo the JSON response.

echo '{"status":"OK"}';



?>