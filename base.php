<?php

  include_once('config.php');
  include_once(LOCAL_PATH . '/common.php');

  $uv = array(
    'id' => false,
    'type' => false,
    'type_id' => false,
  );

  $other_id = trim($_SERVER['PATH_INFO'], '/');
  $ids = array();
  if(preg_match('/^([0-9]+)\-uv([a-z])\-?([0-9]+)?$/i', $other_id, $ids)) {
    $uv['id'] = $ids[1];
    $uv['type'] = $ids[2];
    $uv['type_id'] = isset($ids[3]) ? $ids[3] : false;
  }

  $base_url = USERVOICE_SITE_URL;
  
  if($uv['id'] && $uv['type'] && $uv['type_id']) {
    if($uv['type'] == 's') {
      // Suggestion
      $base_url .= "/forums/{$uv['type_id']}/suggestions/{$uv['id']}";
    }
    if($uv['type'] == 't') {
      // Ticket
      $base_url .= "admin/tickets/{$uv['type_id']}/";
    }
  }
  else {
    log_error_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  missing/partial base info - uv = " . print_r($uv, true));
  }

  header("Location: {$base_url}");

?>
