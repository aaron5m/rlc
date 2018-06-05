<?php Namespace rlc;
session_start();

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains a very simple CAPTCHA confirmation.
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

	// If the answer is incorrect, 
	// reset timestamp and send back NO.

if ( !isset($_POST["choice"]) 
 || $_POST["choice"] != $_SESSION["captcha"] ) {
  $_SESSION["captchaTimestamp2"] = time();
  echo "That answer was incorrect. Please count to 15 and try again.";
  die();
}

	// If the answer has come too quickly
	// reset timestamp and send back NO.

if ( isset($_SESSION["captchaTimestamp2"]) 
 && time() < $_SESSION["captchaTimestamp2"] + 10 ) {
  $_SESSION["captchaTimestamp2"] = time();
  echo "You are going too fast. Please count to 15 and try again.";
  die();
}

	// IMPORTANT!
	// If the answer is correct
	// SET "captcha" TO "human" IN SESSION and send back OK.

if ( isset($_POST["choice"]) 
 && $_POST["choice"] == $_SESSION["captcha"] ) {
  $_SESSION["captcha"] = "human";
  echo "OK";
  die();
}

/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

The default captcha for RLC is a simple association question. 

The answer is stored in session through the php/ajax_CaptchaSet.php script, which the JavaScript function showCaptcha in js/captcha.js calls. That function also parses the answer choices sent back from PHP and displays them on the page. 

An answer is attempted with the JavaScript function tryCaptcha in js/captcha.js, which checks the answer through this php/ajax_CaptchaCheck.php script. If the answer is correct, this script sets "captcha" to "human" in session and the original signup function proceeds. 

IMPORTANT: The default ajax_signup.php file will NOT WORK without a captcha variable in session set to human.
e.g.

	if ( @$_SESSION["captcha"] != "human" ) die(); // Sign-up fails!

/*
 ***************************************************************
                                                               */

?>