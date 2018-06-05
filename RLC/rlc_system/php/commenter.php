<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the Commenter class.
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
					
if ( PHP_MAJOR_VERSION < 5 
 || ( PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION < 5 ) ) {
  require(__DIR__."/PasswordFunctions.php");
}

	// If PHP is too old, password hashing functions are required.



/***********************			
 * THE COMMENTER CLASS *
 ***********************/

class Commenter {

 public $username;		// String.	
				// The display name of the commenter.

 private $passwordHash;		// String. 
				// A hash of the commenter's password.
				// NOTE: A private property.

 private $idHashes = array();	// Array. 	
				// Hashes of the commenter's ids 
				// (for signing in on various devices, etc.).
				// NOTE: A private property.

 public $comments;		// Array. 	
				// Key value pairs of commenter's comments 
				// and their boards (comment=>board).

 public $isMod = FALSE;		// Boolean.	
				// Is this commenter a moderator?

 public $isBanned = FALSE;	// Boolean.	
				// Is this commenter banned?

 public $karma;			// Integer.	
				// Sum of all +upvotes and -downvotes 
				// on commenter's comments.

 public $email;			// String. 	
				// The commenter's email, if you're into that.


 /*
  * NOTE: The following properties are not written to file. *
  ***********************************************************/

 protected $isLoggedIn = FALSE;	// Boolean.	
				// Is this commenter logged in?
 
 protected $isLoaded = FALSE;	// Boolean. 	
				// Is this commenter's data loaded?

 protected $filename;		// String.	
				// The filename for the commenter.
				// (RLC_SECRET_PATH.'commenters/'
				//  			.$username.'.txt')

 protected $fh;			// File handle.
				// A reference to the file for this comment.



/*
 * INSTANTIATING A COMMENTER *		
 *****************************/		

 public function __construct($username = "") {

			// Make sure commenter's file has a folder
			// that is protected from prying eyes.

  if ( !file_exists(RLC_SECRET_PATH) ) {
   mkdir(RLC_SECRET_PATH);
  }
  if ( !file_exists(RLC_SECRET_PATH."/commenters") ) {
   mkdir(RLC_SECRET_PATH."/commenters", 0700);
  }

			// Some dummy variables for mini_json object 						// (written over later if file is loaded).

  $this->username 	= '';
  $this->isLoggedIn	= FALSE;
  $this->isMod		= FALSE;
  $this->isBanned	= FALSE;
  $this->karma		= 0;

			// Try to load the commenter's data, if possible.
			// Since this is optional, 
			// suppress errors with TRUE.
			// (The load method is directly below.)

  if ( $this->load($username, TRUE) ) $this->isLoaded = TRUE;

 }



/*
 * LOADING AN EXISTING COMMENTER *
 *********************************/

			// Return TRUE if commenter's file loads.
			// Return FALSE if no file or malformed JSON.
			// Optionally set $suppress to TRUE,
			// to avoid echoing out any errors.
			// Leave the optional $lock parameter as it is.

 public function load($username, $suppress = FALSE, $lock = LOCK_SH) {

			// Fail if improper username.

  if ( !($this->proper_username($username, $suppress)) ) return FALSE;

			// Prepare the filename for this commenter.

  $this->filename = RLC_SECRET_PATH.
			"/commenters/".strtolower($username).".txt";

			// Fail if there is no file.

  try {
   if ( !($this->fh = fopen($this->filename, "r+")) ) {
    throw new \Exception('Username does not exist.');
   }
  } catch (\Exception $e) {
    echo 'Error: ',  $e->getMessage();
    return FALSE;
  }
			// Fail if unable to lock the file.

  if ( !flock($this->fh, $lock) ) {
    return FALSE;
  }
			// Make sure the user JSON can be unpacked to array.
 
  try {
   clearstatcache();
   rewind($this->fh);
   $userJson = fread($this->fh, filesize($this->filename));
   if ( !($userData = json_decode($userJson, true)) ) {
    throw new \Exception('The user JSON is malformed.');
   }
  } catch (\Exception $e) {
    echo 'Error: ',  $e->getMessage();
    @ flock($this->fh, LOCK_UN);
    return FALSE;
  }
			// Unpack JSON array to this commenter instance.

  $this->username	= $userData["username"];
  $this->passwordHash 	= $userData["passwordHash"];
  $this->idHashes 	= $userData["idHashes"];
  $this->comments 	= $userData["comments"];
  $this->isMod 		= $userData["isMod"];
  $this->isBanned 	= $userData["isBanned"];
  $this->karma		= $userData["karma"];
  $this->isLoaded	= TRUE;

			// Return TRUE for successful load.

  return TRUE;

 }



/************************************************************************
 * UTILITY METHODS: RELOAD AND REWRITE
 *
 * These functions are paired to protect commenter files by locking them.
 * They are called together for every method that changes the commenter.*	
 ************************************************************************/

 protected function reload($tries = 1) {

			// Returns TRUE for file locked and loaded.
			// Returns FALSE if unable to get the lock.

  if ( $this->load($this->username, TRUE, LOCK_EX | LOCK_NB) ) {
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

			// Organize the commenter's information...

  $userData["username"] 	= $this->username;
  $userData["passwordHash"] 	= $this->passwordHash;
  $userData["idHashes"] 	= $this->idHashes;
  $userData["comments"]		= $this->comments;
  $userData["isMod"]		= $this->isMod;
  $userData["isBanned"]		= $this->isBanned;
  $userData["karma"]		= $this->karma;

			// JSON encode...

  $userJson = json_encode($userData, JSON_PRETTY_PRINT);

			// Write the file and release the lock.

  rewind($this->fh);
  ftruncate($this->fh, 0);
  rewind($this->fh);
  fwrite($this->fh, $userJson);
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
 * SIGNING UP A COMMENTER *	
 **************************/

			// Returns ID on successful signup. 
			// Returns FALSE on failure.

 public function sign_up($username, $password1, $password2, 
   					$filter ="bad_words.txt") {

			// Fail if improper username 
			// (only numbers,letters, dashes, and underscores;
			//  method at bottom of class).

  if ( !($this->proper_username($username)) ) return FALSE;

			// Fail if passwords do not match
			// (method at bottom of class).
  
  if ( !($this->passwords_match($password1, $password2)) ) return FALSE;

			// Fail if username already exists.

  try {
   if ( file_exists(RLC_SECRET_PATH."/commenters/".
					strtolower($username).".txt") ) {
    throw new \Exception('Username already exists.');
   }
  } catch (\Exception $e) {
   echo 'Error: ',  $e->getMessage();
   return FALSE;
  }

			// Fail if username has a banned word 
			// (see/edit at RLC_PATH.'/bad_words.txt').

  if ( $bannedWordsList = file_get_contents(RLC_PATH."/".$filter) ) {
   $bannedWords = explode("\n", $bannedWordsList);
   foreach ( $bannedWords as $bannedWord ) {
    try { 
     if ( stripos($username, trim($bannedWord)) !== FALSE ) {
      throw new \Exception('Username contains banned word.');
     }
    } catch (\Exception $e) {
     echo 'Error: ',  $e->getMessage();
     return FALSE;
    }
   }
  }

			// Prepare the filename for this commenter.

  $this->filename = RLC_SECRET_PATH.
			"/commenters/".strtolower($username).".txt";

			// Hash the password.

  $passwordHash = password_hash($password1, PASSWORD_DEFAULT);

			// Create a random ID and hash it too.

  $id = substr(str_shuffle(str_repeat(
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
         mt_rand(1,20))),1,20);
  $idHash = password_hash($id, PASSWORD_DEFAULT);

			// Organize the commenter's data 
			// and set isLoggedIn and isLoaded to TRUE.

  $userData["username"]		= $this->username 	= $username;
  $userData["passwordHash"]	= $this->passwordHash 	= $passwordHash;
  $userData["idHashes"][0]	= $this->idHashes[0] 	= $idHash;
  $userData["comments"]		= $this->comments 	= array();
  $userData["isMod"]		= $this->isMod 		= FALSE;
  $userData["isBanned"]		= $this->isBanned 	= FALSE;
  $userData["karma"]		= $this->karma		= 0;
				  $this->isLoggedIn	= TRUE;
				  $this->isLoaded	= TRUE;

			// JSON encode...

  $userJson = json_encode($userData, JSON_PRETTY_PRINT);

			// Write the file.

  $this->fh = fopen($this->filename, "w");
  fwrite($this->fh, $userJson);
  fclose($this->fh);

			// Return the ID if it is to be used 
			// (e.g. in setting a cookie).

  return $id;

 }







/*
 * SIGNING IN A COMMENTER *	
 **************************/	

			// Returns ID on successful signin.
			// Returns FALSE on failure.
			// Optionally set $suppress to TRUE,
			// to avoid echoing out any errors.

 public function sign_in($username, $password, $suppress = FALSE) {

			// Fail if unable to get user data from file.

  if ( !$this->isLoaded && !$this->load($username, $suppress) ) return FALSE;

			// Fail if the user has been banned.

  if ( $this->isBanned == TRUE ) return FALSE;

			// Fail if the password does not match the hash.

  if ( !password_verify($password, $this->passwordHash) ) return FALSE;

			// Okay, the user is perhaps using a new device, 
			// which needs a new ID.

  $id = substr(str_shuffle(str_repeat(       '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
        mt_rand(1,20))),1,20);
  $idHash = password_hash($id, PASSWORD_DEFAULT);

			// Reload the file to lock it (fail if unable).

  if ( !$this->reload() ) return FALSE;

			// Add the new ID to this commenter instance
			// and set loggedIn to true.

  array_push($this->idHashes, $idHash);
  $this->isLoggedIn = TRUE;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return the ID if it is to be used
			// (e.g. in setting a cookie).

  return $id;

 }



/*
 * CONFIRMING A COMMENTER *	
 **************************/	

			// Returns TRUE on commenter who is already logged in.
			// Returns FALSE on failure. 
			// Optionally set $suppress to TRUE,
			// to avoid echoing out any errors.

 public function confirm($username, $id, $suppress = FALSE) {

			// Fail if unable to get user data from file.

  if ( !$this->isLoaded && !$this->load($username, $suppress) ) return FALSE;

			// Fail if the user has been banned.

  if ( $this->isBanned == TRUE ) return FALSE;

			// Look for the hashed id in the idHashes array.

  foreach ( $this->idHashes as $idHash ) {
   if ( password_verify($id, $idHash) ) {

			// If it is found, the user is legit. 
			// Set loggedIn to TRUE 
			// and return TRUE for a confirmed user.

    $this->isLoggedIn = TRUE;
    return TRUE;

   }
  }
  return FALSE;		// (If the ID was not found, return FALSE.)

 }



/*					
 * CHANGING A COMMENTER'S PASSWORD *	
 ***********************************/

			// Returns new ID on successful password change.
			// Returns FALSE on failure.

 public function change_password($username, $oldPassword, 
					$newPassword1,$newPassword2) {

			// Fail if commenter's file cannot be loaded.

  if ( !$this->isLoaded && !$this->load($username) ) return FALSE;

			// Fail if commenter is not logged in.

  if ( $this->isLoggedIn !== TRUE ) return FALSE;

			// Fail if new passwords do not match.
  
  if ( !($this->passwords_match($newPassword1, $newPassword2)) ) return FALSE;

			// Fail if the old password hash does not match.

  if ( !password_verify($oldPassword, $this->passwordHash) ) return FALSE;

			// Reload the file to lock it (fail if unable).

  if ( !$this->reload() ) return FALSE;

			// A change of password logs out every other device
			// i.e. every other ID is unset. 

  foreach ( $this->idHashes as $key=>$idHash ) {
   unset($this->idHashes[$key]);
  }
			// Make the new password hash...

  $passwordHash = password_hash($newPassword1, PASSWORD_DEFAULT);

			// Create a random new ID and hash it too...

  $id = substr(str_shuffle(str_repeat(       '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 
        mt_rand(1,20))),1,20);
  $idHash = password_hash($id, PASSWORD_DEFAULT);

			// Change the password and add the new ID.
			// Set isLoggedIn to TRUE.

  $this->paswordHash = $passwordHash;
  array_push($this->idHashes, $idHash);
  $this->isLoggedIn = TRUE;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;
			
			// Return the ID if it is to be used
			// (e.g. in setting a cookie).
  return $id;

 } 

/*
 * SIGNING OUT A COMMENTER *	
 ***************************/

			// Returns TRUE on successful signout.
			// Returns FALSE on failure.

 public function sign_out($username, $id) {

			// Fail if commenter's file cannot be loaded.

  if ( !$this->isLoaded && !$this->load($username) ) return FALSE;

			// Fail if commenter is not logged in.

  if ( $this->isLoggedIn !== TRUE ) return FALSE;

			// If the id is found in the idHashes array...

  foreach ( $this->idHashes as $key=>$idHash ) {
   if ( password_verify($id, $idHash) ) {

			// reload the file to lock it (fail if unable)...

  if ( !$this->reload() ) return FALSE;

			// unset the ID.

    unset($this->idHashes[$key]);

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// and return TRUE for succesful signout.

    return TRUE;

   }
  }    
  return FALSE;		// (If the ID was not found, return FALSE.)

 } 


/*
 * ADD A COMMENTER'S COMMENT *		
 *****************************/		
			
			// Avoid using this method.
			// Use instead make_reply and make_comment methods 
			// on the CommentBoard class.

			// Returns TRUE for comment successfully added.
			// Returns FALSE on failure.

			// (A comment is added to the comments array
			//  as a key=>value pair,
			//  which encodes the path to its file, i.e.
			//  RLC_SECRET_PATH.'boards/'.value.'/'.key.'.txt').

 public function add_comment($commentBoardId, $commentId) {

			// Fail if unable to reload the commenter from file.

  if ( !$this->reload() ) return FALSE;

			// Add comment to comments array.

  $this->comments[$commentId] = $commentBoardId;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for successful comment addition.
  return TRUE;

 }



/*
 * ADD COMMENTER'S EMAIL *	
 *************************/

			// Returns TRUE for email successfully added.
			// Returns FALSE on failure. 

 public function add_email($email) {

			// Fail if the email address is not well-formed.

  try {
   if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
    throw new \Exception('Invalid email.');
   }
  } catch (\Exception $e) {
   echo 'Error: ',  $e->getMessage();
   return FALSE;
  }
			// Fail if unable to reload the commenter from file.

  if ( !$this->reload() ) return FALSE;

			// Add email.

  $this->email = $email;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for successful email addition.
  return TRUE;

 }


/*
 * MAKE THIS COMMENTER A MOD OR NOT *		
 ************************************/		

			// Mods can blank out any comment and ban users.
			// Returns TRUE if commenter's mod status changed.
			// Returns FALSE on failure.
			// (Optional parameter $seriously 
			//  must be set to TRUE or FALSE
			//  to actually change the commenter's mod status.)

 public function make_mod($seriously = 'no') {

			// Fail if the commenter has been banned.

  if ( $this->isBanned == TRUE ) return FALSE;

  if ( $seriously === TRUE || $seriously === FALSE ) {

			// Fail if unable to reload the commenter from file.

   if ( !$this->reload() ) return FALSE;

			// Set the commenter's mod status
 
   $this->isMod = $seriously;

			// Fail if unable to rewrite the file.

   if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for successful change of mod status.
   return TRUE;
  }

  return FALSE;		// (If mod status not changed, return FALSE.)

 }


/*				
 * BAN THIS COMMENTER *		
 *********************/		
			// Returns TRUE if commenter successfully banned.
			// Returns FALSE on failure.
			// Optionally, all a commenter's previous comments 
			// can be blanked out when they are banned. 
			// Banned commenters cannot upvote, downvote, 			// edit, make comments, etc.

 public function ban($blankout = TRUE) {
 
			// Fail if unable to reload the commenter from file.

  if ( !$this->reload() ) return FALSE;

			// If the commenter is a mod, revoke that.

  if ( $this->isMod === TRUE ) $this->make_mod(FALSE);

			// Change the commenterBan property of all comments.
			// Optionally, blank out all the commenter's comments.

  foreach ( $this->comments as $commentid=>$boardid ) {

     $comment = new Comment($boardid);
     $comment->load($commentid);
     $comment->set_ban_to(TRUE);
   if ( $blankout === TRUE ) {

     $comment->edit("", 1);
   }
  }
			// Set isBanned to TRUE.

  $this->isBanned = TRUE;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for commenter succesfully banned.
  return TRUE;

 }


/*				
 * UNBAN THIS COMMENTER *		
 ************************/
		
			// Returns TRUE if commenter successfully un-banned.
			// Returns FALSE on failure.
			// Optionally, all a commenter's previous comments 
			// can be restored. 


 public function undo_ban($restore = FALSE) {
 
			// Fail if unable to reload the commenter from file.

  if ( !$this->reload() ) return FALSE;

			// Change the commenterBan property of all comments.
			// Optionally, restore all comments.

  foreach ( $this->comments as $commentid=>$boardid ) {
    $comment = new Comment($boardid);
    $comment->load($commentid);
    $comment->set_ban_to(FALSE);
   if ( $restore === TRUE ) {
      $comment->restore();
   }
  }
			// Set isBanned to FALSE.

  $this->isBanned = FALSE;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for commenter succesfully unbanned.
  return TRUE;

 }


/**
 * UPVOTE / DOWNVOTE THE COMMENTER *		
 ***********************************/		

			// Returns TRUE for bump to commenter's karma.
			// Returns FALSE on failure.
			// ($integer, should typically be +1, or -1.)

 public function vote($integer) {

			// Fail if unable to reload the commenter from file.

  if ( !$this->reload() ) return FALSE;

			// Add the integer to the commenter's karma.

  @ $this->karma += $integer;

			// Fail if unable to rewrite the file.

  if ( !$this->rewrite() ) return FALSE;

			// Return TRUE for successful karma bump.
  return TRUE;

 }


/**
 * MINI JSON *			
 *************/	

			// Returns a JSON object
			// of this commenter's public properties
			// (except email address).
			// Returns dummy variables
			// if this commenter does not exist.

 public function mini_json() {

			// Make a last attempt to load the commenter.

  if ( !$this->isLoaded ) $this->load($this->username, true);

			// Pack up the appropriate data...
			// this may be blank...

   $userData["username"] 	= $this->username;
   $userData["isLoggedIn"]	= $this->isLoggedIn;
   $userData["isMod"]		= $this->isMod;
   $userData["isBanned"]	= $this->isBanned;
   $userData["karma"]		= $this->karma;

			// and return JSON.

   return json_encode($userData);

  }



/*					
 * STATIC UTILITY METHODS *	
 **************************/

 public function proper_username($username, $suppress = FALSE) {

			// Return TRUE for proper username.
			// Return FALSE for improper username.
			// (Only letters, numbers, dashes, and underscores.)

			// Optionally set $suppress to TRUE,
			// to avoid echoing out any errors.
 
  try {
   if ( preg_replace('/[^A-Za-z0-9_-]/', '', $username) === $username 
    && strlen($username) > 4 ) {
     return TRUE;
   } else {
    throw new \Exception('Improper username.');
   }
  } catch (\Exception $e) {
   if (!$suppress) echo 'Error: ',  $e->getMessage();
   return FALSE;
  }

 }


 public function passwords_match($password1, $password2) {

			// Return TRUE if passwords match.
			// Return FALSE if passwords do not match.
  
  try {
   if ( $password1 === $password2 ) {
    return TRUE;
   } else {
    throw new \Exception('Passwords do not match.');
   }
  } catch (\Exception $e) {
   echo 'Error: ',  $e->getMessage();
   return FALSE;
  }

 }



/*
 * CLOSING THE COMMENTER INSTANCE *
 **********************************/

 public function __destruct() {

			// Always try to unlock the file, just to be sure.

  @ flock($this->fh, LOCK_UN);
  @ fclose($this->fh);
 }

}



/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

The commenter class instantiates a new commenter.
 
	$commenter = new Commenter();

When the commenter signs up correctly, an ID is returned. 
This can be used for setting a cookie. 

	$id = $commenter->sign_up($username, $password, $matchingPassword);
	setcookie("rlc_commenter_id", $id, time() + 3600*24*30);
	setcookie("rlc_commenter", $username, time() + 3600*24*30);

When the commenter returns, the id cookie can verify the commenter.

	$commenter = new Commenter();
	if ( isset($_COOKIE["rlc_commenter"])) 
         && isset($_COOKIE["rlc_commenter_id"])
	 && $commenter->confirm($_COOKIE["rlc_commenter"], 
                                       $_COOKIE["rlc_commenter_id"]) ) {		
		// A legitimate user!
	}

The commenter can also sign out.
This deletes the ID from his file so that the above cookie will fail.

	$commenter->sign_out($username, $id);

Or the commenter can change his password.
This deletes all the IDs from his file and returns a new one.

	$newId = $commenter->change_password($username, $oldPassword, 					$newPassword, $matchingNewPassword);

A comment can be added to the commenter's file.

	$commenter->add_comment($commentBoardId, $commentId);

An email can be added to the commenter's file.

	$commenter->add_email("commenter@website.com");

The commenter can be banned from further actions,
without deleting previous comments...

	$commenter->ban(FALSE);

or deleting previous comments.

	$commenter->ban(TRUE);

And a banned commenter can be reinstated
with the restoral of all his previous comments...

	$commenter->undo_ban(TRUE);

or without the restoral of his previous comments.

	$commenter->undo_ban(FALSE);

Or the commenter can be made into a mod...

	$commenter->make_mod(TRUE);

or unmade from being a mod.

	$commenter->make_mod(FALSE);

 ***************************************************************
                                                               */

?>


