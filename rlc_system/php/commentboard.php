<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the CommentBoard class.
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



/**************************	// Where all the comments will go on the page.
 * THE COMMENTBOARD CLASS *
 **************************/

class CommentBoard {

 public $id;		// String.	
			// Unique identifier for this commentboard.

 public $children;	// Array. 	
			// Key-value pairs of first comments 
			// with their scores (e.g. commentId1=>19).

 public $descendants;	// Array. 	
			// Key-value pairs of all comments
			// on the board with their scores (unordered).

 public $timestamp;	// Timestamp. 	
			// The UTC timestamp when the commentboard was made.

 /*
  * NOTE: The following properties are not written to file. *
  ***********************************************************/

 protected $filename;	// String.	
			// The filename for the commentboard.
			// RLC_SECRET_PATH.'/boards/'.$this->id.'/'
			//  .$this->id.'.txt'

 protected $fh;		// File handle.
			// A reference to the file for this commentboard.

 protected $rendering;	// Interface.	
			// The way the commentboard and descendants render.
			// Can be changed with this set_rendering method.

 public function set_rendering(Rendering $rendering) {
  $this->rendering = $rendering;
 }


/*
 * INSTANTIATING THE COMMENTBOARD *
 **********************************/

			// You should instantiate a commentboard with an ID
			// of only letters, numbers, dashes, and underscores.
			// If you forget, an ID will be made
			// from the hash of the URL.

 public function __construct($id = false) {

			// Make sure the commentboard has a folder
			// that is protected from prying eyes.

  if ( !file_exists(RLC_SECRET_PATH) ) {
   mkdir(RLC_SECRET_PATH);
  }
  if ( !file_exists(RLC_SECRET_PATH."/boards") ) {
   mkdir(RLC_SECRET_PATH."/boards", 0700);
  }

			// Set the ID for the commentboard.

  if ( !is_string($id) 
   || preg_replace('/[^A-Za-z0-9_-]/', '', $id) !== $id ) {

   $id = hash("md4", __DIR__."/".__FILE__);
  }
  $this->id = $id;

			// The commentboard becomes its own folder.

  if ( !file_exists(RLC_SECRET_PATH."/boards/".$id) ) {
   mkdir(RLC_SECRET_PATH."/boards/".$id, 0700);
  }
			// Prepare the filename for the commentboard.

  $this->filename = RLC_SECRET_PATH."/boards/".$id."/".$id.".txt";

			// Set the default rendering for the commentboard.

  $this->set_rendering(new RenderByDefault);

			// Load the commentboard (method directly below).
  $this->load();

 }



/*
 * LOADING A COMMENTBOARD *
 **************************/

			// Returns TRUE on successful file load.
			// Returns FALSE on failure.
			// Leave the optional $lock parameter as it is.

			// NOTE: Unlike the comment and commenter classes
			//  the commentboard'S load method is protected.

 protected function load($lock = LOCK_SH) {

			// If the commentboard file does not yet exist, 
			// the commentboard is created...

  if ( !file_exists($this->filename) ) {

   $info["id"]		= $this->id;
   $info["children"]	= $this->children 	= array();	
   $info["descendants"]	= $this->descendants 	= array();
   $info["timestamp"]	= $this->timestamp 	= time();

			// its information is JSON encoded...

   $infoJson = json_encode($info, JSON_PRETTY_PRINT);

			// and it is written to file.

   $this->fh = fopen($this->filename, "w");
   fwrite($this->fh, $infoJson);
   fclose($this->fh);

			// And TRUE is returned for a successful load.
   return TRUE;

  } else {
			// If, however, the commentboard file exists, 
			// the commentboard is loaded from its file.

   try {
    $this->fh = fopen($this->filename, "r+");

			// Fail if unable to lock the file.

    if ( !flock($this->fh, $lock) ) {
     return FALSE;
    }
			// Make sure the file JSON can be unpacked to array.
 
    clearstatcache();
    rewind($this->fh);
    $infoJson = fread($this->fh, filesize($this->filename));
    if ( !($info = json_decode($infoJson, true)) ) {
     throw new \Exception('CommentBoard JSON is malformed.');
    }
   } catch (\Exception $e) {
    echo 'Error: ',  $e->getMessage();
    @ flock($this->fh, LOCK_UN);

			// Without a comment board, there is no RLC system.
			// Commit tsuifuku.
    die();
   }
			// Organize the commentboard's information. 

   $this->id 		= $info["id"];
   $this->children	= $info["children"];
   $this->descendants	= $info["descendants"];
   $this->timestamp	= $info["timestamp"];

			// Return TRUE for successful file load.
   return TRUE;

  }

 }



/************************************************************************
 * UTILITY METHODS: RELOAD AND REWRITE
 *
 * These functions are paired to protect commentboard files by locking them.
 * They are called together for every method that changes the commentboard.	
 ************************************************************************/

 protected function reload($tries = 1) {

			// Returns TRUE for file locked and loaded.
			// Returns FALSE if unable to get the lock.

  if ( $this->load(LOCK_EX | LOCK_NB) ) {
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
  $info["children"] 	= $this->children;
  $info["descendants"]	= $this->descendants;
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
 * ADDING A FIRST COMMENT TO THE BOARD *
 ***************************************/

			// Returns new comment ID.
			// Returns FALSE on failure.

 public function make_comment($comment, $commenter) {

			// Fail if unable to reload commentboard from file.

  if ( !$this->reload() ) return FALSE;

			// The first comment must be a new comment 
			// with the board as both ancestor and parent.

  $firstComment = new Comment($this->id);
  $firstComment->create($this->id, $comment, $commenter);

			// Add the key-value pair of comment ID and netvotes
			// to the board's children and descendants.
    
  $this->children[$firstComment->id] = 0;
  $this->descendants[$firstComment->id] = 0;

			// The commenter gets a new comment added to his file.

  $commenterInstance = new Commenter();
  $commenterInstance->load($commenter);
  $commenterInstance->add_comment($this->id, $firstComment->id);

			// Fail if unable to rewrite commentboard file.

  if ( !$this->rewrite() ) return FALSE;

			// Return the comment ID in case it is to be used.

  return $firstComment->id;

 }


/*
 * REPLYING TO A COMMENT ON THE BOARD *
 **************************************/

			// Returns new comment ID of the reply.
			// Returns FALSE on failure.

 public function make_reply($parent, $comment, $commenter) {

			// Fail if unable to reload commentboard from file.

  if ( !$this->reload() ) return FALSE;

			// The reply is a new comment with board as ancestor.

  $reply = new Comment($this->id);
  $reply->create($parent, $comment, $commenter);

			// Instantiate the parent comment 
			// and log in the reply ID.

  $parentComment = new Comment($this->id);
  $parentComment->load($parent);
  $parentComment->log_child_score($reply->id);

			// All forefathers have a new descendant. 
			// A key=>value pair of reply ID and netvotes.

  $parentComment->log_descendant_score($reply->id);
  while ( $parentComment->parent != $parentComment->ancestor ) {
   $tempParent = $parentComment->parent;
   $parentComment = new Comment($this->id);
   $parentComment->load($tempParent);
   $parentComment->log_descendant_score($reply->id);
  }

			// The board has a new descendant.
			// A key=>value pair of reply ID and netvotes.
    
  $this->descendants[$reply->id] = 0;

			// The commenter gets a new comment added to his file.

  $commenterInstance = new Commenter();
  $commenterInstance->load($commenter);
  $commenterInstance->add_comment($this->id, $reply->id);

			// Fail if unable to rewrite commentboard file.

  if ( !$this->rewrite() ) return FALSE;

			// Return the reply ID in case it is to be used.
  return $reply->id;

 }



/*
 * SCORE ALL THE COMMENTS *
 ****************************/

			// Pass the scoring Interface that is to be used.
			// And pass the parameters
			// of the particualar scoring interface as an array.

			// NOTE: Although this method changes the board's file
			//  reload and rewrite are not called.
			//  The scoring interface must take care of that.

 public function score_the_comments($scoring = null, $params = array()) {
  if ( $scoring === null ) $scoring = new ScoreByLower();

			// Make sure the board actually has comments to score.

  if ( !empty($this->descendants) ) {

			// Fail if unable to reload commentboard from file.

   if ( !$this->reload() ) return FALSE;

			// Go through all the comments.

   foreach ($this->descendants as $descendantId=>$score) {

			// Set scoring interface for each comment and score.

    $comment = new Comment($this->id);
    $comment->load($descendantId);
    $comment->set_scoring($scoring);
    $newScore = $comment->score($params);

			// Reset the comment's score in the comment board.

    $this->descendants[$comment->id] = $newScore;
    if ( $this->id == $comment->parent ) {
     $this->children[$comment->id] = $newScore;
    }
   }
			// Fail if unable to rewrite commentboard file.

   if ( !$this->rewrite() ) return FALSE;

  }

 }


/*
 * RENDER THE COMMENT BOARD *
 ****************************/

			// A render should return JSON 
			// for JavaScript / JQuery manipulation.

			// Optionally, pass parameters
			// of a particualar rendering interface as array.

 public function render($params = array()) {

  return $this->rendering->render($this, $params);

 }



/*
 * CLOSING THE COMMENTBOARD INSTANCE *
 *************************************/

 public function __destruct() {

			// Always try to unlock the file, just to be sure.

  @ flock($this->fh, LOCK_UN);
  @ fclose($this->fh);
 }

}




/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

The CommentBoard class instantiates a new commentboard.
 
	$commentboard = new CommentBoard("myBoard");

When a commenter makes a first comment on the board,
it should be added to the board with the make_comment method.

	$commentboard->make_comment("The comment!", "theCommenter");

When a commenter makes a reply to a first comment or any other comment
it should be added to the board with the make_reply method.

	$commentboard->make_reply("myBoard", "The reply!", "theCommenter");

All the comments on the board can be scored
so that the best or most pertinent are more quickly displayed.
For scoring, use the score_the_comments method
(a comment's scoring interface may use parameters like time, etc;
 see the scoring interface file for more information).

	$commentboard->score_the_comments($interface, $params);

The commentboard can be rendered into a nested JSON object,
which is then in turn rendered by JavaScript / jQuery for display.
For rendering, use the render method
(a rendering method may use parameters like username, etc;
 see the rendering interface file for more information).

	$commentboardJSON = $commentboard->render($params);

 ***************************************************************
                                                               */

?>