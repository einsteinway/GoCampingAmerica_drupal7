<?php
// $Id: user-profile.tpl.php,v 1.2.2.2 2009/10/06 11:50:06 goba Exp $

/**
 * @file user-profile.tpl.php
 * Default theme implementation to present all user profile data.
 *
 * This template is used when viewing a registered member's profile page,
 * e.g., example.com/user/123. 123 being the users ID.
 *
 * By default, all user profile data is printed out with the $user_profile
 * variable. If there is a need to break it up you can use $profile instead.
 * It is keyed to the name of each category or other data attached to the
 * account. If it is a category it will contain all the profile items. By
 * default $profile['summary'] is provided which contains data on the user's
 * history. Other data can be included by modules. $profile['user_picture'] is
 * available by default showing the account picture.
 *
 * Also keep in mind that profile items and their categories can be defined by
 * site administrators. They are also available within $profile. For example,
 * if a site is configured with a category of "contact" with
 * fields for of addresses, phone numbers and other related info, then doing a
 * straight print of $profile['contact'] will output everything in the
 * category. This is useful for altering source order and adding custom
 * markup for the group.
 *
 * To check for all available data within $profile, use the code below.
 * @code
 *   print '<pre>'. check_plain(print_r($profile, 1)) .'</pre>';
 * @endcode
 *
 * Available variables:
 *   - $user_profile: All user profile data. Ready for print.
 *   - $profile: Keyed array of profile categories and their items or other data
 *     provided by modules.
 *
 * @see user-profile-category.tpl.php
 *   Where the html is handled for the group.
 * @see user-profile-item.tpl.php
 *   Where the html is handled for each item in the group.
 * @see template_preprocess_user_profile()
 */
?>
<div class="profile">
  <?php 
  global $user;
  echo "<div style='font-size:0.7em;color:#ccc;'>" . $user->name . "</div>";
  //echo "<pre>";
  //print_r($user);
  //echo "</pre>";
  if ($user->roles[5] == "park owner") {
    $toReplace = array("/users/", "/user/");
    //$profileID = str_replace($toReplace, "", $_SERVER['REQUEST_URI']); 
    //$uid = getParkUID($user->name);
	//$uid = getParkUID($profileID);
    $park = getParkDetails($user->uid);
    if ($park) { 
	  // Change tabs on profile pages
	?>
	<script type="text/javascript">
	var $gca = jQuery.noConflict();
    $gca(document).ready(function() {
	  $gca(".primary .view").hide();
      $gca(".primary .edit").html("<a href='/user/" + <?php echo $uid; ?> + "/edit'>Account Information</a></li><li class='view-park'><a href='/<?php echo $park["alias"]; ?>'>View Park</a></li><li class='edit-park'><a href='/node/<?php echo $park["nid"]; ?>/edit'>Edit Park</a>");
    });
	</script>
    <div id="park_profile_edit_link">
      <h3><?php echo $park["title"]; ?></h3>
	  <a href="/<?php echo $park["alias"]; ?>">View Park</a> | <a href="/node/<?php echo $park["nid"]; ?>/edit">Edit Park</a><br />
    </div>
  <? } else {
      echo "No park found for this user.";
	}
  } else {
    if (!$user->roles[7]) {
      echo "No park is owned by this user.";
    }
  }
  if ($user->roles[7]) {
    $camps = getStateParks($user->profile_stateassnID);
	echo "<h2>State Association Parks</h2>";
	echo "<table class='views-table cols-50'>";
	echo "<thead>";
	echo "<tr><th class='views-field views-field-title'>Park Name</th><th class='views-field views-field-title'>State</th><th class='views-field views-field-title'>Edit Link</th></tr>";
	echo "<tbody>";
	$y = 1;
	$campTitles = array();
	foreach ($camps as $camp) {
	  if (!in_array($camp["title"], $campTitles)) {
	    $rowClass = "odd";
	    if ($y == 0) {
	      $rowClass = "even";
	    }
	    echo "<tr class='" . $rowClass . "'><td class='views-field'><a href='/" . getParkAlias($camp["nid"]) . "'>" . $camp["title"] . "</a></td><td class='views-field'>" . $camp["province"] . "</td><td class='views-field'><a href='/node/" . $camp["nid"] . "/edit'>Edit</a></td></tr>";
		$campTitles[] = $camp["title"];
	    if ($y == 1) {
	      $y = 0;
	    } else {
	      $y = 1;
	    }
	  }
	}
	echo "</tbody>";
	echo "</table>";
  }
  ?>
  <pre>
  </pre>
  <?php //print $user_profile; ?>
</div>

<?php
function getStateParks($said) {
  //echo $said . "<br />";
  $query = db_query("SELECT DISTINCT n.nid, n.title, l.province FROM {node} n, {content_type_camp} c, {location} l, {location_instance} li WHERE n.nid = c.nid AND l.lid = li.lid AND li.nid = n.nid AND n.status = 1 AND c.field_camp_state_assnid_value = %d ORDER BY n.title ASC", $said);
  $x = 0;
  while ($row = db_fetch_array($query)) {
    if (isLatest($row["nid"], $said)) {
      $result[$x]["nid"] = $row["nid"];
	  $result[$x]["title"] = $row["title"];
	  $result[$x]["province"] = $row["province"];
	  $x++;
	}
  }
  return $result;
}

function isLatest($nid, $said) {
  $result = 0;
  $query = db_query("SELECT field_camp_state_assnid_value FROM {content_type_camp} WHERE nid = %d ORDER BY vid DESC LIMIT 1", $nid);
  while ($row = db_fetch_array($query)) {
    if ($row["field_camp_state_assnid_value"] == $said) {
      $result = 1;
    }
  }
  return $result;
}

function getParkDetails($uid) {
  $query = db_query("SELECT title, nid FROM {node} WHERE uid = %d AND type = 'camp' LIMIT 1", $uid);
  while ($row = db_fetch_array($query)) {
    $result["nid"] = $row["nid"];
	$result["title"] = $row["title"];
	$result["alias"] = getParkAlias($row["nid"]);
  }
  return $result;
}

function getParkAlias($nid) {
  $src = "node/" . $nid;
  $query = db_query("SELECT dst FROM {url_alias} WHERE src = '%s' LIMIT 1", $src);
  while ($row = db_fetch_array($query)) {
    $result = $row["dst"];
  }
  return $result;
}

function getParkUID($profileID) {
  $query = db_query("SELECT uid FROM {users} WHERE name = '%s' LIMIT 1", $profileID);
  while ($row = db_fetch_array($query)) {
    $uid = $row["uid"];
  }
  return $uid;
}
?>
