<div id="advanced-search-widget" class="home-search">
  <div id="search-map" style="float:right;"><a href="#large-map" onClick="_gaq.push(['_trackEvent', 'Graphics', 'Homepage Map', 'Clicked',, false]);" ><img src="/sites/all/themes/gca_new/images/home_search_map.gif" width="126" height="178" alt="Search by state" class="activate-map-front" align="left" /></a>
    <!-- Use this map to quickly search by rv parks and campgrounds by state. Use the search tool at the left to search by address, landmark or park name. -->
  </div>

  <div id="asw-left">
    <div id="asw-tabs" class="fap-tab-1">
      <img src="/sites/all/themes/gca_new_interior/images/spacer.gif" width="114" height="26" class="fap-tab-general" /><img src="/sites/all/themes/gca_new_interior/images/spacer.gif" width="114" height="26" class="fap-tab-parkname" /><!-- <img src="/sites/all/themes/gca_new_interior/images/spacer.gif" width="114" height="26" class="fap-tab-landmarks" /> -->
    </div>
    <div id="asw-location" class="asw-tabs-box">
      <div class="search-label"><b>Enter Search Terms</b></div>
      <div class="search-label">Enter an address, city, state or landmark name</div>
      <input type="text" name="location" id="search-location" class="ui-corners-all" />
      <div id="distance-wrapper">
        <select name="distance" id="search-distance">
          <option value=10>Within 10 miles</option>
          <option value=15>Within 15 miles</option>
          <option value=20>Within 20 miles</option>
          <option value=25>Within 25 miles</option>
          <option value=50>Within 50 miles</option>
          <option value=75>Within 75 miles</option>
          <option value=100>Within 100 miles</option>
          <option value=150>Within 150 miles</option>
          <option value=200>Within 200 miles</option>
        </select>
      </div> <!-- distance-wrapper -->
      <!-- <div id="official-optin" class="search-label"><input type="checkbox" name="official" value="yes" id="optin" /> Show only parks honoring GCA promotions</div> -->
    </div> <!-- asw-location -->
    <div id="asw-parkname" class="asw-tabs-box hide">
      <div class="search-label">Enter a Park Name</div>
      <input type="text" name="park" id="search-location-park" class="ui-corners-all" />
    </div> <!-- asw-parkname -->
    <div id="asw-landmarks" class="asw-tabs-box hide">
      <!-- <div id="location-label">Select a Landmark</div> -->
      <div id="landmark-wrapper">
        <div class="search-label">Select a Featured Landmark</div>
        <ul id="search-landmarks" class="ui-corners-all"><?php
          foreach (getLandmarks() as $landmark) {
            echo "<li rel='" . $landmark[coords] . "'>" . $landmark[title] . "</li>";
          }
          ?></ul>
        <div id="distance-wrapper">
          <select name="distance" id="search-landmark-distance">
            <option value=10>Within 10 miles</option>
            <option value=15>Within 15 miles</option>
            <option value=20>Within 20 miles</option>
            <option value=25>Within 25 miles</option>
            <option value=50>Within 50 miles</option>
            <option value=75>Within 75 miles</option>
            <option value=100>Within 100 miles</option>
            <option value=150>Within 150 miles</option>
            <option value=200>Within 200 miles</option>
          </select>
        </div> <!-- distance-wrapper -->
      </div> <!-- landmark wrapper -->
    </div> <!-- asw-landmarks -->
    <div id="search-box" style="margin-top:10px;">
      <img src="/sites/all/themes/gca_new_interior/images/fap_search.gif" class="fap-search-box" style="cursor:pointer;float:left;margin-right:10px;" /><a href="<? echo url("findpark"); ?>" style="float:left;padding-top:10px;">Advanced Search</a>
    </div>
  </div> <!-- asw-left -->
  <br clear="all" />
</div> <!-- adv search widget -->

<img src="/sites/all/themes/gca_new/images/body_divider.jpg" width="" height="" class="home-body-divider" />

<?php

function getLandmarks() {
//  $query = db_query("SELECT n.nid, n.title, ctl.field_landmark_latitude_value as latitude, ctl.field_landmark_longitude_value as longitude FROM {node} n, {content_type_landmarks} ctl WHERE n.nid = ctl.nid AND n.type = 'landmarks' AND n.status = 1 ORDER BY n.title ASC");
	$query = db_query("SELECT n.nid, n.title, ctllat.field_landmark_latitude_value as latitude, ctllng.field_landmark_longitude_value as longitude FROM {node} n JOIN {field_data_field_landmark_latitude} ctllat ON n.nid = ctllat.entity_id JOIN {field_data_field_landmark_longitude} ctllng ON n.nid = ctllng.entity_id WHERE n.type = 'landmarks' AND n.status = 1 ORDER BY n.title ASC");
  $x = 0;
  while ($row = $query->fetchObject()) {
    $landmarks[$x][nid] = $row->nid;
    $landmarks[$x][title] = $row->title;
    $landmarks[$x][coords] = $row->longitude . "|" . $row->latitude;
    $x++;
  }
  return $landmarks;
}

?>