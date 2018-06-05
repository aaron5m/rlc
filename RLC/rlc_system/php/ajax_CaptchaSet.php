<?php Namespace rlc;
session_start();

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains a very simple CAPTCHA preparation.
 * See the summary section at bottom for a quick overview.
 *
 ********************************************************************
 *
 * IMPORTANT! - The default ajax_signup.php file will NOT WORK
 * without a captcha variable in session set to human.
 * e.g.
 *
 *  if ( @$_SESSION["captcha"] != "human" ) die(); // Sign-up fails!
 *
 ********************************************************************
 *
 * Aaron Mitchell 2018
 * meotterpaaronmitchellottercom
 * (change the aquatic mammal instances
 *  into the appropriate symbols for an email address)
 *
 * MIT License
 *
**/

	// Each set should have one thing that is not like the others.
	// That unlike thing is the answer and key of the set.

$sets = array(

 "triangle"=>array("kitten", "puppy", "calf", "cub", "yearling",
   "piglet", "foal", "fawn", "lamb", "triangle"),

 "elbow"=>array("square", "rectangle", "hexagon", "parallelogram",
   "rhombus", "pentagon", "triangle", "octagon", "trapezoid", "elbow"),

 "paper"=>array("toe", "finger", "elbow", "shoulder", "nose", "eye",
   "knee", "ear", "chin", "heel", "paper"),

 "puppy"=>array("pen", "pencil", "eraser", "paper", "ruler", 
   "calculator", "scissors", "marker", "paperclip", "puppy")

);

	// If the user is just blasting through captcha sets,
	// tell them to slow down.

if ( isset($_SESSION["captchaTimestamp1"]) 
 && $_SESSION["captchaTimestamp1"] > time() - 10 ) {
 echo '["You are going too fast. Please count to 15 and try again."]';
 die();
}
$_SESSION["captchaTimestamp1"] = time();

	// Choose a set at random and store the answer in session.

$answer = array_rand($sets);
$set = $sets[$answer];
shuffle($set);
$_SESSION["captcha"] = $answer;

	// Encode the set as JSON and send back to JavaScript AJAX call.

echo json_encode($set);
exit();

/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

The default captcha for RLC is a simple association question. 

The answer is stored in session through this php/ajax_CaptchaSet.php script, which the JavaScript function showCaptcha in js/captcha.js calls. That function also parses the answer choices sent back and displays them on the page. 

An answer is attempted with the JavaScript function tryCaptcha in js/captcha.js, which checks the answer through the php/ajax_CaptchaCheck.php script. If the answer is correct, that script sets "captcha" to "human" in session and the original signup function proceeds.

IMPORTANT: The default ajax_signup.php file will NOT WORK without a captcha variable in session set to human.
e.g.

	if ( @$_SESSION["captcha"] != "human" ) die(); // Sign-up fails!

/*
 ***************************************************************
                                                               */

?>