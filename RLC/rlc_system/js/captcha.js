/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains JavaScript AJAX calls for a very simple captcha.
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
			// A relative path to the PHP scripts.

if ( ajaxPath === undefined ) {
 var ajaxPath = 'rlc_system/php';
}

function showCaptcha(aFunction) {

	// Set the captcha and get the options for the pop-up menu.

 var captcha = document.createElement("div");
 captcha.setAttribute("class", "rlc_captcha");
 captcha.innerHTML = "one of these things is not like the others:<br>";
 document.getElementById("rlc_pop").appendChild(captcha);

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_CaptchaSet.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

	// Display the options in the pop-up menu.

  var options = JSON.parse(this.responseText);
  Object.keys(options).forEach(function (key) {
   
   var option = document.createElement("div");
   option.innerHTML = options[key];
   option.setAttribute("class", "rlc_captcha_choice");
   option.addEventListener("click", function () {
    tryCaptcha(options[key], aFunction);
   });
   captcha.appendChild(option);
   
  });

 };
 xhr.send();

}

function tryCaptcha(choice, aFunction) {

	// Remove the captcha options.

 var captcha = document.getElementsByClassName("rlc_captcha")[0];
 captcha.remove();

	// Try the chosen captcha option through PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_CaptchaCheck.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

	// If the answer was correct, PHP sends back "OK".
	// Do whatever function was being attempted
	//  (by default, the signup function).

  if (this.responseText === "OK") {
   aFunction(true);
  } else {

	// Otherwise show the PHP error somewhere
	// (by default, in the popup menu).

   var error = document.createElement("div");    
   error.setAttribute("class", "rlc_pop_error");
   error.innerHTML = this.responseText;
   document.getElementById("rlc_pop").appendChild(error);

  }

 };
 xhr.send("choice="+choice);

}



/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

The default captcha for RLC is a simple association question. 

The answer is stored in session through the php/ajax_CaptchaSet.php script, which the above JavaScript function showCaptcha calls. The showCaptcha function also parses the answer choices sent back from PHP and displays them on the page. 

An answer is attempted with the above JavaScript function tryCaptcha, which checks the answer through the php/ajax_CaptchaCheck.php script. If the answer is correct, that script sets "captcha" to "human" in session and the original signup function proceeds. 

IMPORTANT: The default ajax_signup.php file will NOT WORK without a captcha variable in session set to human.
e.g.

	if ( @$_SESSION["captcha"] != "human" ) die(); // Sign-up fails!

/*
 ***************************************************************
                                                               */


