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


/***************************	
 * THE RENDERING INTERFACE *	
 ***************************/

			// A render returns a nested JSON object of comments,
			// which can then be displayed via JavaScript/jQuery.
			// You may create different rendering interfaces
			// according to your needs.

interface Rendering {

 public function render($commentOrCommentBoard, $params);

}
				

/*
 * DEFAULT RENDER *
 ******************/	

			// The default render is a tree
			// rooted in the commmentboard (or in a comment)
			// with branches to top-scored descendant comments.

class RenderByDefault implements Rendering {

 public function render($commentOrCommentBoard, $params = array()) {

			// You may use your own parameters							// for your own rendering interface.
			// For default rendering parameters, 
			// username is the person viewing the commentboard,
			// and limit is how many top-scored descendants
			// to branch to.
		
  if ( isset($params["username"]) ) {
   $username = $params["username"];
  } else {
   $username = '';
  }

  if ( isset($params["limit"]) ) {
   $limit = $params["limit"];
  } else {
   $limit = 20;
  }
			// Figure out the ancestor 
			// for loading this tree's comments
			// by whether it's being called 
			// on a comment or commentboard
			// (commentboard's don't have ancestors).

  if ( isset($commentOrCommentBoard->ancestor) ) {
   $ancestor = $commentOrCommentBoard->ancestor;
  } else {
   $ancestor = $commentOrCommentBoard->id;
  }

			// The comment tree begins 
			// from the passed comment or commentboard.

  $trunk = $commentOrCommentBoard;

			// Clone the descendants for temporary manipulation.

  $descendants = $trunk->descendants;

			// Begin making the tree.

  $tree["id"] = $trunk->id;
  $tree["children"] = array();
  if ( isset($trunk->parent) ) $tree["parent"] = $trunk->parent;

			// (Junctions will be nodes in the nested JSON,
			//  i.e. comments and their parent/child comments.)

  $junctions = array();
  $junctionIds = array($trunk->id);

			// Sort all the descendants of the board or comment
			// and get the top comments by the limit.

  arsort($descendants);
  $kount = count($descendants);
  while ( $kount > $limit ) {
   array_pop($descendants);
   $kount -= 1;
  }
			// Branches must be formed out to each descendant. 
			// Each descendant becomes a junction 
			// on the branch of the comment tree.

  foreach ( $descendants as $descendantId=>$score ) {

			// If the descendant is already in the junctions
			// (i.e. it was loaded as the parent of a previous)
			// just continue to next.

   if ( in_array($descendantId, $junctionIds) ) continue;

   $descendant = new Comment($ancestor);
   $descendant->load($descendantId);
   $junction = $descendant->make_tree_junction($username);

			// When a junction is made from a comment
			// add it to the array of junctions,
			// and add its ID to the array of junction IDs.

   array_push($junctions, $junction);
   array_push($junctionIds, $junction["id"]);

			// Make any required parent junctions.
			// (NOTE: This means, for a comment tree with limit 10,
			//  it is possible to render more than 10 comments.)

   while ( !in_array($junction["parent"], $junctionIds) ) {
    $parent = new Comment($ancestor);
    $parent->load($junction["parent"]);
    $junction = $parent->make_tree_junction($username);
    array_push($junctions, $junction);
    array_push($junctionIds, $junction["id"]);
   }

  }
			// Count how many main branches there are,
			// i.e. how many comments that connect directly
			//  to the trunk (comment board or comment).
  $kount = 0;
  foreach ( $junctions as $aJunction) {
   if ( $aJunction["parent"] == $tree["id"] ) $kount += 1;
  }
			// Now start connecting junctions
			// until there are only main branches left.
			// (so, every other comment finds its place
			//  somewhere on a main branch.
			//  The connect_a_junction method is below.)

  while ( count($junctions) > $kount ) {
   $junctions = $this->connect_a_junction($junctions, $tree);
  }
			// Now connect the main junctions to the trunk.

  foreach ( $junctions as $junction ) {
   if ( $junction["parent"] == $trunk->id ) {
    $tree["children"][$junction["id"]] = $junction;
   }
  }
			// Go back down the tree
			// and re-arrange each comment's children
			// from highest to lowest score.
			// (The sort_junctions method is below.)

  $tree = $this->sort_junctions($tree);

			// Return the tree as a nested JSON object.

  return json_encode($tree);
 }



/*			
 * CONNECTING A JUNCTION *
 *************************/

			// Two methods.
			// Together they connect junctions into branches.

			// *****
			// First, and very quick: 
			// find out if junction (by ID) has children present.
			// If so, return the first found child's ID.

 protected function has_child($id, $junctions) {
  foreach ( $junctions as $aJunction ) {
   if ( $aJunction["parent"] == $id ) return $aJunction["id"];
  }
  return FALSE;
 }
			// ******
			// Second, using that method, and more involved:

 protected function connect_a_junction($junctions, $tree) {

			// Avoiding any main branches...
  shuffle($junctions);
  $k = 0;
  while ( $junctions[$k]["parent"] == $tree["id"] ) {
   $k+=1;
  }
  $id = $junctions[$k]["id"];
			
			// start from a junction and find out 
			// if it has any children present.
			// If so, do the same thing with that child
			// until you have a junction without children.

  while ( is_string($this->has_child($id, $junctions)) ) {
    $id = $this->has_child($id, $junctions);
  }
			// This childless junction can be safely removed.
			// Set it apart from the junctions array.

  foreach ( $junctions as $key=>$aJunction ) {
   if ( $aJunction["id"] == $id ) { 
    $tempJunction = $aJunction;
    unset($junctions[$key]);
    break;
   }
  }
			// Now connect the childless junction
			// to its own parent.
 
  foreach ( $junctions as $key=>$aJunction ) {
   if ( $aJunction["id"] == $tempJunction["parent"] ) {
    $junctions[$key]["children"][$tempJunction["id"]] = $tempJunction;
    unset($tempJunction);
    break;
   }
  }			// Return the new array of junctions
			// (one fewer element than when function was called).
  return $junctions;
 }



/*			
 * SORTING JUNCTIONS *
 *********************/

			// Sort a junction's children in order from 
			// highest score to lowest score, and...

 public function sort_junctions($junction) {

  if ( isset($junction["children"]) && !empty($junction["children"]) ) {
   usort( $junction["children"], function ($child1, $child2) {
    if ($child2["score"] <= $child1["score"]) {
     return -1; 
    } else {
     return 1;
    }
   });
			// keep doing so out across all descendants.

   foreach ( $junction["children"] as $aKey=>$aJunction ) {
    $junction["children"][$aKey] = $this->sort_junctions($aJunction);
   }
   return $junction;
  }
  return $junction;
 }

}



/***************************************************************
 * SUMMARY *****************************************************
 ***************************************************************

A Rendering class instantiates a rendering interface, in this case the default.

	$rendering = new RenderByDefault();

The comment tree is rendered according to the interface,
(different interfaces may be used for different kinds of sites, etc).
In this case, continuing with the default.

	$commentBoard = new CommentBoard("aCommentBoard");
	$commentBoard->set_rendering($rendering);

A rendering interface likely requires external parameters in an array.
For the default the only external parameters are optional:
"username", for the person viewing the commentboard
and "limit" for how many comments to display if possible.

	$commentBoard->render(["username"=>"theViewer", "limit"=>20]);

In this case, the commentboard is rendered for "theViewer",
so "theViewer" will see his own upvotes and downvotes on respective comments.
And "limit" has been set to 20, to show at least 20 comments
(if there are that many).

 ***************************************************************
                                                               */
?>