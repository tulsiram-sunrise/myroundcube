<?php
// headers ...
$json = file_get_contents('bounding_boxes.json');
echo 'jsonp_callback_boundingBoxes(' . $json . ')';
exit;
?>