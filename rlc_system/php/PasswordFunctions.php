<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains password functions for older versions of PHP.
 * I've done my best, but maybe it's better to just upgrade your PHP,
 * if you can.
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

			// Double-check that password_ functions 
			// really are not available.

if ( !function_exists('password_hash') ) {

			// If there is no PASSWORD_DEFAULT constant 
			// one is made to prevent errors.

 if ( !defined('PASSWORD_DEFAULT') ) define('rlc\PASSWORD_DEFAULT', 'n/a'); 



 /*
  * PASSWORD HASH *
  *****************/ 

			// A function for salting and hashing a secret.
			// Returns a string.

 function password_hash($secret, $method) {

			// Create a random salt.

  $seed = str_split('abcdefghijklmnopqrstuvwxyz'
                 .'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
  shuffle($seed); 
  $salt = '$2a$07$';
  foreach (array_rand($seed, 30) as $k) $salt .= $seed[$k];

			// Use salt to hash secret.

  $secrethashed = crypt($secret, $salt);

			// Concatenate salt to secret hashed 
			// so both are available.

  $hashandsalt = $salt . $secrethashed;

			// Return the string of salt and hash.

  return $hashandsalt;

 }


 /*
  * PASSWORD VERIFY *
  *******************/ 

			// A function for verifying a secret matches a hash.
			// Returns TRUE on successful verification.
			// Returns FALSE on failure.

			// If - somehow - the password_verify function exists
			// it must be overridden 
			// to match the password_hash function.

 if ( function_exists('password_verify') ) {

			// This function is the same as the function below.
			// And the function below is easier to read.

  override_function('password_verify', '$newsecret, $oldhashandsalt',

   '
   $salt = substr($oldhashandsalt, 0, 37);
   $oldsecrethashed = substr($oldhashandsalt, 37);

   if (crypt($newsecret, $salt) === $oldsecrethashed) {
    return TRUE;
   } else {
    return FALSE;
   } 
   
   '
   );

 } else {

			// Password verify : same as above.

  function password_verify($newsecret, $oldhashandsalt) {

			// Take out the salt.

   $salt = substr($oldhashandsalt, 0, 37);

			// Isolate the old hashed secret.

   $oldsecrethashed = substr($oldhashandsalt, 37);

			// Test if the new hashed secret is equal.
			// Return TRUE for match, FALSE for failure.

   if (crypt($newsecret, $salt) === $oldsecrethashed) {
    return TRUE;
   } else {
    return FALSE;
   } 

  }

 }

}

/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

PHP has strong password functions beginning from version 5.5.
These functions mirror those for earlier versions of PHP.

password_hash makes a password into a unique string that can't be deciphered.

	$signupHash = password_hash($signupPassword, PASSWORD_DEFAULT);

The string can then be stored in file.
When a commenter returns, and enters the same password,
the string will be the same again.
That is tested using password_verify

	if ( password_verify($signinPassword, $signupHash) === TRUE ) {
	 // The user has signed in!
        }

 ***************************************************************

NOTE: This file is not necessary if you have PHP version > 5.5

 ***************************************************************
                                                               */

?>