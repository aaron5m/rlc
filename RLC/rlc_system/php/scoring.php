<?php Namespace rlc;

/**
 *
 * REDDIT-LIKE COMMENT SYSTEM IN PHP, JAVASCRIPT, AND JSON.
 *
 * This file contains the Scoring interface.
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
			


/*************************	
 * THE SCORING INTERFACE *	
 *************************/

			// A comment's score determines where it is shown 					// on the comment tree (up or down).
			// You may create different scoring interfaces
			// according to your needs.

interface Scoring {

 public function score($comment, $params = array());

}


/* 				
 * DEFAULT SCORING *	 	
 *******************/

			// The default scoring interface is something like
			// the lower bound of the Wilson score interval
			// on proportion of upvotes,
			// boosted by a time-decay factor. 

class ScoreByLower implements Scoring {

 public function score($comment, $params = array()) {

			// You may use your own parameters							// for your own scoring interface.
			// For default scoring parameters, 
			// rate is average seconds between comments.

  if ( isset($params["rate"]) ) {
   $rate = $params["rate"];
  } else {
   $rate = 30;
  }

			// Quick adjustment to avoid dividing by 0.

  if ( $comment->totalVotes == 0 ) {
   $ntot = 1;
  } else {
   $ntot = $comment->totalVotes;
  }

			// Something like the lower bound 
			// of the Wilson score interval?
			// (Be gentle, good mathematicians.)

  $npos = ($comment->totalVotes + $comment->netVotes) / 2;
  $p = $npos/$ntot;
  $root2 = sqrt( ($p*(1-$p))/$ntot + 3.8416/(4*$ntot*$ntot) );
  $coeff = 1.96 / (1 + 3.8416/$ntot);
  $base = ($p + 3.8416/(2*$ntot))/(1+3.8416/$ntot);
	
  $lw = $base - $coeff*$root2;

			// A time-decay boost, favoring newer posts
			// until they sink to where they should be
			// by the Wilson approach above.

  $timenow = time();
  $timedelta = $timenow - $comment->timestamp;
  if ($timedelta <= 1 ) $timedelta = 1.01;
  $timedecay = 1/log($timedelta,$rate);
  if ($timedecay >= 1) $timedecay = 1;
  $adjustment = pow(.96, (3600 + $timedelta)/3600);
  $adjTD = $adjustment*$timedecay;

			// The score is
			// the lower bound of the Wilson score
			// summed with the time-decay boost.

  $score = $lw + $adjTD;

			// If this comment is not a first comment...
 
  if ( $comment->parent != $comment->ancestor ) {

			// add the score to the comment's parent's 
			// children and descendants...

   $parentComment = new Comment($comment->ancestor);
   $parentComment->load($comment->parent);
   $parentComment->log_child_score($comment->id, $score);
   $parentComment->log_descendant_score($comment->id, $score);

			// and continue for each forefather,
			// adding the score to the forefather's descendants...
  
   while ( $parentComment->parent != $parentComment->ancestor ) {
    $tempAncestor = $parentComment->ancestor;
    $tempParent = $parentComment->parent;
    $parentComment = new Comment($tempAncestor);
    $parentComment->load($tempParent);
    $parentComment->log_descendant_score($comment->id, $score);
   }

  }

			// The score itself is returned
			// so that it can be saved to the comment's file
			// from the score method within the comment class.
  return $score;

			// (And that score is returned 
			//  so that scores can be saved to the commentboard).
 }
}



/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

A Scoring class instantiates a scoring interface, in this case the default.

	$scoring = new ScoreByLower();

Comments can be scored according to the interface,
(different interfaces may be used for different kinds of sites, etc).
In this case, continuing with the default.

	$comment = new Comment("aCommentBoard");
	$comment->load("commentID");
	$comment->set_scoring($scoring);

A scoring interface likely requires external parameters in an array.
For the default the only external parameter is "rate",
which is the average number of seconds between comments on the site,
so that new comments don't sink too quickly before anyone can see them.

	$comment->score(["rate"=>5]);

In this case, the comment is scored with a rate of 5 seconds,
because it is a popular website with new comments about every 5 seconds.

 ***************************************************************
                                                               */
?>