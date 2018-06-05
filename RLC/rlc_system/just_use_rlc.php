<?php Namespace rlc;			

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the just_use_rlc plugin script.
 * It is the fastest way to just start using rlc.
 * See the summary section at bottom for a quick overview.
 *
 * Aaron Mitchell 2018
 * meotterpaaronmitchellottercom
 * (change the aquatic mammal instances
 *  into the appropriate symbols for an email address)
 *
 * MIT License
 *
**/



/************************************************************************		
 * IMPORTANT: Change $yourModeratorUsername to your moderator username. *
 ************************************************************************/

 $yourModeratorUsername = "CHANGE_THIS_PART!";

/************************************************************************
 * Once you sign up and sign in with your moderator username            *
 * your moderator priveliges will come into effect.                     *
 ************************************************************************/

	// This script should not run itself.
if ( stripos($_SERVER["PHP_SELF"], "just_use_rlc.php") !== FALSE ) die();

			// **********************************************
			// SEMI-IMPORTANT: You may have to change this line
			// to a valid path to your Constants.php file
			// if you are running rlc across multiple folders.

@ include_once("php/Constants.php"); 

			// END SEMI-IMPORTANT / BUT ONE MORE ON THIS PAGE
			// **********************************************



if ( !defined('RLC_PATH') ) define('RLC_PATH', __DIR__);
if ( !defined('RLC_SECRET_PATH') ) define('RLC_SECRET_PATH', RLC_PATH);
 
	// RLC_PATH is the absolute path to your rlc_system folder.
	// RLC_SECRET_PATH is the absolute path
	// to the folder with your commentboards, comments, and commenters.

	// Require all the classes.

require_once(RLC_PATH."/php/scoring.php");
require_once(RLC_PATH."/php/rendering.php");
require_once(RLC_PATH."/php/comment.php");
require_once(RLC_PATH."/php/commentboard.php");
require_once(RLC_PATH."/php/commenter.php");

	// Instantiate a commenter.

$commenter = new Commenter();

	// Load a returning commenter, or a blank commenter for guest.

if ( isset($_COOKIE["rlc_commenter"]) 
 && isset($_COOKIE["rlc_commenter_id"])
 && $commenter->confirm(
       $_COOKIE["rlc_commenter"], $_COOKIE["rlc_commenter_id"], true) ) {

	// If this is a returning commenter, reset cookies.

 setcookie(
  "rlc_commenter_id", $_COOKIE["rlc_commenter_id"], time() + 3600*24*30, "/"
  );
 setcookie(
  "rlc_commenter", $_COOKIE["rlc_commenter"], time() + 3600*24*30, "/"
  );

	// If this commenter is the default moderator ($yourModeratorUsername)
	// make sure the commenter's mod status has been set to TRUE.

 if ( $commenter->username == $yourModeratorUsername && !$commenter->isMod ) {
  $commenter->make_mod(TRUE);
 }

	// Finally, set username to this user or to blank if guest.

 $username = $commenter->username;
} else {
 $username = '';
}

	// Make a minified JSON object of the user's public properties.
	// This will be passed to JavaScript for the JavaScript render.

$user = $commenter->mini_json();

	// Instantiate the comment board.

$commentBoard = new CommentBoard();

	// This script (just_use_rlc.php) reads
	// and writes a file "last_scoring.txt".
	// If it has been more than a minute since the last scoring,
	// your comment board will be scored, but otherwise not.
	// This is to reduce your server's workload
	// and distribute the scoring delay across users.

@ $lastScoring = file_get_contents(RLC_PATH."/last_scoring.txt");
if ( $lastScoring === FALSE
 || !is_numeric($lastScoring)
 || $lastScoring < time() - 60 ) {

 $commentBoard->score_the_comments();
 file_put_contents(RLC_PATH."/last_scoring.txt", time());
}

	// Store the comment board's ID and render.

$id = $commentBoard->id;
$jsonRender = $commentBoard->render(["username"=>$username]);

	// Plug in the CSS from css/default.css
	// NOTE: This is something of a hack.
	//  You can delete this part if you place
	// <link rel="stylesheet" type="text/css"
	//  href="rlc_system/css/default.css">
	// between the <head></head> tags of your page.

// ********************************************************

echo '<style type="text/css" scoped>'.
 file_get_contents(RLC_PATH."/css/default.css").'</style>';

// ********************************************************

	// Plug in the HTML and JavaScript.
?>

<div id="rlc_pop_container">
<div id="rlc_pop">
<input type="text" id="username" placeholder="username">
<p>
<input type="password" id="password1">
<p>
<span id="rlc_sign_in">Sign In</span>
<p>
<input type="password" id="password2">
<p>
<span id="rlc_sign_up">Sign Up</span>
</div>
</div>
<div class="rlc_welcome"><?php echo $username; ?></div>
<div id="<?php echo $id;?>" class="rlc_comment_board"></div>

<?
			// **********************************************
			// SEMI-IMPORTANT: You may have to change these lines
			// to valid paths to your JavaScript
			// if you are running rlc across multiple folders.
?>

<script src="rlc_system/js/defaultRender.js"></script>
<script src="rlc_system/js/defaultAjax.js"></script>
<script src="rlc_system/js/captcha.js"></script>
<script>
			// **********************************************
			// CONTINUE SEMI-IMPORTANT:
			// You may have to change this line
			// to a valid path to your ajax files
			// if you are running rlc across multiple folders.

var ajaxPath = 'rlc_system/php';

			// END SEMI-IMPORTANT
			// **********************************************


	// Parse the user JSON object from PHP into JavaScript.

var user = '<?php echo $user; ?>';
var userObj = JSON.parse(user);

	// Parse the board's rendering (JSON object) from PHP into JavaScript.

var render = '<?php echo $jsonRender; ?>';
var renderObj = JSON.parse(render);

	// Render the board.

defaultRender(renderObj, userObj);
</script>



<?php

/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

The just_use_rlc.php plugin script aims to be as simple as possible.

There are ONLY 3 STEPS.


(1) Put your rlc_system folder in the same folder 
    as the pages of your site on which you want comments.


(2) Open this file again and change the line that says

	$yourModeratorUsername = "CHANGE-THIS-PART!";

    to whatever your particular username will be.
    Once you've signed up, your moderator status will kick in.


(3) Copy this line of code into every page on which you want comments,
    wherever you want them, but no more than one time per page.

	<div><?php include("rlc_system/just_use_rlc.php"); ?></div>


That's it!

There is more information in the readme.md file
and in the inline documentation of each PHP, JavaScript, and CSS file.
 
 ***************************************************************
                                                               */
?>