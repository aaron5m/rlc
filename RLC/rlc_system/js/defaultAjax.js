/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains JavaScript AJAX calls.
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



			// The relative path to your PHP scripts
			// should be set as a global variable.

if ( ajaxPath === undefined ) {
 var ajaxPath = 'rlc_system/php';
}

/**				
 * POP MENU *			
 ************/		
			// A pop-up asking the user to sign up or sign in.
			// This function was added to the rlc_sign_up class
			// and the rlc_sign_in class during render.
	
			// (NOTE: the use of rlc_sign_up/in in the function
			//  is on ids, not on classes as in the render.)

			// Also, if the user is not signed in, 
			// this pops up instead of anything working below.

function popMenu() {
 var pop = document.getElementById("rlc_pop");
 pop.parentNode.style.display = "block";
 document.getElementById("rlc_sign_in").addEventListener("click", signIn);
 document.getElementById("rlc_sign_up").addEventListener("click", signUp);
}



/**				
 * THE SIGNUP FUNCTION * 	
 ***********************/ 

			// The captcha parameter should be TRUE
			// if the user has been confirmed as human.	

function signUp(captcha) {

			// Gets the username and passwords from the pop menu.

 var username = encodeURIComponent(document.getElementById("username").value);
 var password1 = encodeURIComponent(document.getElementById("password1").value);
 var password2 = encodeURIComponent(document.getElementById("password2").value);

			// SUGGESTION: Implement your own captcha
			// and pass it with above values to ajax_signup.php.
			// The default captcha simply recalls this function
			// with captcha parameter TRUE.
			// IMPORTANT: The default ajax_signup.php script
			// requires a session variable "captcha" set "human".
			//  $_SESSION["captcha"] = "human";

 if ( captcha !== true ) {
  return showCaptcha(signUp);
 }

			// Passes the username and passwords to PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_signup.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// PHP returns "OK" for a successful signup.

  if ( this.responseText.trim() == "OK" ) {
   window.location.reload(true);
  } else {

			// Otherwise the PHP error is shown on the pop menu.

   var error = document.createElement("div");    
   error.setAttribute("class", "rlc_pop_error");
   error.innerHTML = this.responseText;
   document.getElementById("rlc_pop").appendChild(error);
  }
 };
 xhr.send("username="+username+"&password1="+password1+"&password2="+password2);


}



/**
 * THE SIGNIN FUNCTION * 	
 ***********************/ 

function signIn() {

			// Gets the username and password from the pop menu.

 var username = encodeURIComponent(document.getElementById("username").value);
 var password1 = encodeURIComponent(document.getElementById("password1").value);

			// Passes them to PHP for signin.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_signin.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// PHP returns "OK" for a successful signin,
			// at which point the page reloads.

  if ( this.responseText.trim() == "OK" ) {
   window.location.reload(true);
  } else {

			// Otherwise the PHP error is shown on the pop menu.

   var error = document.createElement("div");    
   error.setAttribute("class", "rlc_pop_error");
   error.innerHTML = this.responseText;
   document.getElementById("rlc_pop").appendChild(error);
  }
 };
 xhr.send("username="+username+"&password1="+password1);

}



/**
 * THE SIGNUSEROUT FUNCTION * 		
 ****************************/ 	

			// The signUserOut function was added to the 
			// (click event of the)
			// rlc_sign_out class during render.

function signUserOut() {

			// Passes the call to signout to PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_signout.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// Reloads the page on a successful signout.

  if ( this.responseText.trim() == "OK" ) {	
    window.location.reload(true);
  }

 };
 xhr.send();

}


/**
 * THE FIRST COMMENT FUNCTIONS *	
 *******************************/

			// The firstComment function was added to the 
			// (click event of the)
			// rlc_first_comment class during render.

			// NOTE: the first comment essentially disables 					// itself when finished -
			// so a user cannot make two first comments 						// without reloading the page.

function firstComment(userObj, params) {

			// By default,
			// params[0] is a cutoff number of net votes,
			// i.e. if a comment's net votes are less than -2
			//  the comment will not be shown.

 if ( params === undefined || params[0] === undefined
  || isNaN(params[0]) || params[0] !== parseInt(params[0], 10) ) {
  
  var params = [-2];
 }

			// The function checks for a valid user in PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_user.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// Asks an invalid user to sign up or sign in.

  var response = JSON.parse(this.responseText);
  if ( response.status == "invalid user" ) {	
   popMenu();
  } 

 };
 xhr.send();

			// If the user is valid,
			// the rlc_first_comment class
			// is cloned to remove click events, then blanked out.

 var button = document.getElementsByClassName("rlc_first_comment")[0];
 newButton = button.cloneNode(true);
 button.parentNode.replaceChild(newButton, button);
 newButton.innerHTML = '';


			// A textarea is appended
			// before the first comment container.

 var fc = document.createElement("textarea");
 fc.setAttribute("class", "rlc_first_comment_box");
 var ancestor = document.getElementsByClassName("rlc_comment_board")[0];
 var previous = ancestor.getElementsByClassName("rlc_comment_container")[0];
 ancestor.insertBefore(fc, previous);

			// A save button is appended under the textarea
			// so the user can save the comment
			// using the saveFirstComment function just below.

 var save = document.createElement("div");
 save.setAttribute("class", "rlc_save");
 save.innerHTML = "save";
 save.addEventListener("click", function() {
  saveFirstComment(userObj, params);
 });
 newButton.appendChild(save);

}

			// A first comment in a textarea is saved to PHP.

function saveFirstComment(userObj, params) {

			// By default,
			// params[0] is a cutoff number of net votes,
			// i.e. if a comment's net votes are less than -2
			//  the comment will not be shown.

 if ( params === undefined || params[0] === undefined
  || isNaN(params[0]) || params[0] !== parseInt(params[0], 10) ) {
  
  var params = [-2];
 }

			// The function gets the ID of the commentboard.

 var ancestorNode = document.getElementsByClassName("rlc_comment_board")[0];
 var ancestor = ancestorNode.id;

			// Gets the first comment from the textarea.

 var fc = encodeURIComponent(
  ancestorNode.getElementsByClassName("rlc_first_comment_box")[0].value);

			// Sends the first comment through PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_first.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// If PHP responds that the user is invalid
			// the user is asked to sign up or sign in.

  var response = JSON.parse(this.responseText);
  if ( response.status == "invalid user" ) {	
   popMenu();
  } else if ( response.status == "OK" ) {

			// PHP sends back "OK" for a successfully made comment.
			// A quick container is made.

   var div = document.createElement("div");
   div.setAttribute('id', response.parent);
   if ( previous = 
         ancestorNode.getElementsByClassName("rlc_comment_container")[0] ) {
    ancestorNode.insertBefore(div, previous);
   } else {
    ancestorNode.appendChild(div);
   }
			// And the comment is rendered.

   defaultRender(response, userObj, params);
  }

 };
 xhr.send("ancestor="+ancestor+"&fc="+fc);

			// At last the textarea and save button are removed.

 ancestorNode.getElementsByClassName("rlc_first_comment_box")[0].remove();
 document.getElementsByClassName("rlc_save")[0].remove();
}



/**
 * THE UPVOTE FUNCTION *		
 ***********************/ 	

			// The upvoteThis function was added to 
			// (click events of)
			// instances of rlc_upvote class during render.

function upvoteThis() {

			// Gets the ID of the commentboard.

 var ancestor = document.getElementsByClassName("rlc_comment_board")[0].id;

			// Gets the ID of which comment to upvote.

 var id = this.parentNode.id;

			// Calls the upvote through PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_upvote.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// If PHP responds that the user is invalid
			// the user is asked to sign up or sign in.

  var response = JSON.parse(this.responseText);
  if ( response.status == "invalid user" ) {	
    return popMenu();
  }

 };
 xhr.send("ancestor="+ancestor+"&id="+id);

			// The comment's netVotes display is changed.

 var netvotes = this.parentNode.getElementsByClassName("rlc_netvotes")[0];
 var bumpVotesUp = 1 + +netvotes.innerHTML;
 netvotes.innerHTML = bumpVotesUp;

			// The class and "click" of the paired rlc_downvote
			// are changed (if undoing a downvote)...

 var downvote = this.parentNode.getElementsByClassName("rlc_downvoted")[0];
 if ( downvote && downvote.parentNode == this.parentNode ) {
  downvote.setAttribute("class", "rlc_downvote");
  downvote.addEventListener("click", downvoteThis);
 } else {

			// or the class and "click" of rlc_upvote
			// are changed (if upvoting).

  this.setAttribute("class", "rlc_upvoted");
  this.removeEventListener("click", upvoteThis);
 }
}



/**
 * THE DOWNVOTE FUNCTION *	
 *************************/ 	

			// The downvoteThis function was added to 
			// (click events of)
			// instances of rlc_downvote class during render.

function downvoteThis() {

			// Gets the ID of the commentboard.

 var ancestor = document.getElementsByClassName("rlc_comment_board")[0].id;

			// Gets the ID of which comment to downvote.
 
 var id = this.parentNode.id;

			// Calls the downvote through PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_downvote.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// If PHP responds that the user is invalid
			// the user is asked to sign up or sign in.

  var response = JSON.parse(this.responseText);
  if ( response.status == "invalid user" ) {	
   popMenu();
  }

 };
 xhr.send("ancestor="+ancestor+"&id="+id);

			// The comment's netVotes display is changed.

 var netvotes = this.parentNode.getElementsByClassName("rlc_netvotes")[0];
 var bumpVotesDown = +netvotes.innerHTML - 1;
 netvotes.innerHTML = bumpVotesDown;

			// The class and "click" of the paired rlc_upvote
			// are changed (if undoing an upvote)...

 var upvote = this.parentNode.getElementsByClassName("rlc_upvoted")[0];
 if ( upvote && upvote.parentNode == this.parentNode ) {
  upvote.setAttribute("class", "rlc_upvote");
  upvote.addEventListener("click", upvoteThis);
 } else {

			// or the class and "click" of rlc_downvote
			// are changed (if downvoting).

  this.setAttribute("class", "rlc_downvoted");
  this.removeEventListener("click", downvoteThis);
 }

}


/**
 * THE REPLY FUNCTIONS *
 ***********************/	

			// The replyToThis function was added to 
			// (click events of)
			// instances of rlc_reply class during render.

			// NOTE: a reply essentially disables 							// other replies when finished -
			// so a user cannot reply twice to the same comment					// without reloading the page.

function replyToThis(id, userObj, params) {

			// By default,
			// params[0] is a cutoff number of net votes,
			// i.e. if a comment's net votes are less than -2
			//  the comment will not be shown.

 if ( params === undefined || params[0] === undefined
  || isNaN(params[0]) || params[0] !== parseInt(params[0], 10) ) {
  
  var params = [-2];
 }

			// The function checks for a valid user in PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_user.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// Asks an invalid user to sign up or sign in.

  var response = JSON.parse(this.responseText);
  if ( response.status == "invalid user" ) {	
   popMenu();
  } 

 };
 xhr.send();
			// If the user is valid,
			// the rlc_reply instance
			// is cloned to remove click events, then blanked out.

 var button = 
   document.getElementById(id).getElementsByClassName("rlc_reply")[0];
 newButton = button.cloneNode(true);
 button.parentNode.replaceChild(newButton, button);
 newButton.innerHTML = '';

			// A textarea is appended.

 var reply = document.createElement("textarea");
 reply.setAttribute("class", "rlc_reply_box");
 newButton.appendChild(reply);

			// A save button is appended under the textarea
			// so the user can save the reply
			// using the saveReplyToThis function just below.

 var save = document.createElement("div");
 save.setAttribute("class", "rlc_save");
 save.innerHTML = "save";
 save.addEventListener("click", function() { 
  saveReplyToThis(id, userObj, params);
 });
 newButton.appendChild(save);

}

			// A reply in a textarea is saved to PHP.

function saveReplyToThis(id, userObj, params) {

			// By default,
			// params[0] is a cutoff number of net votes,
			// i.e. if a comment's net votes are less than -2
			//  the comment will not be shown.

 if ( params === undefined || params[0] === undefined
  || isNaN(params[0]) || params[0] !== parseInt(params[0], 10) ) {
  
  var params = [-2];
 }

			// The function gets the ID of the commentboard
			// and ID of which comment to reply to.

 var ancestor = document.getElementsByClassName("rlc_comment_board")[0].id;
 var parent = document.getElementById(id);

			// Gets the reply text from the textarea.

 var reply = encodeURIComponent(parent.getElementsByClassName("rlc_reply_box")[0].value);

			// Sends the reply through PHP

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_reply.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// Asks an invalid user to sign up or sign in.

  var response = JSON.parse(this.responseText);
  if ( response.status == "invalid user" ) {	
   popMenu();
  } else if ( response.status == "OK" ) {

			// PHP sends back "OK" for a successfully made reply.
			// A quick container is made.

   var div = document.createElement("div");
   div.setAttribute('id', response.parent);
   if ( previous = 
             parent.getElementsByClassName("rlc_comment_container")[0] ) {
    parent.insertBefore(div, previous);
   } else {
    parent.appendChild(div);
   }

			// And the reply is rendered.

   defaultRender(response, userObj, params);
  }

 };
 xhr.send("ancestor="+ancestor+"&id="+id+"&reply="+reply);

			// The textarea and save button are removed.

 parent.getElementsByClassName("rlc_reply_box")[0].remove();
 parent.getElementsByClassName("rlc_save")[0].remove();
}


/**
 * THE EDIT FUNCTIONS *		
 **********************/ 	

			// The editThis function was added to 
			// (click events of)
			// instances of rlc_edit class during render.

function editThis() {

			// Gets the ID of the comment board.

 var ancestor = document.getElementsByClassName("rlc_comment_board")[0].id;

			// Gets the ID of which comment to edit.

 var id = this.parentNode.id;

			// Checks for a valid user in PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_editor.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

console.log(this.responseText);

			// An invalid user is asked to sign up or sign in.

  var response = JSON.parse(this.responseText);
  if ( response.status == "invalid user" ) {	
   popMenu();
  } else if ( response.status == "OK" ) {

			// The comment is blanked out and replaced
			// with a textarea showing the markup of the comment.

   var commentContainer = document.getElementById(response.id);
   var comment = commentContainer.getElementsByClassName("rlc_comment")[0];
   comment.innerHTML = '';
   var edit = document.createElement("textarea");
   edit.setAttribute("class", "rlc_edit_box");
   edit.innerHTML = response.text;
   comment.appendChild(edit);

  }

 };
 xhr.send("ancestor="+ancestor+"&id="+id);

			// The edit button is changed to "save" 
			// (not the same as an rlc_save button) 
			// and reassigned a new function on "click".

 this.innerHTML = "save";
 this.removeEventListener("click", editThis);
 this.addEventListener("click", saveEdit);

}

			// A comment edit in a textarea is saved to PHP.

function saveEdit() {

			// Gets the ID of the comment board.

 var ancestor = document.getElementsByClassName("rlc_comment_board")[0].id;

			// Gets the ID of which comment to edit.

 var id = this.parentNode.id;

			// Gets the newly edited text.

 var edit = encodeURIComponent(this.parentNode.getElementsByClassName("rlc_edit_box")[0].value);

			// Tries to send the edit through to PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_edit.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

			// An invalid user is asked to sign up or sign in.

  var response = JSON.parse(this.responseText);
  if ( response.status == "invalid user" ) {	
   popMenu();
  } else if ( response.status == "OK" ) {

			// PHP sends back "OK" for a successfully made edit.
			// The textarea is removed
			// and the new comment is shown.

   var commentContainer = document.getElementById(response.id);
   commentContainer.getElementsByClassName("rlc_edit_box")[0].remove();
   var comment = commentContainer.getElementsByClassName("rlc_comment")[0];
   comment.innerHTML = response.html;
  }
 }
 xhr.send("ancestor="+ancestor+"&id="+id+"&edit="+edit);

			// The rlc_edit button is changed back
			// and re-assigned its original function.

 this.innerHTML = "edit";
 this.removeEventListener("click", saveEdit);
 this.addEventListener("click", editThis);

}



/**
 * THE SHOW MORE FUNCTION *		
 **************************/ 		

			// The showMore function was added to 
			// (click events of)
			// instances of rlc_show_more during render.

function showMore(id, userObj, params) {

			// By default,
			// params[0] is a cutoff number of net votes,
			// i.e. if a comment's net votes are less than -2
			//  the comment will not be shown.

 if ( params === undefined || params[0] === undefined
  || isNaN(params[0]) || params[0] !== parseInt(params[0], 10) ) {
  
  var params = [-2];
 }

			// The function gets the ID of the comment board.

 var ancestor = document.getElementsByClassName("rlc_comment_board")[0].id;

			// Stores the button for possible removal.

 var button = 
   document.getElementById(id).getElementsByClassName("rlc_show_more")[0];

			// Sends the request through to PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_more.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

  var response = JSON.parse(this.responseText);
  if ( response.status == "OK" ) {

			// PHP sends back "OK" for a successful PHP render.
			// The rlc_show_more button is removed
			// and the PHP render (a JSON object)
			// is rendered through JavaScript/jQuery
			// for display on the page.

    button.remove();
    defaultRender(response.render, userObj, params);
  }
 }
 xhr.send("ancestor="+ancestor+"&id="+id);

}


/**
 * THE DELETE FUNCTIONS *	
 ************************/ 

			// The deleteThis function was added to 
			// (click events of)
			// instances of rlc_delete class during render.
			// NOTE: Only moderators have this option.

function deleteThis() {

			// Asks for confirmation.

 var confirm = document.createElement("div");
 confirm.innerHTML = "confirm?";
 confirm.setAttribute("class", "rlc_confirm_delete");
 confirm.addEventListener("click", confirmDelete);
 this.appendChild(confirm);
 this.removeEventListener("click", deleteThis);
}

function confirmDelete() {

			// Gets the ID of the comment board.

 var ancestor = document.getElementsByClassName("rlc_comment_board")[0].id;

			// Gets the ID of which comment to delete.

 var id = this.parentNode.parentNode.id;

			// Sends the delete through to PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_delete.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

  var response = JSON.parse(this.responseText);
  if ( response.status == "OK" ) {

			// PHP sends back "OK" for a successful delete.
			// The textarea is removed
			// and an empty comment is shown.
			// NOTE: Delete is the same as editing to "",
			//  but the comment always remains,
			//  for it may be an essential junction on the board.

   var commentContainer = document.getElementById(response.id);
   var comment = commentContainer.getElementsByClassName("rlc_comment")[0];
   comment.innerHTML = response.html;
  }
 }
 xhr.send("ancestor="+ancestor+"&id="+id);

			// The rlc_delete button is removed.

 this.parentNode.remove();

}



/**
 * THE BAN FUNCTIONS *
 *********************/

			// The banCommenter function was added to 
			// (click events of)
			// instances of rlc_ban class during render.
			// NOTE: Only moderators have this option.

function banCommenter() {

			// Gets the commenter to ban.

 var commenter = 
  this.parentNode.getElementsByClassName("rlc_commenter")[0].innerHTML.trim();

			// Asks for confirmation.

 var confirm = document.createElement("div");
 confirm.innerHTML = "just ban?";
 confirm.setAttribute("class", "rlc_confirm_ban");
 confirm.addEventListener("click", function() {
  confirmBan(commenter, 0);
 });
 this.appendChild(confirm);

			// And, alternatively, asks for confirmation of ban
			// with deletion of all commenter's comments.

 var confirmAndErase = document.createElement("div");
 confirmAndErase.innerHTML = "OR ban and erase all comments?";
 confirmAndErase.setAttribute("class", "rlc_confirm_ban");
 confirmAndErase.addEventListener("click", function() {
  confirmBan(commenter, 1);
 });
 this.appendChild(confirmAndErase);

 this.removeEventListener("click", banCommenter);
}


function confirmBan(commenter, erase) {

			// Sends the ban through to PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_ban.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

console.log(this.responseText);

  var response = JSON.parse(this.responseText);
  if ( response.status == "OK" ) {

			// PHP sends back "OK" for a successful ban.
			// The page is refreshed.

   window.location.reload(true);
  }
 }
 xhr.send("commenter="+commenter+"&erase="+erase);

}



/**
 * THE UNBAN FUNCTIONS *
 ***********************/

			// The unbanCommenter function was added to 
			// (click events of)
			// instances of rlc_un_ban class during render.
			// NOTE: Only moderators have this option.

function unbanCommenter() {

			// Gets the commenter to un-ban.

 var commenter = 
  this.parentNode.getElementsByClassName("rlc_commenter")[0].innerHTML.trim();

			// Asks for confirmation.

 var confirm = document.createElement("div");
 confirm.innerHTML = "just un-ban?";
 confirm.setAttribute("class", "rlc_confirm_un_ban");
 confirm.addEventListener("click", function() {
  confirmUnban(commenter, 0);
 });
 this.appendChild(confirm);

			// And, alternatively, asks for confirmation of un-ban
			// with restoration of all commenter's comments.

 var confirmAndRestore = document.createElement("div");
 confirmAndRestore.innerHTML = "OR un-ban and restore all comments?";
 confirmAndRestore.setAttribute("class", "rlc_confirm_un_ban");
 confirmAndRestore.addEventListener("click", function() {
  confirmUnban(commenter, 1);
 });
 this.appendChild(confirmAndRestore);

 this.removeEventListener("click", unbanCommenter);
}


function confirmUnban(commenter, restore) {

			// Sends the un-ban through to PHP.

 var xhr = new XMLHttpRequest();
 xhr.open('POST', ajaxPath+'/ajax_unban.php', true);
 xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 xhr.onload = function () {

console.log(this.responseText);


  var response = JSON.parse(this.responseText);
  if ( response.status == "OK" ) {

			// PHP sends back "OK" for a successful un-ban.
			// The page is refreshed.

   window.location.reload(true);
  }
 }
 xhr.send("commenter="+commenter+"&restore="+restore);

}



/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

AJAX functions allow users to interact with the RLC system.
They are paired with appropriate PHP scripts.
For example, the JavaScript AJAX function upvoteThis
is paired with the PHP script ajax_upvote.php.

These ajaxFunctions are meant to be used with the JavaScript default render.
They may not work with other JavaScript/jQuery renders.

Some ajaxFunctions require a user object,
which should be received from PHP when the page is loaded...

	$commenterJSON = $commenter->mini_json();

Then parsed in JavaScript.

	var userObject = JSON.parse('<?php echo $commenterJSON; ?>');

 ****************************************************************

Here is an overview of the AJAX functions:

 popMenu(); 		Shows the user a pop-up menu to sign up or sign in.

 signUp();
 signIn();
 signUserOut();		Signs a user up, in, or out.

 firstComment(userObj);	Allows a user to make a first comment on the board.
			Note that a user object must be passed as a parameter,
			because every comment requires a commenter, 
			in this case userObj.username

 upvoteThis();
 downvoteThis(); 	Lets a user upvote or downvote a post.

 replyToThis(userObj); 	Lets a user reply to a post.
			Note again the requirement of a user object.

 editThis(); 		Lets a user edit a post.

 showMore(); 		Lets a user see more comments than currently displayed.

 deleteThis(); 		Lets a moderator delete a comment.

 banCommenter(); 	Lets a moderator ban a commenter.

 ***************************************************************
                                                               */