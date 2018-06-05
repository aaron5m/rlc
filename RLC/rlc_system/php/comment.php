<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the Comment class.
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
			
if ( !defined('RLC_PATH') ) define('RLC_PATH', dirname(__DIR__));
if ( !defined('RLC_SECRET_PATH') ) define('RLC_SECRET_PATH', RLC_PATH);
 
	// RLC_PATH is the absolute path to your rlc_system folder.
	// RLC_SECRET_PATH is the absolute path
	// to the folder with your commentboards, comments, and commenters.	 



/*********************			
 * THE COMMENT CLASS *
 *********************/

class Comment {

 public $id; 		// String. 	
			// Unique identifier for this comment.

 public $comment;	// String.	
			// The comment, stored with HTML. 
			// Raw text (with markup) in $commentEdits.

 protected $commentEdits;	// Array. 	
				// 0 element is newest version of comment
				// (allows user edits).
				// NOTE: a protected property
				//  to access or change use the edit, restore,
				//  and get_current_edit methods.

 public $commenter;	// String. 	
			// The handle of the person who made this comment.

 protected $commenterBan;	// Boolean.
				// The status of the commenter, 
				// TRUE if banned, otherwise FALSE.
				// NOTE: a protected property,
				//  use the set_ban_to method to change.	

 public $ancestor;	// String. 	
			// ID of highest parent, i.e. the commentboard.
			// The "page" or "post" on which are all comments.

 public $parent;	// String. 	
			// ID of the comment to which this comment replies; 
			// can be same as ancestor.

 public $children;	// Array. 	
			// Key-value pairs of this comment's replies
			// with scores (e.g. replyId1=>0.5).

 public $descendants;	// Array.	
			// Key-value pairs for all replies
			// and replies' replies with scores.

 public $netVotes;	// Integer. 	
			// Net sum of +upvotes and -downvotes.

 public $totalVotes;	// Integer.	
			// Total sum of votes (+upvotes and +downvotes).

 public $voters;	// Array.	
			// Key-value pairs of who cast which vote 
			// (e.g. User1=>+1).

 public $score; 	// Double.	
			// Score for where to show this comment on the board. 					// See rlc_score function.

 public $timestamp;	// Timestamp. 	
			// The UTC timestamp of when the comment was made.

 /*
  * NOTE: The following properties are not written to file. *
  ***********************************************************/

 protected $writeFile;	// Boolean.	
			// If the comment is to be saved it needs a file.

 protected $filename;	// String.	
			// The filename for the comment.
			// RLC_SECRET_PATH.'/boards/'.$this->ancestor.'/'
			//  .$this->id.'.txt'

 protected $fh;		// File handle.
			// A reference to the file for this comment.

 protected $scoring;	// Interface.	
			// The way the comment is scored. 
			// Can be changed with this set_scoring method.

 public function set_scoring(Scoring $scoring) {
  $this->scoring = $scoring;
 }

 protected $rendering;	// Interface.	
			// The way the comment and descendants are rendered. 
			// Can be changed with this set_rendering method.

 public function set_rendering(Rendering $rendering) {
  $this->rendering = $rendering;
 }



/*
 * INSTANTIATING A COMMENT *
 ***************************/

 public function __construct($ancestor) {

			// Make sure the comment's board has a folder
			// that is protected from prying eyes.

  if ( !file_exists(RLC_SECRET_PATH) ) {
   mkdir(RLC_SECRET_PATH);
  }
  if ( !file_exists(RLC_SECRET_PATH."/boards") ) {
   mkdir(RLC_SECRET_PATH."/boards", 0700);
  }
  if ( !file_exists(RLC_SECRET_PATH."/boards/".$ancestor) ) {
   mkdir(RLC_SECRET_PATH."/boards/".$ancestor, 0700);
  }

  $this->ancestor = $ancestor;

			// Set the default scoring and rendering.

  $this->set_scoring(new ScoreByLower);
  $this->set_rendering(new RenderByDefault);

 }



/*
 * LOADING AN EXISTING COMMENT *
 *******************************/

			// Returns TRUE on successful file load.
			// Returns FALSE on failure.
			// Leave the optional $lock parameter as it is.

 public function load($id, $lock = LOCK_SH) {

			// Prepare the filename for this comment.

  $this->filename = RLC_SECRET_PATH."/boards/".$this->ancestor."/".$id.".txt";

			// Make sure the comment exists (has a file).

  try {
   if ( !($this->fh = fopen($this->filename, "r+")) ) {
    throw new \Exception('There is no file for that comment.');
   }
  } catch (\Exception $e) {
    echo 'Error: ',  $e->getMessage();
    return FALSE;
  }
			// Fail if unable to lock the file.

  if ( !flock($this->fh, $lock) ) {
    return FALSE;
  }

			// Make sure the file JSON can be unpacked to array.
 
  try {
   clearstatcache();
   rewind($this->fh);
   $infoJson = fread($this->fh, filesize($this->filename));
   if ( !($info = json_decode($infoJson, true)) ) {
    throw new \Exception('The comment JSON is malformed.');
   }
  } catch (\Exception $e) {
    echo 'Error: ',  $e->getMessage();
    @ flock($this->fh, LOCK_UN);
    return FALSE;
  }
			// Unpack and organize the comment's information. 

  $this->id 		= $info["id"];
  $this->comment	= $info["comment"];
  $this->commentEdits	= $info["commentEdits"];
  $this->commenter	= $info["commenter"];
  $this->commenterBan 	= $info["commenterBan"];
  $this->ancestor	= $info["ancestor"];
  $this->parent		= $info["parent"];
  $this->children	= $info["children"];
  $this->descendants	= $info["descendants"];
  $this->netVotes	= $info["netVotes"];
  $this->totalVotes	= $info["totalVotes"];
  $this->voters		= $info["voters"];
  $this->score		= $info["score"];
  $this->timestamp	= $info["timestamp"];

			// Return TRUE for successful file load.
  return TRUE;

 }



/************************************************************************
 * UTILITY METHODS: RELOAD AND REWRITE
 *
 * These functions are paired to protect comment files by locking them.
 * They are called together for every method that changes the comment.  *	
 ************************************************************************/

 protected function reload($tries = 1) {

			// Returns TRUE for file locked and loaded.
			// Returns FALSE if unable to get the lock.

  if ( $this->load($this->id, LOCK_EX | LOCK_NB) ) {
   return TRUE;
  } else {
			// Tries 5 times for the lock. Then gives up.

   usleep(50);
   if ( $tries >= 5 ) return FALSE;
   $tries += 1;
   return $this->reload($tries);
  }
 }

 //*********************************************************************

 protected function rewrite() {

			// Organize the comment's information...

  $info["id"] 		= $this->id;	
  $info["comment"] 	= $this->comment;
  $info["commentEdits"]	= $this->commentEdits;	
  $info["commenter"]	= $this->commenter;
  $info["commenterBan"]	= $this->commenterBan;	
  $info["ancestor"] 	= $this->ancestor;
  $info["parent"] 	= $this->parent;
  $info["children"] 	= $this->children;
  $info["descendants"]	= $this->descendants;
  $info["netVotes"] 	= $this->netVotes;
  $info["totalVotes"]	= $this->totalVotes;
  $info["voters"] 	= $this->voters;
  $info["score"]	= $this->score;
  $info["timestamp"] 	= $this->timestamp;

			// JSON encode...

  $infoJson = json_encode($info, JSON_PRETTY_PRINT);

			// Write the file and release the lock.

  rewind($this->fh);
  ftruncate($this->fh, 0);
  rewind($this->fh);
  fwrite($this->fh, $infoJson);
  fflush($this->fh);
  flock($this->fh, LOCK_UN);
  fclose($this->fh);

			// Return TRUE for successful rewrite.
  return TRUE;

 }	

/*
 ************************************************************************
                                                                        */



/*
 * CREATING A NEW COMMENT *	
 **************************/	

			// Avoid using this method.
			// Use instead the make_reply method 
			// on the CommentBoard class.

 public function create($parent, $comment, $commenter) {

			// Generate a random unique ID for this comment.

  $id = substr(str_shuffle(str_repeat(
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
         mt_rand(1,20))),1,20);

  while ( file_exists(
           RLC_SECRET_PATH."/boards/".$this->ancestor."/".$id.".txt") ) {

   $id = substr(str_shuffle(str_repeat(
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
         mt_rand(1,20))),1,20);
  }

			// Prepare a filename.

  $this->filename = RLC_SECRET_PATH."/boards/".$this->ancestor."/".$id.".txt";

			// Sanitize the comment allowing markup.

  $comment = preg_replace('/[^a-zA-Z0-9.,;:!?_\*\-\'\"\/\s ]/', '', $comment);
  $comment = htmlspecialchars($comment, ENT_QUOTES);

			// Convert the markup.
			// (This method is immediately below.)

  $convertedComment = $this->convert_markup($comment);

			// Organize the comment's information. 
			// 0s and empty arrays because just made.

  $info["id"]			= $this->id		= $id;
  $info["comment"]		= $this->comment	= $convertedComment;
  $info["commentEdits"][0]	= $this->commentEdits[0]= $comment;
  $info["commenter"]		= $this->commenter	= $commenter;
  $info["commenterBan"]		= $this->commenterBan 	= FALSE;
  $info["ancestor"]		= $this->ancestor;
  $info["parent"]		= $this->parent		= $parent;
  $info["children"]		= $this->children	= array();
  $info["descendants"]		= $this->descendants	= array();
  $info["netVotes"]		= $this->netVotes	= 0;
  $info["totalVotes"]		= $this->totalVotes	= 0;
  $info["voters"]		= $this->voters		= array();
  $info["score"]		= $this->score		= 0;
  $info["timestamp"]		= $this->timestamp	= time();

			// JSON encode...

  $infoJson = json_encode($info, JSON_PRETTY_PRINT);

			// Write the file.

  $this->fh = fopen($this->filename, "w");
  fwrite($this->fh, $infoJson);
  fclose($this->fh);

 }



/*
 * STATIC UTILITY METHOD : CONVERTING MARKUP *
 *********************************************/

 protected function convert_markup($comment) {

			// The comment is converted from simple markup.
			// Returns the comment.
			// Returns FALSE if comment is not a string.

			// NOTE: Simple markup is **bold**, *italic*, 
			//  http://web.url to clickable link,
			//  and double line-break to 
			//  <p class=rlc_spacer><\/p>. 

  try {
   if ( !is_string($comment) ) {
    throw new \Exception('That comment is not a string.');
   }
  } catch (\Exception $e) {
    echo 'Error: ',  $e->getMessage();
    return FALSE;
  }

  $comment = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$0</strong>', $comment);
  $comment = preg_replace('/\*\*/', '', $comment);
  $comment = preg_replace('/\*(.+?)\*/s', '<em>$0</em>', $comment);
  $comment = preg_replace('/\*/', '', $comment);
  $url = '/(http|https)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';   
  $comment = preg_replace($url, 
   '<a href=\\"$0\\" target=\\"_blank\\" title=\\"$0\\">$0</a>', $comment);
  $comment = preg_replace('/(\r?\n)\s*(\r?\n)/', 
				'<p class=rlc_spacer></p>', $comment);
  $comment = preg_replace('/\s+/', ' ', $comment);

			// (Note: foregoing proper CSS for sake of JSON.)

  return $comment;

 }



/*
 * EDITING THE COMMENT *
 ***********************/

			// Returns TRUE on successful edit.
			// Returns FALSE on failure.
			// Optionally set the number of comment edits to log. 		
			// Default is 0.

 public function edit($edit, $log_rlc_edits = 0) {

			// Fail if the comment is not a string.

  if ( !is_string($edit) ) return FALSE;

			// Fail if unable to reload the comment from file.

  if ( !$this->reload() ) return FALSE;

			// Sanitize the edit allowing markup.

  $edit = preg_replace('/[^a-zA-Z0-9.,;:!?_\*\-\'\"\/\s ]/', '', $edit);
  $edit = htmlspecialchars($edit, ENT_QUOTES);

			// Convert the markup and set.

  $convertedComment = $this->convert_markup($edit);
  $this->comment = $convertedComment;

			// If edits are not being logged, 
			// simply replace the previous edit with this one.

  if ( $log_rlc_edits == 0 ) {
   $this->commentEdits[0] = $edit;
  } else {
			// If edits are being logged, unshift in the new edit...

   array_unshift($this->commentEdits, $edit);

			// and pop off any old ones over the limit.

   $kount = count($this->commentEdits);
   while ( $kount > $log_rlc_edits + 1 ) {
    array_pop($this->commentEdits);
    $kount -= 1;
   }
  }
			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for successful edit.
  return TRUE;
		
 }



/*
 * GET CURRENT EDIT / VERSION OF THE COMMENT *
 *********************************************/

 public function get_current_edit() {
  return $this->commentEdits[0];
 }



/*
 * RESTORING THE PREVIOUS EDIT / VERSION OF THE COMMENT *
 ********************************************************/

			// Returns TRUE on successful restore.
			// Returns FALSE on failure.
			// NOTE: This function does not use reload/rewrite
			//  because it calls restore through the edit method.

 public function restore() {

			// Fail if there is no previous edit.

  if ( ( $kount = count($this->commentEdits) ) < 2 ) return FALSE;

			// Remove the current edit and re-edit with previous.

  array_shift($this->commentEdits);
  $restored = array_shift($this->commentEdits);

			// Fail if the edit does not go through.

  if ( !$this->edit($restored, $kount + 1) ) return FALSE;

			// Return TRUE for successful edit restore.
  return TRUE;
		
 }



/*		
 * LOGGING A CHILD OF THIS COMMENT *	
 ***********************************/	

			// Avoid using this method
			// or changing the children property directly.
			// Use instead the make_reply method 
			// on the CommentBoard class.

 public function log_child_score($replyId, $score = 0) {

			// Fail if unable to reload the comment from file.

  if ( !$this->reload() ) return FALSE;

			// Add the key-value pair 
			// of the reply id and its score 
			// to this comment's children.
    
  $this->children[$replyId] = $score;
 
			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for successful log.

  return TRUE;

 }



/*		
 * LOGGING A DESCENDANT OF THIS COMMENT *	
 ****************************************/	

			// Avoid using this method
			// or changing the descendants property directly.
			// Use instead the make_reply method 
			// on the CommentBoard class.

 public function log_descendant_score($descendantId, $score = 0) {

			// Fail if unable to reload the comment from file.

  if ( !$this->reload() ) return FALSE;

			// Add the key-value pair 
			// of the descendant id and its score 
			// to this comment's descendants.
    
  $this->descendants[$descendantId] = $score;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for successful log.

  return TRUE;

 }



/*
 * CHANGING THE COMMENTER STATUS ON THE COMMENT (BANNED OR NOT) *
 ****************************************************************/

			// Returns TRUE for a succesful change of the ban.
			// Returns FALSE on failure.
			// (Optional parameter $seriously 
			//  must be set to TRUE or FALSE
			//  to actually change the commenter's ban status.)

 public function set_ban_to($seriously = 'no') {

  if ( $seriously === TRUE || $seriously === FALSE ) {

			// Fail if unable to reload the comment from file.

   if ( !$this->reload() ) return FALSE;

			// Change the ban.

   $this->commenterBan = $seriously;

			// Fail if unable to rewrite the file.

   if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for successful ban change.
   return TRUE;
  }

  return FALSE;		// (If ban not changed, return FALSE.)
 }



/*
 * UPVOTING THIS COMMENT *	
 *************************/	

			// Returns TRUE on successful upvote.
			// Returns FALSE on failure.

 public function upvote($username) {

			// Make sure this is a real user upvoting...

  $commenter = new Commenter();
  if ( !($commenter->load($username)) ) return FALSE;
  $username = $commenter->username;

			// Fail if unable to reload the comment from file.

  if ( !$this->reload() ) return FALSE;

			// then add 1 to this comment's netvotes,
			// add 1 to this voter's vote on this comment,
			// and evaluate if the voter was undoing a downvote
			// (totalVotes -1) OR
			// if the voter was truly upvoting
			// (totalVotes +1).

  @ $this->netVotes += 1;
  @ $this->voters[$username] += 1;
  if ( $this->voters[$username] == 1 ) {
   @ $this->totalVotes += 1;
  } else if ( $this->voters[$username] === 0 ) {
   @ $this->totalVotes -= 1;
  }
			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Also add 1 karma to this commenter
			// (be sure to use the vote method, 
			//  or the karma bump won't be saved to file).

  $theCommenter = new Commenter();
  $theCommenter->load($this->commenter);
  $theCommenter->vote(1);

  return TRUE;

 }



/*
 * DOWNVOTING THIS COMMENT *
 ***************************/	

			// Returns TRUE on successful downvote.
			// Returns FALSE on failure.

 public function downvote($username) {

			// Make sure this is a real user upvoting...

  $commenter = new Commenter();
  if ( !($commenter->load($username)) ) return FALSE;
  $username = $commenter->username;

			// Fail if unable to reload the comment from file.

  if ( !$this->reload() ) return FALSE;

			// then subtract 1 from this comment's netvotes,
			// subtract 1 from this voter's vote on this comment,
			// and evaluate if the voter was undoing an upvote
			// (totalVotes -1) OR
			// if the voter was truly downvoting
			// (totalVotes +1).

  @ $this->netVotes -= 1;
  @ $this->voters[$username] -= 1;
  if ( $this->voters[$username] == -1 ) {
   @ $this->totalVotes += 1;
  } else if ( $this->voters[$username] === 0 ) {
   @ $this->totalVotes -= 1;
  }
			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Also subtract 1 karma from this commenter
			// (be sure to use the vote method, 
			//  or the karma bump won't be saved to file).

  $theCommenter = new Commenter();
  $theCommenter->load($this->commenter);
  $theCommenter->vote(-1);

  return TRUE;

 }



/*
 * SCORING THE COMMENT *
 ***********************/

			// Returns a score on success.
			// Returns FALSE on failure.

			// You may wish to avoid using this method
			// and simply score all comments at once
			// using the score_the_comments method 
			// on the CommentBoard class.

			// If the scoring interface has not been set
			// (by using the set_scoring method) 
			// the default scoring (ScoreByLower) is used.

 public function score($params) {

			// Fail if unable to reload the comment from file.

  if ( !$this->reload() ) return FALSE;

  $score = $this->scoring->score($this, $params);
  $this->score = $score;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return the score for further use.
  return $score;

 }



/*
 * RENDER THE COMMENT'S DESCENDANTS ON THE BOARD *
 *************************************************/

			// If the rendering interface has not been set
			// (by using the set_rendering method)
			// the default rendering (RenderByDefault) is used.

			// NOTE: a render should return JSON
			// for JavaScript / JQuery manipulation.

 public function render($params) {

  return $this->rendering->render($this, $params);

 }



/*		
 * MAKING A JUNCTION FROM THE COMMENT *		
 **************************************/

			// A junction determines the comment's display
			// in the CommentBoard tree.
			// Returns the junction.

 public function make_tree_junction($username = '') {

			// NOTE: There are no given children in a junction.
			// This allows a comment tree to build itself.

  $junction["id"] 		= $this->id;
  $junction["netVotes"] 	= $this->netVotes;
  $junction["totalVotes"] 	= $this->totalVotes;
  $junction["score"] 		= $this->score;
  $junction["comment"] 		= $this->comment;
  $junction["parent"] 		= $this->parent;
  $junction["commenter"] 	= $this->commenter;
  $junction["commenterBan"] 	= $this->commenterBan;
  $junction["descendants"] 	= $this->descendants;
  $junction["timestamp"]	= $this->timestamp;
  $junction["hasChildren"] 	= count($this->children);
  
			// If a username has been passed in, 
			// get the user's vote on this comment.
			// Otherwise set a neutral vote = 0.

  if ( is_string($username) 
   && preg_replace('/[^A-Za-z0-9_-]/', '', $username) === $username
   && isset($this->voters[$username]) 
   && abs($this->voters[$username]) == 1 ) {

   $junction["userVote"] = $this->voters[$username];
  } else {
   $junction["userVote"] = 0;
  }
			// The junction is an array of comment information.

  return $junction;

}



/*
 * CLOSING THE COMMENT INSTANCE *
 ********************************/

 public function __destruct() {

			// Always try to unlock the file, just to be sure.

  @ flock($this->fh, LOCK_UN);
  @ fclose($this->fh);
 }

}



/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

The comment class instantiates a comment on a commentboard.

	$comment = new Comment("aCommentBoard");

A new comment can be "stuck" somewhere on the board,
either to the board directly (to the board's ID) 
or to a previous comment (to that comment's ID).

	$comment->create("IDtoStickTo", "The comment!", "theCommenter");

However, new comments are GENERALLY NOT created directly.
And most of the comment methods should be called 
through the make_comment and make_reply methods of the CommentBoard class, 
or through the rendering interface.

 ***************************************************************

But there are THREE IMPORTANT DIRECT METHODS of the comment class, as follows:
once a comment is loaded,it can be edited, upvoted, or downvoted.

	$comment = new Comment("aCommentBoard");
	$comment->load($commentId);

	$comment->edit("I changed my comment!");
	$comment->upvote("upvoterName");
	$comment->downvote("downvoterName");

Also, note that comments are stripped of all but the most basic markup.
Basic markup is **bold**, *italic*, https://clickable.site,
and paragraph spacing between two or more line-breaks.

 ***************************************************************
                                                               */

?>