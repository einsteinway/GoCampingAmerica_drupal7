<?php
/* ****************** */
/* WEEKLY DATA DUMP   */
/* Created 11/14/2012 */
/* Updated 06/30/2013 */
/* ****************** */



// DRUPAL BOOTSTRAP
chdir($_SERVER['DOCUMENT_ROOT']);
define('DRUPAL_ROOT', __DIR__ . "/..");
global $base_url;
$base_url = 'http://' . $_SERVER['HTTP_HOST'];
require_once './includes/bootstrap.inc';
require_once './includes/common.inc';
require_once './includes/module.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
drupal_load('module', 'node');
module_invoke('node', 'boot');

require_once('scripts/PHPExcel.php');


ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);
ini_set('memory_limit','999M');

// EXECUTE

if (isset($_REQUEST["action"]) == "dump") {
  if (isset($_REQUEST["begin"]) && isset($_REQUEST["end"])) {
	//displayForm();
	doDataDump();
  }
} else {
  displayForm();
}

// FUNCTIONS


function doDataDump() {
  $begin = convertTime($_REQUEST["begin"]);
  $end = convertTime($_REQUEST["end"]);
  $allChangeIDs = getAllChangeIDs($begin, $end);
  
  // Loop through. Check for changes between current and previous (only
  // for specified fields). If a change (any change) is detected, add
  // the instance to the semifinal array for future analysis.
  $x = 0;
  foreach ($allChangeIDs as $instance) {
    if (checkForChange($instance) == 1) {
	  
    
	  /*if ($instance["username"] == 67164) {
	    echo "<pre>";
	    print_r($instance);
	    echo "</pre>";
      exit;
    }*/
   
	  $semiResult[$x]["Modified"] = date("r", $instance["modified"]);
	  $semiResult[$x]["Changed By"] = getChangedBy($instance["user"]);
    $semiResult[$x]["pkID"] = $instance["username"];

	  if ($instance["data"]->field_camp_status[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_camp_status[LANGUAGE_NONE][0]["value"]) {
	    $semiResult[$x]["Status"] = $instance["previous"]["data"]->field_camp_status[LANGUAGE_NONE][0]["value"] . " -> " . $instance["data"]->field_camp_status[LANGUAGE_NONE][0]["value"];
	  } else {
	    $semiResult[$x]["Status"] = "";
	  }
	  
	  if ($instance["park_name"] != $instance["previous"]["data"]->title) {
	    $semiResult[$x]["Member Name"] = $instance["previous"]["data"]->title . " -> " . $instance["park_name"];
	  } else {
	    $semiResult[$x]["Member Name"] = "";
	  }

      if ($instance["data"]->field_camp_website[LANGUAGE_NONE][0]["url"] != $instance["previous"]["data"]->field_camp_website[LANGUAGE_NONE][0]["url"]) {
        if($instance["data"]->field_camp_website[LANGUAGE_NONE][0]["url"] == ""){
          $semiResult[$x]["Website"] = $instance["previous"]["data"]->field_camp_website[LANGUAGE_NONE][0]["url"] . " -> " . "DELETED";
        }
        else {
          $semiResult[$x]["Website"] = (($instance["previous"]["data"]->field_camp_website[LANGUAGE_NONE][0]["url"] != "")?$instance["previous"]["data"]->field_camp_website[LANGUAGE_NONE][0]["url"]:"NONE") . " -> " . $instance["data"]->field_camp_website[LANGUAGE_NONE][0]["url"];
        }
      } else {
        $semiResult[$x]["Website"] = "";
      }
	        
	  if ($instance["data"]->field_location[LANGUAGE_NONE][0]["street"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["street"]) {
	    if ($instance["data"]->field_location[LANGUAGE_NONE][0]["street"] == "") {
		    $semiResult[$x]["Location Address 1"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["street"] . " -> " . "DELETED";
		  } else {
	      $semiResult[$x]["Location Address 1"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["street"] . " -> " . $instance["data"]->field_location[LANGUAGE_NONE][0]["street"];
	    }
	  } else {
	    $semiResult[$x]["Location Address 1"] = "";
	  }
	  
    // adding support for opening/close dates
    if ($instance["data"]->field_location[LANGUAGE_NONE][0]["street"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["street"]) {
	    if ($instance["data"]->field_location[LANGUAGE_NONE][0]["street"] == "") {
		    $semiResult[$x]["Location Address 1"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["street"] . " -> " . "DELETED";
		  } else {
	      $semiResult[$x]["Location Address 1"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["street"] . " -> " . $instance["data"]->field_location[LANGUAGE_NONE][0]["street"];
	    }
	  } else {
	    $semiResult[$x]["Location Address 1"] = "";
	  }
    
    
	  if ($instance["data"]->field_location[LANGUAGE_NONE][0]["additional"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["additional"]) {
	    if ($instance["data"]->field_location[LANGUAGE_NONE][0]["additional"] == "") {
		  $semiResult[$x]["Location Address 2"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["additional"] . " -> " . "DELETED";
		} else {
	      $semiResult[$x]["Location Address 2"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["additional"] . " -> " . $instance["data"]->field_location[LANGUAGE_NONE][0]["additional"];
	    }
	  } else {
	    $semiResult[$x]["Location Address 2"] = "";
	  }
	  
	  if ($instance["data"]->field_location[LANGUAGE_NONE][0]["city"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["city"]) {
	    if ($instance["data"]->field_location[LANGUAGE_NONE][0]["city"] == "") {
		  $semiResult[$x]["Location City"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["city"] . " -> " . "DELETED";
		} else {
		  $semiResult[$x]["Location City"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["city"] . " -> " . $instance["data"]->field_location[LANGUAGE_NONE][0]["city"];
	    }
	  } else {
	    $semiResult[$x]["Location City"] = "";
	  }
	  
	  if ($instance["data"]->field_location[LANGUAGE_NONE][0]["province"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["province"]) {
	    if ($instance["data"]->field_location[LANGUAGE_NONE][0]["province"] == "") {
		  $semiResult[$x]["Location State"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["province"] . " -> " . "DELETED";
		} else {
		  $semiResult[$x]["Location State"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["province"] . " -> " . $instance["data"]->field_location[LANGUAGE_NONE][0]["province"];
	    }
	  } else {
	    $semiResult[$x]["Location State"] = "";
	  }
	  
	  if ($instance["data"]->field_location[LANGUAGE_NONE][0]["postal_code"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["postal_code"]) {
	    if ($instance["data"]->field_location[LANGUAGE_NONE][0]["postal_code"] == "") {
		  $semiResult[$x]["Location ZIP"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["postal_code"] . " -> " . "DELETED";
		} else {
	      $semiResult[$x]["Location ZIP"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["postal_code"] . " -> " . $instance["data"]->field_location[LANGUAGE_NONE][0]["postal_code"];
	    }
	  } else {
	    $semiResult[$x]["Location ZIP"] = "";
	  }
	  
	  if ($instance["data"]->field_location[LANGUAGE_NONE][0]["country"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["country"]) {
	    if ($instance["data"]->field_location[LANGUAGE_NONE][0]["country"] == "") {
		  $semiResult[$x]["Location Country"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["country"] . " -> " . "DELETED";
		} else {
		  $semiResult[$x]["Location Country"] = $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["country"] . " -> " . $instance["data"]->field_location[LANGUAGE_NONE][0]["country"];
	    }
	  } else {
	    $semiResult[$x]["Location Country"] = "";
	  }
	  
	  if ($instance["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"] != $instance["previous"]["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"]) {
	    if ($instance["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"] == "") {
		  $semiResult[$x]["Phone"] = $instance["previous"]["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"] . " -> " . "DELETED";
		} else {
		  $semiResult[$x]["Phone"] = $instance["previous"]["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"] . " -> " . $instance["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"];
	    }
	  } else {
	    $semiResult[$x]["Phone"] = "";
	  }
	  
	  if ($instance["data"]->field_camp_fax[LANGUAGE_NONE][0]["number"] != $instance["previous"]["data"]->field_camp_fax[LANGUAGE_NONE][0]["number"]) {
	    if ($instance["data"]->field_camp_fax[LANGUAGE_NONE][0]["number"] == "") {
		  $semiResult[$x]["Fax"] = $instance["previous"]["data"]->field_camp_fax[LANGUAGE_NONE][0]["number"] . " -> " . "DELETED";
		} else {
		  $semiResult[$x]["Fax"] = $instance["previous"]["data"]->field_camp_fax[LANGUAGE_NONE][0]["number"] . " -> " . $instance["data"]->field_camp_fax[LANGUAGE_NONE][0]["number"];
	    }
	  } else {
	    $semiResult[$x]["Fax"] = "";
	  }
	  
	  if ($instance["data"]->field_camp_email[LANGUAGE_NONE][0]["email"] != $instance["previous"]["data"]->field_camp_email[LANGUAGE_NONE][0]["email"]) {
	    if ($instance["data"]->field_camp_email[LANGUAGE_NONE][0]["email"] == "") {
		  $semiResult[$x]["Email Address"] = $instance["previous"]["data"]->field_camp_email[LANGUAGE_NONE][0]["email"] . " -> " . "DELETED";
		} else {
		  $semiResult[$x]["Email Address"] = $instance["previous"]["data"]->field_camp_email[LANGUAGE_NONE][0]["email"] . " -> " . $instance["data"]->field_camp_email[LANGUAGE_NONE][0]["email"];
	    }
	  } else {
	    $semiResult[$x]["Email Address"] = "";
	  }

	  if ($instance["data"]->field_camp_tollfree_phone_number[LANGUAGE_NONE][0]["number"] != $instance["previous"]["data"]->field_camp_tollfree_phone_number[LANGUAGE_NONE][0]["number"]) {
	    if ($instance["data"]->field_camp_tollfree_phone_number[LANGUAGE_NONE][0]["number"] == "") {
		  $semiResult[$x]["Toll-Free Number"] = $instance["previous"]["data"]->field_camp_tollfree_phone_number[LANGUAGE_NONE][0]["number"] .  " -> " . "DELETED";
		} else {
	      $semiResult[$x]["Toll-Free Number"] = $instance["previous"]["data"]->field_camp_tollfree_phone_number[LANGUAGE_NONE][0]["number"] . " -> " . $instance["data"]->field_camp_tollfree_phone_number[LANGUAGE_NONE][0]["number"];
	    }
	  } else {
	    $semiResult[$x]["Toll-Free Number"] = "";
	  }
    
    // logging date opening close 
    if ($instance["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"]) {
      if (empty($instance["data"]->field_park_date_open) || $instance["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"] == "") {
        $semiResult[$x]["Month Open"] = $instance["previous"]["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"] . " -> " . "DELETED";
      } else {
 	      $semiResult[$x]["Month Open"] = $instance["previous"]["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"] . " -> " . $instance["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"];
      }
    } else {
      $semiResult[$x]["Month Open"] = "";
    }
    
    if ($instance["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"]) {
      if (empty($instance["data"]->field_park_date_open_day) || $instance["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"] == "") {
        $semiResult[$x]["Day Open"] = $instance["previous"]["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"] . " -> " . "DELETED";
      } else {
 	      $semiResult[$x]["Day Open"] = $instance["previous"]["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"] . " -> " . $instance["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"];
      }
    } else {
      $semiResult[$x]["Day Open"] = "";
    }
    
    if ($instance["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"]) {
      if (empty($instance["data"]->field_park_date_closed_month) || $instance["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"] == "") {
        $semiResult[$x]["Month Closed"] = $instance["previous"]["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"] . " -> " . "DELETED";
      } else {
 	      $semiResult[$x]["Month Closed"] = $instance["previous"]["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"] . " -> " . $instance["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"];
      }
    } else {
      $semiResult[$x]["Month Closed"] = "";
    }
    
    if ($instance["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"]) {
      if (empty($instance["data"]->field_park_date_closed_day[LANGUAGE_NONE]) || $instance["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"] == "") {
        $semiResult[$x]["Day Closed"] = $instance["previous"]["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"] . " -> " . "DELETED";
      } else {
 	      $semiResult[$x]["Day Closed"] = $instance["previous"]["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"] . " -> " . $instance["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"];
      }
    } else {
      $semiResult[$x]["Day Closed"] = "";
    }
	  // end logging dates
	  
	  $x++;
	}
  }
  
  //echo "<pre>";
  //print_r($semiResult);
  //echo "</pre>";
  
  
  exportSpreadsheet($semiResult, $begin, $end);
  
  // Was going to loop through one last time to remove duplicates. Changed my mind. Duplicates will stand.
  
  /*
  echo "<table><tr><td valign='top'>";
  echo "Count: " . count($allChangeIDs) . "<br />";
  for ($i = 0; $i < count($allChangeIDs); $i++) {
    echo "cid: " . $allChangeIDs[$i]["cid"] . "|nid: " . $allChangeIDs[$i]["nid"] . "|" . $allChangeIDs[$i]["modified"] . "<br />";
  }
  echo "</td><td valign='top'>";
  echo "Count: " . count($semiResult) . "<br />";
  for ($i = 0; $i < count($semiResult); $i++) {
    echo "cid: " . $semiResult[$i]["cid"] . "|nid: " . $semiResult[$i]["nid"] . "|" . $semiResult[$i]["modified"] . "<br />";
  }
  
  echo "</td><td valign='top'>";
  echo "</tr></table>";
  */
}

function checkForChange($instance) {
  if (
    ($instance["park_name"] != $instance["previous"]["data"]->title) ||
  ($instance["data"]->field_camp_website != $instance["previous"]["data"]->field_camp_website) ||
	($instance["data"]->field_location[LANGUAGE_NONE][0]["street"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["street"]) ||
	($instance["data"]->field_location[LANGUAGE_NONE][0]["additional"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["additional"]) ||
	($instance["data"]->field_location[LANGUAGE_NONE][0]["city"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["city"]) ||
	($instance["data"]->field_location[LANGUAGE_NONE][0]["province"] != $instance["previous"]["data"]->field_location[LANGUAGE_NONE][0]["province"]) ||
	($instance["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"] != $instance["previous"]["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"]) ||
	($instance["data"]->field_camp_email[LANGUAGE_NONE][0]["email"] != $instance["previous"]["data"]->field_camp_email[LANGUAGE_NONE][0]["email"]) ||
	($instance["data"]->field_camp_status[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_camp_status[LANGUAGE_NONE][0]["value"]) ||
	($instance["data"]->field_camp_tollfree_phone_number[LANGUAGE_NONE][0]["number"] != $instance["previous"]["data"]->field_camp_tollfree_phone_number[LANGUAGE_NONE][0]["number"]) ||

  ($instance["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"]) ||
  ($instance["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"]) ||
  ($instance["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"]) ||
  ($instance["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"] != $instance["previous"]["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"])  
  
  ) {
    return 1;
  }
  return 0;
}

function getAllChangeIDs($begin, $end) {
  $query = db_query("SELECT cid, username, park_name, nid, user, modified, data FROM {park_changes} WHERE modified > :begin AND modified < :end ORDER BY modified DESC", array(':begin' => $begin, ':end' => $end));

  $x = 0;
  while ($row = $query->fetchAssoc()) {
    //die ('<pre>'.print_r($row,true));
    if (checkGCARole($row["user"]) != 1) {
      $result[$x]["cid"] = $row["cid"];
	  $result[$x]["username"] = $row["username"];
	  $result[$x]["park_name"] = $row["park_name"];
	  $result[$x]["user"] = $row["user"];
	  $result[$x]["nid"] = $row["nid"];
	  $result[$x]["modified"] = $row["modified"];
	  $result[$x]["data"] = unserialize($row["data"]);
	  $result[$x]["previous"] = getPreviousChange($row["cid"], $row["nid"], $row["modified"]);
	  $x++;
    }
  }
  //die( "getAllChangeIDs");
  if (isset($result)) {
    return $result;
  }
  return;
}

function getPreviousChange($cid, $nid, $modified) {
  //die ("nid ".$nid);
  $query = db_query("SELECT cid, username, data FROM {park_changes} WHERE nid = :nid AND modified < :modified ORDER BY modified DESC LIMIT 1", array(':nid' => $nid, ':modified' => $modified));
  //if ($nid == 94215) die( t("SELECT cid, username, data FROM {park_changes} WHERE nid = :nid AND modified < :modified ORDER BY modified DESC LIMIT 1", array(':nid' => $nid, ':modified' => $modified)));
  while ($row = $query->fetchAssoc()) {
    $result["cid"] = $row["cid"];
    $result["username"] = $row["username"];
    $result["data"] = unserialize($row["data"]);
  }
  if (isset($result)) {
    return $result;
  }
  return array();;
}

function doDataDump2() {
  $begin = convertTime($_REQUEST["begin"]);
  $end = convertTime($_REQUEST["end"]);
  //echo "Begin: " . $_REQUEST["begin"] . " | End: " . $_REQUEST["end"] . "<br />";
  //echo "Begin: " . $begin . " | End: " . $end . "<br />";
  $parks = getParks($begin, $end);
  $x = 0;
  //echo "Changes: " . count($parks) . "<br />";
  $parksAdded = array();
  foreach ($parks as $park) {
    $parkInfo = getParkInfo($park);
    if (!in_array($parkInfo["nid"], $parksAdded)) {
	  $spreadsheet[$x] = extractFields($parkInfo);
	  $parksAdded[] = $parkInfo["nid"];
	  $x++;
    }
  }
  //echo "Parks Changed: " . $x . "<br />";
  //echo "<pre>";
  //print_r($spreadsheet);
  //echo "</pre>";
  exportSpreadsheet($spreadsheet, $begin, $end);
}

function exportSpreadsheet($info, $begin, $end) {
  //$headerNames = array("pkID", "Status", "Member Name", "Location Address 1", "Location Address 2", "Location City", "Location State", "Location ZIP", "Location Country", "Phone", "Fax", "Email Address", "Website", "Primary Contact Name", "Billing Address 1", "Billing Address 2", "Billing City", "Billing State", "Billing ZIP", "Billing Country", "Month Open", "Day Open", "Month Closed", "Day Closed", "Open Year-Round", "Sites Cabin", "Sites Electric_Water", "Sites Electrical", "Sites Full Hookups", "Sites No Hookups", "Sites Other", "Sites Park Model", "Sites Teepee", "Sites Tent", "Sites Total RV", "Sites Yurt", "Sites Total", "Sites Total Reported", "Company Association", "pkGuestReviewID", "State Association Name", "fkStateID");
  $headerNames = array("Modified", "Changed By", "pkID", "Status", "Member Name", "Website",  "Location Address 1", "Location Address 2", "Location City", "Location State", "Location ZIP", "Location Country", "Phone", "Fax", "Email Address", "Toll-Free Number", "Month Open", "Day Open", "Month Closed", "Day Closed");
  // Create new excel object
  $objPHPExcel = new PHPExcel();

  // Set metadata
  $objPHPExcel->getProperties()->setCreator("ARVC")
							 ->setLastModifiedBy("ARVC")
							 ->setTitle("ARVC")
							 ->setSubject("ARVC")
							 ->setDescription("ARVC")
							 ->setKeywords("ARVC")
							 ->setCategory("ARVC");
  $objPHPExcel->getDefaultStyle()->getFont()
    ->setName('Arial')
    ->setSize(10);
  $objPHPExcel->getActiveSheet()->setTitle('Data Dump');

  // Define active sheet
  $objPHPExcel->setActiveSheetIndex(0);
  $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(18);

  // Set column header names
  $cols = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP");
  $x = 0;
  foreach ($headerNames as $name) {
    $cell = $cols[$x] . "1";
    $objPHPExcel->getActiveSheet()->SetCellValue($cell, $name);
    $x++;
  }
  $styleArray = array('font' => array('bold' => true));
  $objPHPExcel->getActiveSheet()->getStyle('A1:AP1')->applyFromArray($styleArray);
  
  $x = 2;
  foreach ($info as $datarow) {
    $y = 0;
	foreach ($datarow as $key => $value) {
      $cell = $cols[$y] . $x;
	  $objPHPExcel->getActiveSheet()->SetCellValue($cell, $value);
      $y++;
    }
	$x++;
  }
  
  $OutputFilename = "Data Dump (" . date("Y-m-d", $begin) . " - " . date("Y-m-d", $end) . ").xlsx";
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); 
  header("Content-Disposition: attachment;filename=\"" . $OutputFilename . "\"");
  header('Cache-Control: max-age=0'); 

  $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

  unset($projectData);
  unset($pmCased);
  unset($headerNames);

  $objWriter->save('php://output');
}

function getChangedBy($user) {
  $query = db_query("SELECT name FROM {users} WHERE uid = :uid LIMIT 1", array(':uid' => $user));
  while ($row = $query->fetchAssoc()) {
    $result = $row["name"];
  }
  if ($result) {
    return $result;
  }
  return;
}

function extractFields($info) {
  $result["Changed By"] = getChangedBy($info["user"]);
  $result["pkID"] = $info["username"];
  $result["Status"] = $info["data"]->field_camp_status[LANGUAGE_NONE][0]["value"];
  $result["Member Name"] = $info["park_name"];
  $result["Website"] = $info["data"]->field_camp_website;
  $result["Location Address 1"] = $info["data"]->field_location[LANGUAGE_NONE][0]["street"];
  $result["Location Address 2"] = $info["data"]->field_location[LANGUAGE_NONE][0]["additional"];
  $result["Location City"] = $info["data"]->field_location[LANGUAGE_NONE][0]["city"];
  $result["Location State"] = $info["data"]->field_location[LANGUAGE_NONE][0]["province"];
  $result["Location ZIP"] = $info["data"]->field_location[LANGUAGE_NONE][0]["postal_code"];
  $result["Location Country"] = $info["data"]->field_location[LANGUAGE_NONE][0]["country"];
  $result["Phone"] = $info["data"]->field_camp_phone[LANGUAGE_NONE][0]["number"];
  $result["Fax"] = $info["data"]->field_camp_faxs[LANGUAGE_NONE][0]["number"];
  $result["Email Address"] = $info["data"]->field_camp_email[LANGUAGE_NONE][0]["email"];
  //$result["Website"] = $info["data"]->field_camp_website[0]["url"];
  $result["Primary Contact Name"] = $info["data"]->field_park_contact_name[LANGUAGE_NONE][0]["value"];
  $result["Billing Address 1"] = $info["data"]->field_park_billing_street[LANGUAGE_NONE][0]["value"];
  $result["Billing Address 2"] = $info["data"]->field_park_billing_street2[LANGUAGE_NONE][0]["value"];
  $result["Billing City"] = $info["data"]->field_park_billing_city[LANGUAGE_NONE][0]["value"];
  $result["Billing State"] = $info["data"]->field_park_billing_state[LANGUAGE_NONE][0]["value"];
  $result["Billing ZIP"] = $info["data"]->field_park_billing_zip[LANGUAGE_NONE][0]["value"];
  $result["Billing Country"] = $info["data"]->field_park_billing_country[LANGUAGE_NONE][0]["value"];
  $result["Month Open"] = $info["data"]->field_park_date_open[LANGUAGE_NONE][0]["value"];
  $result["Day Open"] = $info["data"]->field_park_date_open_day[LANGUAGE_NONE][0]["value"];
  $result["Month Closed"] = $info["data"]->field_park_date_closed_month[LANGUAGE_NONE][0]["value"];
  $result["Day Closed"] = $info["data"]->field_park_date_closed_day[LANGUAGE_NONE][0]["value"];
  /*
  $result["Open Year-Round"] = "";
  if ($info["data"]->field_camp_open_year_round[0]["value"] == "off") {
    $result["Open Year-Round"] = "No";
  } elseif ($info["data"]->field_camp_open_year_round[0]["value"] == "on") {
    $result["Open Year-Round"] = "Yes";
  }
  $result["Sites Cabin"] = $info["data"]->field_camp_rental_cabins[0]["value"];
  $result["Sites Electric_Water"] = $info["data"]->field_camp_electric_water[0]["value"];
  $result["Sites Electrical"] = $info["data"]->field_camp_electrical[0]["value"];
  $result["Sites Full Hookups"] = $info["data"]->field_camp_full_hookups[0]["value"];
  $result["Sites No Hookups"] = $info["data"]->field_camp_no_hookups[0]["value"];
  $result["Sites Other"] = $info["data"]->field_camp_other[0]["value"];
  $result["Sites Park Model"] = $info["data"]->field_camp_rental_trailers[0]["value"];
  $result["Sites Teepee"] = $info["data"]->field_camp_teepee[0]["value"];
  $result["Sites Tent"] = $info["data"]->field_camp_tents[0]["value"];
  $result["Sites Total RV"] = $info["data"]->field_camp_total_rv_calc[0]["value"];
  $result["Sites Yurt"] = $info["data"]->field_camp_yurts[0]["value"];
  $result["Sites Total"] = $info["data"]->field_camp_total_calc[0]["value"];
  $result["Sites Total Reported"] = $info["data"]->field_camp_totals[0]["value"];
  $result["Company Association"] = $info["data"]->field_camp_company_assoc[0]["value"];
  $result["pkGuestReviewID"] = $info["data"]->field_camp_guestreview_id[0]["value"];
  $result["State Association Name"] = $info["data"]->field_camp_state_assnname[0]["value"];
  $result["fkStateID"] = $info["data"]->field_camp_state_assnid[0]["value"];
  */
  return $result;
}

function getParkInfo($cid) {
  $query = db_query("SELECT nid, username, park_name, modified, data, user FROM {park_changes} WHERE cid = :cid LIMIT 1", array(':cid' => $cid));
  while ($row = $query->fetchAssoc()) {
    $result["cid"] = $cid;
	$result["nid"] = $row["nid"];
	$result["username"] = $row["username"];
	$result["park_name"] = $row["park_name"];
	$result["modified"] = $row["modified"];
	$result["data"] = unserialize($row["data"]);
	$result["user"] = $row["user"];
  }
  if ($result) {
    return $result;
  }
  return;
}

function checkGCARole($user) {
  $query = db_query("SELECT rid FROM {users_roles} WHERE uid = :uid AND (rid = 6 || rid = 4)", array(':uid' => $user));
  while ($row = $query->fetchAssoc()) {
    return 1;
  }
  return 0;
}

function getParks($begin, $end) {
  $query = db_query("SELECT cid, user FROM {park_changes} WHERE modified > :begin AND modified < :end ORDER BY modified DESC", array(':begin' => $begin, ':end' => $end));
  while ($row = $query->fetchAssoc()) {
    if (checkGCARole($row["user"]) != 1) {
	  $result[] = $row["cid"];
    }
  }
  if ($result) {
    return $result;
  }
  return;
}

function convertTime($string) {
  return strtotime("12:00am " . $string);
}

function displayForm() { ?>

Weekly Data Dump (New)<br />
(Date format: mm/dd/yyyy)<br />
<form method="get">
  <input type="hidden" name="action" value="dump" />
  <table>
  <tr><td>Begin:</td><td><input type="text" name="begin" /></td></tr>
  <tr><td>End:</td><td><input type="text" name="end" /></td></tr>
  </table>
  <input type="submit" name="Submit" />
</form>

<?php }


?>