<?php
// headers ...
$json = json_decode(file_get_contents('tz_json/bounding_boxes.json'), true);
$tznames = array();
foreach($json as $idx => $zones){
  foreach($zones['zoneCentroids'] as $tzname => $zone){
    $tznames[] = array(
      'name' => $tzname,
      'txt'  => str_replace('_', ' ', $tzname),
    );
  }
}
echo json_encode($tznames);
exit;
?>