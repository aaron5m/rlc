# RLC

A reddit-like comment system in PHP, JavaScript, and JSON.

### Prerequisites

PHP 5 >= 5.2.0

## Getting Started

The simplest way to use the RLC comment system is the just_use_rlc.php script.

There are ONLY 3 STEPS.

	(1) Put your rlc_system folder in the same folder 
	    as the pages of your site on which you want comments.

	(2) Open this just_use_rlc.php file and change the line that says

		$yourModeratorUsername = "CHANGE-THIS-PART!";

	    to whatever your particular username will be.
	    Once you've signed up, your moderator status will kick in.

	(3) Copy this line of code into every page on which you want comments,
	    wherever you want them, but no more than one time per page.

		<div><?php include("rlc_system/just_use_rlc.php"); ?></div>

[rlc snapshot](rlc_picture.jpg)

## A Broader Overview

### PHP

There are 3 classes and 2 interfaces in PHP in this RLC system.

The CommentBoard class in the php/commentboard.php file is a container of all the unique IDs of comments on the board. There is one board per webpage.

The Commenter class in the php/commenter.php file stores the information of a particular commenter, including password, username, and the unique IDs of the comments the user has made.

The Comment class in the php/comment.php file stores the information for a particular comment, including its unique ID, the comment to which it replied and the comments replying to it, its upvotes, and the commenter who made it.

The Scoring interface in the php/scoring.php file is a method for scoring comments relative to each other, to determine which ones go higher on a board, making them therefore more readily seen by a reader.

The Rendering interface in the php/rendering.php file is a method for rendering a particular comment board into nested JSON, so that it may be manipulated with JavaScript/jQuery for display.

There are also a number of AJAX files in the php folder.

The AJAX files work in tandem with JavaScript to allow users to interact with the board (i.e. to make replies and comments, and to upvote and downvote comments, etc).

IMPORTANT NOTES ABOUT THE PHP FILES

If you are using the rlc system across multiple folders, you should define a path (RLC_PATH) to the rlc_system folder in the php/Constants.php file. Including that file then into all of your PHP scripts will allow them to find the classes they need.

You may also define an RLC_SECRET_PATH in the php/Constants.php file for a folder in which to store files for your comment boards, commenters, and comments - these files may have sensitive data like password hashes.

### JavaScript and CSS

There are 2 important aspects of JavaScript in RLC.

First, JavaScript renders the JSON from PHP onto a page. This is accomplished through the defaultRender function in the js/defaultRender.js file. Without this rendering there would be no HTML for a reader to see in the browser.

Second, JavaScript provides AJAX functionality with the JavaScript rendered comment board. This is accomplished with a series of functions in the js/defaultAjax.js file. Without this functionality, users would not be able to upvote, downvote, or make comments on the comment board.

Comment boards can by styled in the css/default.css file.

IMPORTANT NOTES ABOUT THE JAVASCRIPT AND CSS FILES

It is likely that a particular JavaScript render must work in tandem with a particular set of JavaScript AJAX functions. That is how the js/defaultRender.js and js/defaultAjax.js files are set up. Their styling can be somewhat altered through the css/default.css file. However, for great alterations all 3 of these files should be considered their own system, and changed to work together.

Also, if you are using the rlc system across multiple folders, you must take into account the relative path from your JavaScript to your PHP scripts. The js/defaultAjax.js file will look for a global variable called *ajaxPath* for a path to the PHP scripts; if it does not find the variable, it looks in 'rlc_folder/php'.

### An Example Implementation

Define the location of your secret folder and RLC folder in php/Constants.php.

	define('RLC_SECRET_PATH', '/home/user');
	define('RLC_PATH', '/home/user/public_html');

Now all your RLC scripts can find each other.

Require all of the RLC classes and interfaces in your script.

	include('path/to/Constants.php');
	require_once(RLC_PATH.'/php/rendering.php');
	require_once(RLC_PATH.'/php/scoring.php');
	require_once(RLC_PATH.'/php/commenter.php');
	require_once(RLC_PATH.'/php/commentboard.php');
	require_once(RLC_PATH.'/php/comment.php');

Commenters can sign up with the sign up method.

	$commenter = new Commenter();
	$id = $commenter->sign_up($username, $password1, $password2);

As with the sign in method, an ID is returned that can be used - for example, in a cookie - to confirm commenters later. The ID is erased when a commenter signs out. And all previous IDs are erased when a commenter changes a password.

	$commenter = new Commenter();
	$id = $commenter->sign_in($u, $pw);
	// ... time passes ...
	$c = new Commenter();
	if ( $c->confirm($u, $id) ) echo 'You came back!';
	if ( $id = $c->change_password($u, $oldPW, $pw1, $pw2) ) echo 'OK'; 
	if ( $c->sign_out($u, $id) ) echo 'Alas!';

Commenters can also be banned or made mods.

	$c->ban();			// You're terrible!
	$c->undo_ban();		// No, you're not so bad...
	$c->make_mod(TRUE);	// Actually, I like you a lot!

Once a commenter is verified, comments can be added to the comment board. It is important to use the comment board methods rather than directly manipulating comment objects. A first comment goes directly onto the commentboard, i.e. a response to the page itself.

	$cb = new CommentBoard("string");
	// ...
	if ( !$commenter->confirm($u, $id) ) {
	 $cb->make_comment("A first comment.", $username);
	}

A reply goes under a previous comment.

	// ...
	$cb->make_reply($prevCommentId, "I know you!", $username);

A comment board can be scored, so that the better and newer comments and replies rise to the top. This is done through scoring interfaces and their parameters. Depending on the size of your comment board folders, you may wish to schedule scoring through cron.

	$scoring = new ScoreByLower();
	$cb->score_the_comments($scoring, ["rate"=>30]);

A comment board is rendered into JSON through a rendering interface and its parameters. The default render takes optional parameters of "username" (to display an individual's previous upvotes, downvotes, etc. on comments) and "limit" for number of comments to show.

	$rendering = new RenderByDefault();
	$cbJson = $cb->render($rendering, ["username"=>$username]);

The JSON returned from PHP can then be manipulated through JavaScript or jQuery for display. If you use the defaultRender.js and defaultAjax.js scripts, make sure to help your AJAX calls find your PHP by setting the ajaxPath.

	<script src="path/to/defaultRender.js"><script>
	<script src="path/to/defaultAjax.js"></script>
	<script>
	 var ajaxPath = 'path/to/rlc_system/php;
	 ...

Whether using the default JavaScript render or a render of your own, the comment board's JSON must be parsed and rendered to be displayed. The default JavaScript render takes a user object (to display an individual's previous upvotes, downvotes, etc.) and an optional cutoff parameter to hide comments below a certain karma. The user object can be returned as JSON from a PHP commenter object.

	... // first in PHP
	$uJson = $commenter->mini_json();
	... // then later that same night in JavaScript
	 var cbObj = JSON.parse('<?php echo $cbJson; ?>');
	 var userObj = JSON.parse('<? echo $uJson; ?>');
	 defaultRender(cbObj, userObj, -2);

The default.css file can be used to alter the display of the default JavaScript render.

## Important Note About CAPTCHA

The captcha included in the RLC system is weak and should not be relied upon, most especially since it is included here in the code.

**You should work in your own captcha.**

But if you're not technical enough for that yet, to make the included captcha stronger you can **change out the sets in php/ajax_CaptchaSet.php** with your own. All it takes is a little brain-power. Think of at least 10 things that are alike and 1 that is obviously different to a human reasoner. Make at least 10 sets.

	// in file captcha/set.php
	$sets = array( 
	 "triangle"=>array("triangle", "puppy", "calf", "cub", "yearling",
	  "piglet", "foal", "fawn", "lamb", "kitten"),
       ...
      );
     	// "triangle" has nothing to do with young animals

Also, if you are using the included captcha, make sure to load the js/captcha.js script in your page with sign up.

	// in your page where people are signing up to comment...
	<script src="js/captcha.js"></script>

IMPORTANT: The default php/ajax_signup.php script will not work without a session variable "captcha" set to "human". **When you implement your own captcha you must delete these 4 lines from php/ajax_signup.php**.

	// delete these lines in php/ajax_signup.php
	if ( @ $_SESSION["captcha"] != "human" ) {
	 echo 'Something is wrong. Please refresh the page and try again.';
	 die();
	}

## Demo

You can see a working example of the RLC system at 

https://paaronmitchell.com/rlc

## Serious Limitations of RLC

The RLC system does a lot of re-reading and re-writing of the same information. This is a failure on the part of yours truly. I thought I could get the file locks to work across instances (on construct and destruct) but I couldn't. So, every object-changing method now re-reads and re-writes a file for the most part. That probably won't hurt a normal-sized site, but it's a waste.

I'd like to fix that in the future.

## A Note About Friendliness

This project was a way for me to stumblingly teach myself a little more about OOP. I have gotten a good bit wrong and done some things foolishly. I would be grateful for help and correction.

## Authors

Aaron Mitchell

meotterpaaronmitchellottercom 

(change the aquatic mammal instances to the appropriate symbols for an email)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## Acknowledgments

There were a lot of people who asked and answered questions on https://stackoverflow.com whose code is now in RLC - in fact, I took some of it pretty much cut and paste. The same for PHP.net comments. I did a poor job keeping track of who I took from. So, I am sorry for that. But thank you to all of you.
