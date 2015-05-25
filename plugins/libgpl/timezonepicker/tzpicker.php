<?php
  $input = array();
  $params = array('title', 'reset', 'favicon', 'tzname', 'longitude', 'latitude', 'zoom', 'maxzoom', 'minzoom', 'lang', 'width', 'height', 'left', 'top', 'callback', 'unload');
  foreach($_REQUEST as $key => $val){
    if(in_array($key, $params)){
      $input[$key] = urldecode(trim($val));
    }
  }
  $title = isset($input['title']) ? $input['title'] : 'Select Timezone';
  $reset = isset($input['reset']) ? $input['reset'] : 'Reset';
  $favicon = isset($input['favicon']) ? $input['favicon'] : false;
  $tzname = isset($input['tzname']) ? str_replace('_', ' ', $input['tzname']) : 'Europe/London';
  $longitude = isset($input['longitude']) ? (int) $input['longitude'] : 0;
  $latitude = isset($input['latitude']) ?  (int) $input['latitude'] : 0;
  $zoom = isset($input['zoom']) ?  (int) $input['zoom'] : 2;
  $minzoom = isset($input['minzoom']) ?  (int) $input['minzoom'] : 2;
  $maxzoom = isset($input['maxzoom']) ?  (int) $input['maxzoom'] : 6;
  $lang = isset($input['lang']) ? $input['lang'] : 'en';
  $width = isset($input['width']) ? (int) $input['width'] : 500;
  $height = isset($input['height']) ? (int) $input['height'] : 500;
  $left = isset($input['left']) ? (int) $input['left'] : 0;
  $top = isset($input['top']) ? (int) $input['top'] : 0;
  $callback = isset($input['callback']) ? $input['callback'] : false;
  $unload = isset($input['unload']) ? true : false;
  $zones = array();
  $select = '<select style=\'font-family: "Lucida Grande", Verdana, Arial, Helvetica, sans-serif; font-size: 11px\' onchange="$(\'#zonepicker\').timezonePicker(\'selectZone\', this.value)" id="tzselect">';
  foreach(DateTimeZone::listIdentifiers() as $i => $tzs){
    try{
      $tz      = new DateTimeZone($tzs);
      $date    = new DateTime(date('Y') . '-12-21', $tz);
      $offset  = $date->format('Z') + 45000;
      $sortkey = sprintf('%06d.%s', $offset, $tzs);
      $zones[$sortkey] = array($tzs, $date->format('P'));
    }
    catch(Exception $e){}
  }
  ksort($zones);
  foreach($zones as $zone){
    list($tzs, $offset) = $zone;
    $select .= '<option value="' . $tzs . '">' . strtr($tzs, '_', ' ') . ' (GMT ' . $offset . ')' . '</option>';
  }
  $select .= '</select>';
?>
<html>
<head> 
  <title>Select Timezone</title> 
  <?php
  if($favicon){
  ?>
  <link rel="shortcut icon" href="<?php echo $favicon ?>" /> 
  <?php
  }
  ?>
  <script type="text/javascript" src="https://maps.google.com/maps/api/js?sensor=false&language=<?php echo $lang ?>"></script> 
  <script type="text/javascript" src="../../../program/js/jquery.min.js"></script> 
  <script type="text/javascript" src="jquery.timezone-picker.js"></script> 
  <script> 
  function resizeViewPort (width, height){ 
    if(window.outerWidth){ 
      window.resizeTo( 
        width + (window.outerWidth - document.documentElement.clientWidth), 
        height + (window.outerHeight - document.documentElement.clientHeight) 
      ); 
    } 
    else{ 
      window.resizeTo(<?php echo $width ?>, <?php echo $height ?>); 
      window.resizeTo( 
        width + (<?php echo $width ?> - document.documentElement.clientWidth), 
        height + (<?php echo $height ?> - document.documentElement.clientHeight) 
      ); 
    } 
  } 
  
  $(function(){ 
    var initialSelect = true; 
    var initialutcOffset; 
    $("#zonepicker").timezonePicker({ 
      initialLat: <?php echo $latitude ?>, 
      initialLng: <?php echo $longitude ?>, 
      initialZoom: <?php echo $zoom ?>, 
      onReady: function(){
        $('#tzselect').val('<?php echo str_replace(' ', '_', $tzname) ?>').change(); 
      },
      onSelected: function(olsonName, utcOffset, tzName){ 
        $('#tzselect').val(olsonName); 
        <?php
        if($callback){
        ?> 
        if(initialSelect == false){ 
          var tzAdjust = utcOffset - initialutcOffset; 
          <?php echo $callback ?>(olsonName, utcOffset, tzName, tzAdjust); 
           //self.close();
        } 
        else{ 
          initialutcOffset = utcOffset; 
        } 
        initialSelect = false; 
        <?php
        } 
        ?> 
      }, 
      mapOptions:{ 
        maxZoom: <?php echo $maxzoom ?>, 
        minZoom: <?php echo $minzoom ?> 
      } 
    }); 
  }); 
  </script> 
</head> 
<body style='margin: 0; font-family: "Lucida Grande", Verdana, Arial, Helvetica, sans-serif; font-size: 11px; overflow: hidden'> 
  <div style="height: 20px; text-align: left; display: inline"><span>&nbsp;<?php echo $title ?>:&nbsp;</span><?php echo $select ?></div> 
  <div style="height: 20px; float: right; display: inline"><span><a href="javascript:void(0)" onclick="var arr = document.location.search.split('?tzname='); arr = arr[1].split('&'); $('#tzselect').val(arr[0]).change(); document.location.reload()"><?php echo $reset ?></a>&nbsp;</span></div> 
  <div id="zonepicker" style="margin: 0; width: 100%; height: 100%;"> 
  </div> 
<script> 
resizeViewPort(<?php echo $width ?>, <?php echo $height ?>); 
window.moveTo(<?php echo $left ?>, <?php echo $top ?>); 
<?php
if($unload){
?>
$(window).unload(function(){ 
  if(opener && opener.myrc_navigation_window_closed){ 
    opener.myrc_navigation_window_closed(self.location.href); 
  } 
}); 
<?php
}
?>
</script> 
</body> 
</html>