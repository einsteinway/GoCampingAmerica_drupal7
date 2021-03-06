<?php

/* ********************************************** */
/* This script receives search criteria from the  */
/* Find a Park page, runs the database query and  */
/* displays the results. It also provides the     */
/* list of parks for the State Overview pages.    */
/* ********************************************** */

// Bootstrap
chdir($_SERVER['DOCUMENT_ROOT']);
global $base_url;
$base_url = 'http://'.$_SERVER['HTTP_HOST'];
require_once './includes/bootstrap.inc';
require_once './includes/common.inc';
require_once './includes/module.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
drupal_load('module', 'node');
module_invoke('node', 'boot');

// Stylesheet ?>

<style type="text/css">
  body {
    margin:0;
	padding:0;
  }
</style>

<?
// Load javascript
?>

<script type="text/javascript"
 src="//ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
 <script type="text/javascript">
   console.log("search_results called");
 </script>
 <!-- <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script> -->
<script type="text/javascript" src="/scripts/gca_jquery.js"></script>
<?php if (isset($_REQUEST["smap"])) { 
  if ($_REQUEST["smap"] != 0) { ?>
  <script src="http://maps.google.com/maps?file=api&amp;v=2.115&amp;key=AIzaSyD7q02D5L5ZhPNQK_EWkjwOZQdx6omgIVQ&amp;hl=en" type="text/javascript"></script>
  <script type="text/javascript" src="/scripts/jquery.gmap-1.1.0-min.js"></script>
<?php }
  }
  


$testing = 0; // 2 = show full technical details; 0 = off
$searchType = $_REQUEST["type"]; // 0 = general search; 1 = state search (for state overview page)
$smallMap = 0;
$smallMap = $_REQUEST["smap"]; // 0 = display results; 1 = display maps

/* **************************************** */
/*    GET VARIABLES FROM THE URL STRING     */
/* **************************************** */

if (isset($_REQUEST['t'])) {
  // Variable "t" in the URL string contains all of the taxonomies selected via the search filters
  $originalTerms = str_replace('"', '', $_REQUEST['t']);
  $terms = array_filter(explode("|", $originalTerms));
}

if (isset($_REQUEST['l'])) {
  if($_REQUEST["state"] != "undefined") {
  
    // If a location-based search is run from a state overview page, append the state to the location string
    $location = $_REQUEST['l'] . " " . $_REQUEST["state"];
	
  } else {
  
    // Otherwise the location stands on its own
    $location = $_REQUEST['l'];
	
  }
  $loc = getGeocodes($location);
  $originalLocation = $_REQUEST['l']; // Necessary to show the location in the search criteria and for recording searches to the database
  if ($smallMap == 0) {
    recordSearch($originalLocation, $originalTerms);
  }
  $olCode = "l"; // l = location-based search; p = park name-based search
}

if (isset($_REQUEST['c'])) {
  $loc = explode("|", str_replace('"', "", $_REQUEST['c']));
  $originalLocation = $_REQUEST['c'];
  $olCode = "c";
}

if (!$olCode) {
  // Default to a location-based search
  $olCode = "l";
}

if (isset($_REQUEST['r'])) {
  $radius = preg_replace("/[^0-9\s]/", "", $_REQUEST['r']);
  // Adjust Google Maps zoom level depending on the specified radius
  $zoom = 7;
  if ($radius == 50) {
    $zoom = 6;
  } elseif ($radius == 100 || $radius == 150) {
    $zoom = 5;
  } elseif ($radius == 200) {
    $zoom = 4;
  } elseif ($radius == 3000) {
    $zoom = 3;
  }
} else {
  $radius = 25;
  $zoom = 7;
}


if(isset($_REQUEST['o'])) {
  // In this case, "o" = offset (for pagination)
  $offset = preg_replace("/[^0-9\s]/", "", $_REQUEST['o']);
} else {
  $offset = 0;
}

// Check for parks opted-in for GCA promotions
if (isset($_REQUEST['optin'])) {
  $optin = $_REQUEST['optin'];
} else {
  $optin = "no";
}

if ($_REQUEST["state"]) {
  // If "state" is set, get parks for specific state overview page
  $stateNids = getStateParks($_REQUEST['state']);
}

$state_list = array('AL'=>"Alabama", 'AK'=>"Alaska", 'AZ'=>"Arizona", 'AR'=>"Arkansas", 'CA'=>"California", 'CO'=>"Colorado", 'CT'=>"Connecticut", 'DE'=>"Delaware", 'DC'=>"District Of Columbia", 'FL'=>"Florida", 'GA'=>"Georgia", 'HI'=>"Hawaii", 'ID'=>"Idaho", 'IL'=>"Illinois", 'IN'=>"Indiana", 'IA'=>"Iowa", 'KS'=>"Kansas", 'KY'=>"Kentucky", 'LA'=>"Louisiana", 'ME'=>"Maine", 'MD'=>"Maryland", 'MA'=>"Massachusetts", 'MI'=>"Michigan", 'MN'=>"Minnesota", 'MS'=>"Mississippi", 'MO'=>"Missouri", 'MT'=>"Montana", 'NE'=>"Nebraska", 'NV'=>"Nevada", 'NH'=>"New Hampshire", 'NJ'=>"New Jersey", 'NM'=>"New Mexico", 'NY'=>"New York", 'NC'=>"North Carolina", 'ND'=>"North Dakota", 'OH'=>"Ohio", 'OK'=>"Oklahoma", 'OR'=>"Oregon", 'PA'=>"Pennsylvania", 'RI'=>"Rhode Island", 'SC'=>"South Carolina", 'SD'=>"South Dakota", 'TN'=>"Tennessee", 'TX'=>"Texas", 'UT'=>"Utah", 'VT'=>"Vermont", 'VA'=>"Virginia", 'WA'=>"Washington", 'WV'=>"West Virginia", 'WI'=>"Wisconsin", 'WY'=>"Wyoming", 'AB' => "Alberta", 'BC' => "British Columbia", 'MB' => "Manitoba", 'NB' => "New Brunswick", 'NL' => "Newfoundland and Labrador", 'NT' => "Northwest Territories", 'NS' => "Nova Scotia", 'NU' => "Nunavut", 'ON' => "Ontario", 'PE' => "Prince Edward Island", 'QC' => "Quebec", 'SK' => "Saskatchewan", 'YT' => "Yukon");

$stateFlag = 0;

foreach ($state_list as $key => $value) {
  if (strtolower(str_replace('"', '', $originalLocation)) == strtolower($value)) {
    $stateNids = getStateParks($key);
    $stateFlag = 1;
    $radius = 200;
  }
}

if ($smallMap == 0) {
  echo "<div id='search-map' style='width:690px;height:350px;'></div>";
  echo "<div id='search-results'>";
  //echo "<div style='font-size:0.7em;'>" . $_SERVER['QUERY_STRING'] . "</div>";
  if ($searchType == 1 && $smallMap == 0) {
    echo "<div id='search-map-state' style='float:left;margin-right:20px;'>Map loading ...</div>";
  }
  displayCriteria($terms, $loc, $radius, $originalTerms, $originalLocation, $olCode, $stateFlag);
  if ($searchType == 1) {
    echo "<br clear='all' /><br />";
  }
  if ($testing > 0) {
    echo "*** TESTING MODE IS ON ***<br />";
    echo "Testing Level: " . $testing . "<br />";
    if (count($stateNids)) {
      echo "Parks in state: " . count($stateNids) . "<br />";
    }
    if ($stateFlag == 1) {
      echo "State name entered. Parks in state: " . count($stateNids) . "<br />";
    }
    showNodeCount();
  }
}

if ($testing == 2 && $smallMap == 0) {
  echo "<pre>";
  echo $_SERVER['QUERY_STRING'] . "<br />";
  echo "location: <br />";
  echo $location . "<br />";
  echo "terms:<br />";
  print_r($terms);
  echo "loc:<br />";
  print_r($loc);
  echo "original location:<br />";
  echo $originalLocation . "<br />";
  echo "</pre>";
}

/* ******************** */
/*    RUN THE SEARCH    */
/* ******************** */

// The actual search query and display for all search types are crammed into this next line, ergo the ridiculous number of variables
getResults($terms, $loc, $radius, $testing, $offset, $originalTerms, $originalLocation, $optin, $olCode, $zoom, $stateNids, $stateFlag, $smallMap);

echo "</div>";
if ($_REQUEST["hide"] != "small") {
  echo "<a name='search-map-small'></a><div id='search-map-small' style='width:314px;height:187px;'></div>";
}
echo "<a name='large-map'></a><div id='search-map' style='width:690px;height:350px;'></div>";


/* ******************** */
/*      FUNCTIONS       */
/* ******************** */

function displayCriteria($terms, $loc, $radius, $originalTerms, $originalLocation, $olCode, $stateFlag) {
  echo "<div id='searchCriteria'>";
  echo "<div id='searchCriteriaLabel'><h2>Your Search Criteria:</h2></div>";
  if ($terms) {
    echo "<div id='searchOptions'>";
    echo "<table><tr>";
    echo "<td valign='top' style='position:relative;vertical-align:top;'><b>Options:</b> </td>";
    echo "<td>";
    foreach ($terms as $term) {
      echo "<li>" . getTermName($term) . " (<a class='removeOption' rel='" . $term . "'>remove</a>)</li>";
    }
    echo "</td>";
    echo "</tr></table></div>";
  }
  if ($radius != 3000 && $stateFlag != 1 ) {
    echo "Parks within " . $radius . " miles of " . $originalLocation . "<br /><br />";
  } elseif ($stateFlag == 1) {
    echo "Parks within " . $originalLocation . "<br/><br />";
  }
  echo "</div>";
}

function getStateParks($stateAbbrev) {
  $query = db_query("SELECT DISTINCT n.nid FROM {node} n, {location} l, {location_instance} li, {content_type_camp} c WHERE n.nid = li.nid AND n.nid = c.nid AND li.lid = l.lid AND n.type = 'camp' AND li.genid LIKE '%%field_location%%' AND l.province = '%s' AND c.field_camp_status_value = 'Active' ORDER BY n.title ASC", $stateAbbrev);
  while ($row = db_fetch_object($query)) {
    $extended = checkExtendedStay($row->nid);
	if ($extended != "on") {
      $result[] = $row->nid;
    }
  }
  return $result;
}

function showNodeCount() {
  $query = db_query("SELECT nid FROM {node} WHERE type = 'camp'");
  $x = 0;
  while ($row = db_fetch_object($query)) {
    $x++;
  }
  echo "Camp count: " . $x . "<br />";
  $query = db_query("SELECT n.nid FROM {node} n, {content_type_camp} c WHERE n.nid = c.nid AND n.type = 'camp' AND c.field_camp_status_value = 'Active'");
  $x = 0;
  while ($row = db_fetch_object($query)) {
    $x++;
  }
  echo "Active camp count: " . $x . "<br />";
  $query = db_query("SELECT n.nid FROM {node} n, {content_type_camp} c WHERE n.nid = c.nid AND n.type = 'camp' AND c.field_camp_status_value = 'Inactive'");
  $x = 0;
  while ($row = db_fetch_object($query)) {
    $x++;
  }
  echo "Inactive camp count: " . $x . "<br />";
}

function getTermName($tid) {
  $query = db_query("SELECT name FROM {term_data} WHERE tid = %d", $tid);
  while ($row = db_fetch_object($query)) {
    return $row->name;
  }
}

function getResults($terms, $loc, $radius, $testing, $offset, $originalTerms, $originalLocation, $optin, $olCode, $zoom, $stateNids, $stateFlag, $smallMap) {
  if (isset($loc) && isset($radius)) {

    // A latitude, longitude and radius have been specified, so get an array of matching parks
    $fullResult = getParksGeo($loc, $radius, $optin, $testing);
    if ($testing == 2) {
      echo "getResults: fullResult array: " . count($fullResult) . "<br />";
	  echo "<pre>";
	  //print_r($fullResult);
	  echo "</pre>";
    }
    $geoResult = $fullResult[nidlist];

  }

  if (isset($terms) && count($terms) > 0) {
  
    // Terms have been specified, so get an array of matching parks
    $termsResult = getParksTerms($terms, $testing);
    if ($testing == 2) {
      echo "getResults: termsResult array: " . count($termsResult) . "<br />";
    }

  }
  
  if ($stateFlag == 1) {
    $geoResult = $stateNids;
  }

  if (!isset($terms) && !isset($loc) && !isset($radius)) {
    echo "You did not specify any search criteria. Please try again.<br />";
  } else {
    displayParks($geoResult, $termsResult, $fullResult, $offset, $originalTerms, $originalLocation, $olCode, $radius, $testing, $loc, $zoom, $stateNids, $stateFlag, $smallMap);
  }
}

function getParksGeo($loc, $radius, $optin, $testing) {
  if ($radius == 3000) {
    $orderBy = " ORDER BY n.title ASC";
  } else {
    $orderBy = " ORDER BY distance";
  }
  $queryString = "SELECT 
						c.field_park_tier_value, 
						l.lid, 
						l.latitude, 
						l.longitude, 
						l.street, 
						l.postal_code, 
						( 3959 * acos( cos( radians(" . $loc[1] . ") ) * cos( radians( l.latitude ) ) * cos( radians( l.longitude ) - radians(" . $loc[0] . ") ) + sin( radians(" . $loc[1] . ") ) * sin( radians( l.latitude ) ) ) ) AS distance, 
						li.nid, 
						n.title, 
						l.city, 
						l.province,
						li.genid
					FROM {location} l, 
						{location_instance} li, 
						{node} n, 
						{content_type_camp} c 
					WHERE l.lid = li.lid 
						AND li.nid = n.nid 
						AND n.nid = c.nid 
						AND n.type = 'camp' 
						AND n.status = 1 
						AND (c.field_camp_status_value = 'Active' || c.field_camp_status_value = 'active') HAVING distance < " . $radius . $orderBy;

    $query = db_query($queryString);
    $result = array();
    $result[nidlist] = array();
    while ($row = db_fetch_object($query)) {
      if (!in_array($row->nid, $result[nidlist]) && ($row->title != " ")) {
        //if (($optin == "yes" && getOptin($row->nid) == "yes") || $optin == "no") {
		$extended = checkExtendedStay($row->nid);
		if ($extended != "on") {
          $result[$row->nid][distance] = $row->distance;
          $result[$row->nid][street] = $row->street;
          $result[$row->nid][city] = $row->city;
          $result[$row->nid][province] = $row->province;
          $result[$row->nid][postal_code] = $row->postal_code;
          $result[$row->nid][latitude] = $row->latitude;
          $result[$row->nid][longitude] = $row->longitude;
		  $result[$row->nid]["tier"] = $row->field_park_tier_value;
		  $result[$row->nid]["vid"] = getNewestVid($row->nid);
		  $result[$row->nid]["extended_stay"] = $extended;
          $result[nidlist][] = $row->nid;
        }
		//}
      }
    }
    if ($testing == 2) {
      echo "getParksGeo: final return: " . count($result) . "<br />";
      echo "<pre>";
	  //print_r($result);
	  echo "</pre>";
	}
    return $result;
}

function checkExtendedStay($nid) {
  $query = db_query("SELECT field_park_extended_stay_value FROM {content_type_camp} WHERE nid = %d ORDER BY vid DESC LIMIT 1", $nid);
  while ($row = db_fetch_array($query)) {
    $result = $row["field_park_extended_stay_value"];
  }
  return $result;
}

function getOptin($nid) {
  $query = db_query("SELECT field_park_official_optin_value FROM {content_type_camp} WHERE nid = %s", $nid);
  while ($row = db_fetch_object($query)) {
    if ($row->field_park_official_optin_value == "yes") {
      return "yes";
    }
  }
  return "no";
}

function getTermVid($tid) {
  $query = db_query("SELECT vid FROM {term_data} WHERE tid = %d", $tid);
  while ($row = db_fetch_object($query)) {
    $vid = $row->vid;
  }
  return $vid;
}

function getParksTerms($terms, $testing) {
  if ($testing == 2) {
    echo "getParksTerms receives: <br />";
    echo "<pre>";
    print_r($terms);
    echo "</pre>";
  }
  $result = array();
  $x = 0;
  foreach ($terms as $term) {
    $vid = getTermVid($term);
    $query = db_query("SELECT nid FROM {term_node} WHERE tid = %d", $term);
	$result[$term] = array();
	$tempResult[$term] = array();
    while ($row = db_fetch_object($query)) {
      if ($vid == 1) { // 1 = affiliate term
        $resultsAffiliate[] = $row->nid;
      } else {
        $tempResult[$term][] = $row->nid;
      }
    }
	// If tags are found for a term, make sure the term is associated with the most recent version of the node. Otherwise, terms that have been removed from a node will still show up.
	foreach ($tempResult[$term] as $tempNid) {
	  $newestVid = getNewestVid($tempNid);
	  $query = db_query("SELECT nid FROM {term_node} WHERE nid = %d AND vid = %d AND tid = %d", $tempNid, $newestVid, $term);
	  while ($row = db_fetch_array($query)) {
	    $result[$term][] = $row["nid"];
	  }
	}
  }
  
  if ($testing == 2) {
    foreach ($result as $key=>$value) {
	  echo "Term " . $key . " results: " . count($value) . "<br />";
	}
  }
  
  $result = array_merge(array(), $result);
  
  if ($result[0]) {
    $intersected = array();
    for ($i = 0; $i < count($result); $i++) {
      if ($i == 0) {
        $intersected = $result[0];
        if ($testing == 2) {
          echo "getParksTerms: Set 0: " . count($intersected) . "<br />";
        }
      } else {
        if ($testing == 2) {
          echo "getParksTerms: Set " . $i . ": " . count($result[$i]) . "<br />";
        }
        $intersected = array_intersect($intersected, $result[$i]);
        if ($testing == 2) {
          echo "getParksTerms: After intersect: " . count($intersected) . "<br />";
        }
      }
    }
    if ($resultsAffiliate) {
      $result = array_intersect($intersected, $resultsAffiliate);
      if ($testing == 2) {
        echo "getParksTerms: intersect 1: " . count($result) . "<br />";
      }
    } else {
      $result = $intersected;
      if ($testing == 2) {
        echo "getParksTerms: intersect 2: " . count($result) . "<br />";
      }
    }
  } else {
    $result = $resultsAffiliate;
  }
  if ($testing == 2) {
    echo "getParksTerms: final return: " . count($result) . "<br />";
  }
  return $result;
}

function displayParks($geo, $termsResult, $distance, $offset, $originalTerms, $originalLocation, $olCode, $radius, $testing, $loc, $zoom, $stateNids, $stateFlag, $smallMap) {

    if ($testing == 2) {
      echo "displayParks triggered<br />";
      echo "displayParks: originalTerms: " . $originalTerms . "<br />";
      echo "displayParks: geo array received: " . count($geo) . "<br />";
      echo "displayParks: termsResult array received: " . count($termsResult) . "<br />";
    }

    if ($geo > 0 && $originalTerms != "") {
      if ($testing == 2) {
        echo "displayParks: terms pre-intersect: " . count($termsResult) . "<br />";
      }
      $result = array_intersect($geo, $termsResult);
      if ($testing == 2) {
        echo "displayParks: result post-intersect: " . count($result) . "<br />";
      }
    } elseif (count($geo) > 0) {
      $result = $geo;
    } elseif (count($termsResult) > 0) {
      $result = $termsResult;
    } else {
      echo "Your search criteria yielded no results. Please try again."; ?>
      <script type="text/javascript">
        $gca(function() {
          $gca("#asw-right").html('<img src="/sites/default/files/google_map.jpg">');
          $gca("#gca-search-throbber", window.parent.document).addClass('hide');
        });
      </script>
      <?php
      return;
    }
    array_values($result);
    
    if ($testing == 2) {
      echo "displayParks: after array_values: " . count($result) . "<br />";
    }
    
    if (count($stateNids) > 0) {
      $result = array_intersect($result, $stateNids);
      if ($testing == 2) {
        echo "displayParks: after stateNids intersect: " . count($result) . "<br />";
      }
    }
    
    if (count($result) == 0) { ?>
      <script type="text/javascript">
        $gca(function() {
          $gca("#asw-right").html('<img src="/sites/default/files/google_map.jpg">');
          $gca("#gca-search-throbber", window.parent.document).addClass('hide');
        });
      </script>
    <?php }

	if ($smallMap == 0) {
      echo "<div id='search-results-count'>Results found: " . count($result);
      if (count($result) && count($result) != 1) {
        echo " | Showing " . ($offset + 1) . "-";
      }

      $lastItem = $offset + 15;
      if ($lastItem > count($result)) {
        $lastItem = count($result);
      }
      if (count($result) && count($result) != 1) {
        echo $lastItem;
      }
	}  
	echo "</div>";  
    // Show pagination

    if (count($result) > 15 && $smallMap == 0) {
      echo "<div id='search-pagination'>";
      $pages = ceil(count($result) / 15);
      if ($pages > 1) {
        for ($i = 1; $i < ($pages + 1); $i++) {
          if ($i == 1) {
            $newOffset = 0;
          } else {
            $newOffset = ($i - 1) * 15;
          }
          if ($newOffset != $offset) {
            $output .= "<a class='pagination-link' rel='?t=" . urlencode($originalTerms) . "&" . $olCode . "=" . urlencode($originalLocation) . "&r=" . $radius . "&o=" . $newOffset . "'>" . $i . "</a> ";
          } else {
            $output .= "<span class='current-page'>" . $i . "</span> ";
            $currentPage = $i;
            if ($currentPage > 1) {
              $previousPage = "<a class='pagination-link' rel='?t=" . urlencode($originalTerms) . "&" . $olCode . "=" . urlencode($originalLocation) . "&r=" . $radius . "&o=" . ($newOffset - 15) . "'>previous</a>&nbsp;&nbsp;&nbsp;";
            }
            if ($currentPage != $pages) {
              $nextPage = "&nbsp;&nbsp;&nbsp;<a class='pagination-link' rel='?t=" . urlencode($originalTerms) . "&" . $olCode . "=" . urlencode($originalLocation) . "&r=" . $radius . "&o=" . ($newOffset + 15) . "'>next</a>";
            }
          }
        }
        echo $previousPage . $output . $nextPage;
      }
      echo "</div>";
    }
    
    /*
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    */
    
    $displayResult = array_values($result);
    
    if ($testing == 2) {
      echo "displayParks: displayResult array: " . count($displayResult) . "<br />";
    }
    
    
    //echo "<pre>";
    //print_r($displayResult);
    //echo "</pre>";
    
	
	// 2012-06-27: Add featured parks to the top
	
	$featured = getFeaturedParks($displayResult);
	$numberFeatured = count($featured);
	if ($numberFeatured > 0) {
	  $displayResult = array_merge($featured, $displayResult);
	}
	
	//echo "<pre>";
	//echo "Featured<br />";
	//print_r($featured);
	//echo "</pre>";
    
    unset($mapResult);
    $mapResults = array();
	
    for ($p = $offset; $p < ($offset + 15); $p++) {
	  if ($smallMap == 0) {
	    if ($p < $numberFeatured) {
		  $featuredFlag = 1; // Yes, park is a featured park
		} else {
		  $featuredFlag = 0;
		}
        displayInfo($displayResult[$p], $distance, $radius, $stateFlag, $featuredFlag);
      }
	  if ($displayResult[$p]) {
        $mapResult[] = $displayResult[$p];
      }
    }
    
    if ($testing == 2) {
      echo "displayParks: mapResult array: " . count($mapResult) . "<br />";
    }
    
    /*
    echo "<pre>";
    print_r($mapResult);
    echo "</pre>";
    */
    
    if (count($mapResult) > 0 && $smallMap != 0) {
      displayMap($mapResult, $loc, $zoom, $smallMap);
    } else { ?>
      <script type="text/javascript">
        $gca(function() {
          $gca("#asw-right", window.parent.document).html('<img src="/sites/default/files/google_map.jpg">');
          $gca("#gca-search-throbber", window.parent.document).addClass('hide');
        });
      </script>
    <?php }
    
    // Show pagination

    if (count($result) > 15 && $smallMap != 1) {
      echo "<div id='search-pagination'>";
      $pages = ceil(count($result) / 15);
      if ($pages > 1) {
        for ($i = 1; $i < ($pages + 1); $i++) {
          if ($i == 1) {
            $newOffset = 0;
          } else {
            $newOffset = ($i - 1) * 15;
          }
          if ($newOffset != $offset) {
            echo "<a class='pagination-link' rel='?t=" . urlencode($originalTerms) . "&" . $olCode . "=" . urlencode($originalLocation) . "&r=" . $radius . "&o=" . $newOffset . "'>";
          } else {
            echo "<span class='current-page'>";
          }
          echo $i;
          if ($newOffset != $offset) {
            echo "</a>  ";
          } else {
            echo "</span>  ";
          }
        }
      }
      echo "</div>";
    }
}

function displayInfo($nid, $distance, $radius, $stateFlag, $featuredFlag) {
  if ($nid) {
    $nodeInfo = node_load($nid);
    echo "<div class='search-result-item";
	if ($featuredFlag == 1) {
	  echo " featured-listing";
	}
	echo "'>\n";
    echo "<div class='search-review-widget'>";
    $reviewsCheck = file_get_contents("http://travel.guestrated.com/Widget/Pages/SearchResultRating.aspx?custtypeid=8&portalid=3&customerid=" . $nodeInfo->field_camp_guestreview_id[0][value]);
    $checkPos = strpos($reviewsCheck, "9,9999review");
    if ($checkPos === false) {
      echo "<iframe src='/scripts/review_widget.php?id=" . $nodeInfo->field_camp_guestreview_id[0][value] . "' width='215' height='75'></iframe>";
    } else {
      //echo "No reviews yet.";
    }
    echo "</div>\n";
    echo "<h3><a href='/" . getResultAlias($nid) . "' target='new'>" . $nodeInfo->title . "</a>";
	if ($featuredFlag == 1) {
	  echo " <span class='featured-title'>FEATURED</span>";
	}
	$checkForDeal = checkForDeal($nid);
	//echo "<span style='color:#ccc;font-size:0.7em;'> " . $checkForDeal . "</span>";
	if ($checkForDeal == 1) {
	  echo " <span class='featured-title'>SPECIAL DEAL</span>";
	}
	echo "</h3>\n";
    if ($distance[$nid][city]) {
	  $latestInfo = getLatestInfo($nid, $distance[$nid]["vid"]);
	  
      //echo $distance[$nid][street] . "<br />" . $distance[$nid][city] . ", " . $distance[$nid][province] . "&nbsp;&nbsp;" . $distance[$nid][postal_code];
	  echo $latestInfo["street"] . "<br />" . $latestInfo["city"] . ", " . $latestInfo["province"] . "&nbsp;&nbsp;" . $latestInfo["postal_code"];
    } else {
      echo getCityState($nid);
    } 
    if ($distance[$nid][distance]) {
      if ($radius != 3000 && $stateFlag != 1) {
        echo " | " . ceil($distance[$nid][distance]) . " miles";
      }
      echo "<!-- | <a href='http://www.google.com/maps?q=" . $distance[$nid][latitude] . "+" . $distance[$nid][longitude] . "'>" . $distance[$nid][latitude] . " " . $distance[$nid][longitude] . "</a> -->";
    }
	//echo "<div style='color:#ccc;font-size:0.7em;'>";
	//echo $nid . " | " . $distance[$nid]["vid"] . " | " . $distance[$nid]["extended_stay"];
	//echo "</div>";
    echo "</div>";
  }
}

function getLatestInfo($nid, $vid) {
  $query = db_query("SELECT l.street, l.city, l.province, l.postal_code FROM {location} l, {location_instance} li WHERE l.lid = li.lid AND li.nid = %d AND li.vid = %d LIMIT 1", $nid, $vid);
  while ($row = db_fetch_array($query)) {
    $result["street"] = $row["street"];
	$result["city"] = $row["city"];
	$result["province"] = $row["province"];
	$result["postal_code"] = $row["postal_code"];
  }
  return $result;
}

function getCityState($nid) {
  $query = db_query("SELECT li.lid, l.street, l.city, l.province, l.postal_code FROM {location_instance} li, {location} l WHERE li.lid = l.lid AND li.genid LIKE 'cck:field_location%' AND li.nid = %d LIMIT 1", $nid);
  while ($row = db_fetch_object($query)) {
    $citystate = $row->street . "<br />" . $row->city . ", " . $row->province . "&nbsp;&nbsp;" . $row->postal_code;
    return $citystate;
  }
}

function displayMap($results, $loc, $zoom, $smallMap) {
  if ($testing == 2) {
    echo "displayMap: received results: " . count($results) . "<br />";
  }
  if (count($results) > 0) {

    if (!$zoom) {
      $zoom = 7;
    }
    $x = 0;
    foreach($results as $nid) {
      $query = db_query("SELECT l.latitude, l.longitude FROM {location} l, {location_instance} li, {node} n WHERE l.lid = li.lid AND li.nid = %d AND li.nid = n.nid ORDER BY n.created DESC LIMIT 1", $nid);
      while ($row = db_fetch_object($query)) {
        $coords[$x][nid] = $results[$x];
        $coords[$x][lat] = $row->latitude;
        $coords[$x][long] = $row->longitude;
      }
      // Get rates
      $rates = "";
      $query2 = db_query("SELECT DISTINCT td.name FROM {term_node} tn, {node} n, {term_data} td WHERE tn.nid = n.nid AND tn.tid = td.tid AND n.nid = %d AND td.vid = 11 ORDER BY td.weight ASC", $nid);
      while ($row = db_fetch_array($query2)) {
        $rates .= $row["name"] . ", ";
      }
      $coords[$x]["rates"] = substr($rates, 0, -2);
      $x++;
    }
  
    if ($testing == 2) {
      echo "displayMap: post loc query: " . $x . "<br />";
    }
  
    $markers = "markers: [";
    foreach ($coords as $result) {
      $markers .= '{ latitude: ' . $result[lat] . ', longitude: ' . $result[long] . ', html: "<b>' . getParkName($result[nid]) . '</b><br />Rates: ';
      if ($result["rates"]) {
        $markers .= $result["rates"];
      } else {
        $markers .= "Not Specified";
      }
      $markers .= '<br /><a href=\"/' . getResultAlias($result[nid]) . '\" target=\"_parent\">View &gt;&gt;</a>" }, ';
    }
    $markers = substr($markers, 0, (strlen($markers) - 2));
    $markers = $markers . "]";
    ?>
    <script type="text/javascript">
    $gca(function() {
      $gca("#search-map").gMap({ <?php echo $markers; ?>,
                  latitude: <?php echo $loc[1]; ?>,
                  longitude: <?php echo $loc[0]; ?>,
                  zoom: <?php echo $zoom; ?> });
      $gca("#search-map-small").gMap({ <?php echo $markers; ?>,
                  latitude: <?php echo $loc[1]; ?>,
                  longitude: <?php echo $loc[0]; ?>,
                  zoom: <?php echo $zoom; ?> });
      $gca(".admin-menu").removeClass("admin-menu");
    });
    </script><?php
  } else { ?>
    <script type="text/javascript">
        $gca(function() {
          $gca("#search-map-small").html('<img src="/sites/default/files/google_map.jpg">');
        });
      </script>
  <?php }
}

function lookup($string){
 
   $string = str_replace (" ", "+", urlencode($string));
   $details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$string."&sensor=false";
 
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $details_url);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   $response = json_decode(curl_exec($ch), true);
 
   // If Status Code is ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED or INVALID_REQUEST
   if ($response['status'] != 'OK') {
    return null;
   }
 
   //print_r($response);
   $geometry = $response['results'][0]['geometry'];
 
    $longitude = $geometry['location']['lat'];
    $latitude = $geometry['location']['lng'];
 
    $array = array(
        0 => $geometry['location']['lng'],
        1 => $geometry['location']['lat']
    );
 
    return $array;
 
}

function getGeocodes($address) {
  //$google_maps_key='ABQIAAAAYUCEx550pPnhZbiXhQp6KRTuLIPQxQ_MO9mtUG5QXxVZmkO4NhTIpXTHTAqs_C0eelzsC0qm-615jA';
  //$google_maps_key='AIzaSyDoFIdhWSMf90AsIjL82BIcCnL5nNSLdGE';
  //$adr = urlencode($address);
  //$url = "http://maps.google.com/maps/geo?q=".$adr."&output=xml&key=$google_maps_key";
  //$url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . $adr . "&sensor=false";
  //$url = "http://maps.google.com/maps/geo?q= " . $adr . "&output=xml";
  //$urlData = file_get_contents($url); 
  
  //echo "<textarea>";
  //echo $urlData;
  //echo "</textarea>";
  
  //$xml = simplexml_load_file($url);
  //$jsonArray = json_decode($urlData);
  //$resultsArray = $jsonArray->results;
  
  //$loc[0] = $resultsArray[0]->geometry->location->lng;
  //$loc[1] = $resultsArray[0]->geometry->location->lat;
  
  $loc = lookup($address);
  
  /*
  $status = $xml->Response->Status->code;
  if ($status='200') { 
    foreach ($xml->Response->Placemark as $node) { 
      $coordinates = $node->Point->coordinates;
      $loc = explode(",", $coordinates);
    }
  } else { 
    console.log("Geo look-up error.");
    $loc[0] = 0;
    $loc[1] = 0;
  }
  */
?>
<script type="text/javascript">
  console.log("loc[0] = " + "<?php echo $loc[0]; ?>");
  console.log("loc[1] = " + "<?php echo $loc[1]; ?>");
</script>
<?
  return $loc;
}

function getParkName($nid) {
  $query = db_query("SELECT title FROM {node} WHERE nid = %d LIMIT 1", $nid);
  while ($row = db_fetch_object($query)) {
    return $row->title;
  }
}

function getResultAlias($nid) {
  $target = "node/" . $nid;
  $query = db_query("SELECT dst FROM {url_alias} WHERE src ='%s'", $target);
  while ($row = db_fetch_object($query)) {
    $result = $row->dst;
  }
  if (!$result) {
    return $target;
  } else {
    return $result;
  }
}

function getFeaturedParks($list) {
  $result = array();
  
  foreach ($list as $park) {
    $query = db_query("SELECT field_park_tier_value FROM {content_type_camp} WHERE nid = %d ORDER BY vid DESC LIMIT 1", $park);
	while ($row = db_fetch_array($query)) {
	  if ($row["field_park_tier_value"] == 4) {
	    $result[] = $park;
	  }
	}
  }
  
  // Old query
  //$query = db_query("SELECT DISTINCT nid FROM {content_type_camp} WHERE nid IN ('" . implode("','", $list) . "') AND field_park_tier_value = 4");
	  
  return $result;
}
		
function recordSearch($loc, $terms) {
  $timenow = mktime();
  $ip = $_SERVER['REMOTE_ADDR'];
  $loc = str_replace('"', "", $loc);
  db_query("INSERT INTO {gca_searches} (ip, created, data, filters) VALUES ('%s', %d, '%s', '%s')", $ip, $timenow, $loc, $terms);
}

function getNewestVid($nid) {
  $query = db_query("SELECT vid FROM {node} WHERE nid = %d ORDER BY vid DESC LIMIT 1", $nid);
  while ($row = db_fetch_array($query)) {
    $result = $row["vid"];
  }
  return $result;
}

function checkForDeal($nid) {
  $query = db_query("SELECT field_park_state_assn_optin_value, field_camp_state_assnid_value FROM {content_type_camp} WHERE nid = %d ORDER BY vid DESC LIMIT 1", $nid);
  while ($row = db_fetch_array($query)) {
    // Check whether the park is opted in for state association deals
    if ($row["field_park_state_assn_optin_value"] == "on") {
	  
	  // Check whether the state association has an active deal
	  $assnDeal = checkAssnDeal($row["field_camp_state_assnid_value"]);
	  if ($assnDeal == 1) {
	    return 1;
	  }
	}
  }
  return 0;
}

function checkAssnDeal($assnID) {
  //echo "<span style='color:#ccc;font-size:0.7em;'> cad</span>";
  $userID = getUserID($assnID);
  $query = db_query("SELECT nid FROM {node} WHERE type = 'deal' AND uid = %d", $userID);
  $result = 0;
  while ($row = db_fetch_array($query)) {
    //echo "<span style='color:#ccc;font-size:0.7em;'> dl</span>";
    $times = getTimes($row["nid"]);
    //echo "<span style='color:#ccc;font-size:0.7em;'> " . $row['nid'] . " </span>";
    $startTime = strtotime(str_replace("T", " ", $times["start"]));
    $endTime = strtotime(str_replace("T", " ", $times["end"]));	  
    $timeNow = mktime();
	//echo "<span style='color:#ccc;font-size:0.7em;'> $startTime|$endTime|$timeNow</span>";

    if ($startTime < $timeNow && $endTime > $timeNow) {
	  //echo "<span style='color:#ccc;font-size:0.7em;'> y</span>";
      $result = 1;
    }
  }
  return $result;
}

function getTimes($nid) {
  $query = db_query("SELECT field_deal_start_value, field_deal_end_value FROM {content_type_deal} WHERE nid = %d ORDER BY vid DESC LIMIT 1" , $nid);
  while ($row = db_fetch_array($query)) {
    $result["start"] = $row["field_deal_start_value"];
	$result["end"] = $row["field_deal_end_value"];
  }
  return $result;
}

function getUserID($name) {
  $query = db_query("SELECT uid FROM {users} WHERE name = '%s' LIMIT 1", $name);
  while ($row = db_fetch_array($query)) {
    $result = $row["uid"];
  }
  return $result;
}

?>