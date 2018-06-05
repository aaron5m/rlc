/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the default JavaScript render.
 * See the summary section at bottom for more information.
 *
 * Aaron Mitchell 2018
 * meotterpaaronmitchellottercom
 * (change the aquatic mammal instances
 *  into the appropriate symbols for an email address)
 *
 * MIT License
 *
 *


 *****************************************
 * A QUICK OVERVIEW FOLLOWED BY THE CODE *
 *****************************************

When PHP renders a CommentBoard it returns a JSON string,
which can then be parsed by JavaScript into an object.
Each node in the object is a comment object with these properties:

node.id		= a unique identifier for this comment, alphanumeric
node.comment 	= the comment text in HTML
node.commenter	= the commenter who made the comment
node.commenterBan
		= whether the commenter has since been banned or not
node.netVotes	= the total +upvotes and -downvotes on the comment
node.userVote	= this particular user's vote on this comment (+1/-1/0)
node.children	= the nodes within this node
node.parent 	= the id of the comment to which this comment replied,
		   or the id of the board if it was a first comment

 
 *
 * THE DEFAULT RENDER
 **********************
 * 
 * The default JavaScript render is a recursive function.
 * Remember to parse the PHP board render and user information into objects
 * before passing them to the JavaScript render.
 * e.g. 
 *	var phpRender = '<?php echo $someCommentBoard->render(); ?>';
 *	var renderObj = JSON.parse(phpRender);
 *	var userJSON = '<?php echo $someCommenter->mini_json(); ?>';
 *	var userObj = JSON.parse(userJSON);
 *	defaultRender(renderObj, userObj);
 *
 * You may also pass other parameters to the render through the params array.
 *	defaultRender(renderObj, userObj, params);
 */


function defaultRender(renderObj, userObj, params) {

			// For the default render,
			// params[0] is a cutoff number of net votes,
			// i.e. if a comment's net votes are less than -2
			//  the comment will not be shown.

 if ( params === undefined || params[0] === undefined
  || isNaN(params[0]) || params[0] !== parseInt(params[0], 10) ) {
  
  var params = [-2];
 }

			// If this is the commentBoard itself, 
			// there is no parent. 
			// Offer to "make new comment" at the top.

 if ( renderObj.parent === undefined ) {
  var div = document.createElement("div");
  div.setAttribute("class", "rlc_first_comment");
  div.innerHTML = "make new comment";
  div.addEventListener("click", function() {
   firstComment(userObj, params);
  });
  document.getElementById(renderObj.id).appendChild(div);

			// If the user is signed in,
			// offer a chance to sign out,

  if ( userObj.isLoggedIn && userObj.username.length > 4 ) {
    var div = document.createElement("div");
    div.setAttribute("class", "rlc_sign_out");
    div.innerHTML = "sign out";
    div.addEventListener("click", signUserOut);
    document.getElementById(renderObj.id).appendChild(div);
  } else {

			// otherwise offer a chance to sign in or sign up.

   var div = document.createElement("div");
   div.setAttribute("class", "rlc_sign_in");
   div.innerHTML = "sign in";
   div.addEventListener("click", popMenu);
   document.getElementById(renderObj.id).appendChild(div);

   var div = document.createElement("div");
   div.setAttribute("class", "rlc_sign_up");
   div.innerHTML = "sign up";
   div.addEventListener("click", popMenu);
   document.getElementById(renderObj.id).appendChild(div);

  }

 }


			// For each child of this node 
			// (i.e. for each comment under this one)...

 var children = renderObj.children;
 Object.keys(children).forEach(function (key) {

			// (If it's already on the board
			//  for example, when render is called from show more,
			//  just continue to the next key.)

  if ( document.getElementById(children[key].id) ) {
   return;
  }

			// Create a container.	

  var div = document.createElement("div");
  div.setAttribute('id', children[key].id);
  div.setAttribute('class', 'rlc_comment_container');
  document.getElementById(children[key].parent).appendChild(div);


			// Create an upvote button...

  var upvote = document.createElement("div");
  upvote.innerHTML = "&uarr;";

			// and set the class of the upvote button 
			// according to this user's vote on the comment.

  if ( children[key].userVote == 1 ) {
   upvote.setAttribute('class', 'rlc_upvoted');
  } else {
   upvote.setAttribute('class', 'rlc_upvote');
   upvote.addEventListener("click", upvoteThis);
  }
  document.getElementById(children[key].id).appendChild(upvote);


			// Create a downvote button...

  var downvote = document.createElement("div");
  downvote.innerHTML = "&darr;";

			// and set the class of the downvote button 
			// according to this user's vote on the comment.

  if ( children[key].userVote == -1 ) {
   downvote.setAttribute('class', 'rlc_downvoted');
  } else {
   downvote.setAttribute('class', 'rlc_downvote');
   downvote.addEventListener("click", downvoteThis);
  }
  document.getElementById(children[key].id).appendChild(downvote);


			// Show the net votes on this comment.

  var netVotes = document.createElement("div");
  netVotes.innerHTML = children[key].netVotes;
  netVotes.setAttribute('class', 'rlc_netvotes');
  document.getElementById(children[key].id).appendChild(netVotes);


			// Show the commenter who made this comment.
			// Strikeout if banned.

  var commenter = document.createElement("div");
  commenter.innerHTML = children[key].commenter;
  if ( children[key].commenterBan ) {
   commenter.setAttribute("class", "rlc_banned_commenter");
  } else {
   commenter.setAttribute("class", "rlc_commenter");
  }
  document.getElementById(children[key].id).appendChild(commenter);

			// If the commenter is this user, show an edit button.

  if ( userObj.username === children[key].commenter ) {
   var edit = document.createElement("div");
   edit.innerHTML = "edit";
   edit.setAttribute("class", "rlc_edit");
   edit.addEventListener("click", editThis);
   document.getElementById(children[key].id).appendChild(edit);
  }


			// If this user is a mod and did not make this comment,
			// and the commenter is not banned,
			// show the option to ban the commenter.

  if ( userObj.isMod && userObj.username != children[key].commenter
   && !children[key].commenterBan ) {
   var ban = document.createElement("div");
   ban.innerHTML = "ban";
   ban.setAttribute("class", "rlc_ban");
   ban.addEventListener("click", banCommenter);
   document.getElementById(children[key].id).appendChild(ban);
  }


			// If this user is a mod and did not make this comment,
			// and the commenter is banned,
			// show the option to unban the commenter.

  if ( userObj.isMod && userObj.username != children[key].commenter
   && children[key].commenterBan ) {
   var ban = document.createElement("div");
   ban.innerHTML = "un-ban";
   ban.setAttribute("class", "rlc_un_ban");
   ban.addEventListener("click", unbanCommenter);
   document.getElementById(children[key].id).appendChild(ban);
  }

			// Show the comment, unless...

  var comment = document.createElement("div");
  comment.setAttribute("class", "rlc_comment");
  document.getElementById(children[key].id).appendChild(comment);
  if ( children[key].netVotes >= params[0] ) {
   comment.innerHTML = children[key].comment;

			// if the comment's net votes are below the cutoff
			// show that, with an option for the comment anyways.
  } else {
   comment.innerHTML = "";
   var see = document.createElement("span");
   see.setAttribute("class", "rlc_see_anyways");
   see.innerHTML = "comment below threshold. see anyways?";
   see.addEventListener("click", function() {
    comment.innerHTML = children[key].comment;
    this.remove();
   });
   comment.appendChild(see);
  } 


			// If this user is a mod and did not make this comment,
			// and the comment is not empty,
			// show the option to delete the comment.

  if ( userObj.isMod && userObj.username != children[key].commenter
   && children[key].comment.trim() != '' ) {
   var delt = document.createElement("div");
   delt.innerHTML = "delete";
   delt.setAttribute("class", "rlc_delete");
   delt.addEventListener("click", deleteThis);
   document.getElementById(children[key].id).appendChild(delt);
  }


			// If this user did not make this comment,
			// show a reply button
			// for making a reply to this comment.

  if ( userObj.username != children[key].commenter ) {
   var reply = document.createElement("div");
   reply.setAttribute("class", "rlc_reply");
   reply.innerHTML = "reply";
   reply.addEventListener("click", function() {
    replyToThis(children[key].id, userObj, params);
   });
   document.getElementById(children[key].id).appendChild(reply);
  }


			// If the node has more replies than what are shown, 
			// add a button to show more.
			// (But the button may end up moved
			//  further down after all comments are displayed.)

  var kidsHere;
  if ( typeof children[key].children === 'undefined' ) {
   kidsHere = 0;
  } else {
   kidsHere = Object.keys(children[key].children).length;
  }
  if ( children[key].hasChildren > kidsHere ) {
    var more = document.createElement("div");
    more.setAttribute("class", "rlc_show_more");
    more.innerHTML = "show more";
    more.addEventListener("click", function() {
     showMore(children[key].id, userObj, params);
     this.remove();
    });
    document.getElementById(children[key].id).appendChild(more);

  }


			// And do all this again
			// for every reply to this comment in the JSON
			// and their replies, and their replies' replies, etc.

  if (children[key].children !== undefined) {
   return defaultRender(children[key], userObj, params);
  }
 });

			// By now the render is finished
			// (all of the comments are on the page).
			// Here you can further adjust the board,
			// for example, moving the show more buttons
			// to underneath their comment's replies...

 var showMoreButtons = document.getElementsByClassName("rlc_show_more");
 Object.keys(showMoreButtons).forEach(function (key) {
  showMoreButtons[key].parentNode.appendChild(showMoreButtons[key]);
 });

}



/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

When PHP renders a CommentBoard it returns a JSON string,
which can then be parsed by JavaScript into an object.
Each node in the object is a comment object with these properties:

node.id		= a unique identifier for this comment, alphanumeric
node.comment 	= the comment text in HTML
node.commenter	= the commenter who made the comment
node.netVotes	= the total +upvotes and -downvotes on the comment
node.userVote	= this particular user's vote on this comment (+1/-1/0)
node.children	= the nodes within this node
node.parent 	= the id of the comment to which this comment replied,
		   or the id of the board if it was a first comment

The default JavaScript render is a recursive function.
Remember to parse the PHP board render and user information into objects
before passing them to the JavaScript render.
 e.g. 
	var phpRender = '<?php echo $someCommentBoard->render(); ?>';
	var renderObj = JSON.parse(phpRender);
	var userJSON = '<?php echo $someCommenter->mini_json(); ?>';
	var userObj = JSON.parse(userJSON);
	defaultRender(renderObj, userObj);

You may also pass other parameters to the render through the params array.
	defaultRender(renderObj, userObj, params);

The render organizes comments in a tree.
Each comment is shown with its net votes, the commenter who made it,
and with AJAX options for other users (or for the commenter who made it)
to upvote, downvote, reply, edit, delete, ban, see more replies, etc.

The appearance and even to some degree the placement of all these options
can be customized through a stylesheet.
The default styling is taken from the css folder: css/default.css

 ***************************************************************
                                                               */