<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the sign out script for AJAX calls.
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

			// If this is not a signed-in user, no signout.
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
			// If this is not POST, no signout.
			// Echo the JSON status error "invalid post" and die.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {			
 echo '{"status":"invalid post"}';
 die();
}

			// Otherwise, signout, unset cookies, and echo "OK".

$commenter->sign_out($_COOKIE["rlc_commenter"], $_COOKIE["rlc_commenter_id"]);
setcookie("rlc_commenter_id", "", time() - 3600*24*30, "/");
setcookie("rlc_commenter", "", time() - 3600*24*30, "/");
echo 'OK';

?>