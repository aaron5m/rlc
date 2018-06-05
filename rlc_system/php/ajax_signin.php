<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the sign in script for AJAX calls.
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


			// If this is not POST, no signin.
			// Echo "invalid post" and die.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 echo 'Error: Invalid post.';
 die();
}

$commenter = new Commenter();
if ( $id = $commenter->sign_in($_POST["username"], $_POST["password1"]) ) {

			// If signup is successful, set cookies, and echo "OK".

 setcookie("rlc_commenter_id", $id, time() + 3600*24*30, "/");
 setcookie("rlc_commenter", $_POST["username"], time() + 3600*24*30, "/");
 echo 'OK';
 die();
}
			// Otherwise an Error message will be echoed
			// from the Commenter class->sign_in method.

?>