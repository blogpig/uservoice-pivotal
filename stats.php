<?php

  include_once('config.php');
  include_once(LOCAL_PATH . '/uservoice_http.class.php');
  include_once(LOCAL_PATH . '/uservoice_oauth.class.php');
  include_once(LOCAL_PATH . '/common.php');

  $stats = array();

  $oauth = new UserVoiceOAuth();
  if($oauth) {

    /*
     * Forums
     */

    $uservoice_forums = isset($_GET['forum']) && $_GET['forum'] ? strtolower($_GET['forum']) : false;

    $forum_ids = array();
    if(($uservoice_forums && $uservoice_forums != 'none') || (defined('USERVOICE_IMPORT_FORUMS') && USERVOICE_IMPORT_FORUMS && USERVOICE_IMPORT_FORUMS != 'none')) {
      if($uservoice_forums && $uservoice_forums != 'none' && $uservoice_forums != 'all') {
        $forum_ids = explode(',', $uservoice_forums);
      }
      else if(USERVOICE_IMPORT_FORUMS != 'all') {
        $forum_ids = explode(',', USERVOICE_IMPORT_FORUMS);
      }
      else {
        // All forums - retrieve forums list first...
        $api_url = $oauth->signTrustedUrl(USERVOICE_API_URL . "/forums.json");
        $http = new UserVoiceHTTP();
        if($http) {
          $api_reply = $http->getUrl($api_url);

          $data = @json_decode($api_reply);
          if($data) {
            if(isset($data->forums)) {
              //var_dump($data->forums);
              // Got forums list...
              foreach($data->forums as $forum) {
                $forum_ids[] = $forum->id;
              }
            }
            else if(isset($data->errors)) {
              // Something went wrong...
              log_stats_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data->errors = " . print_r($data->errors, true));
            }
            else {
              // No error, but no forums either?!
              log_stats_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data = " . print_r($data, true));
            }

          }
          unset($data);

        }
        unset($http);
      }
      //var_dump($forum_ids);

      $category = isset($_GET['category']) && $_GET['category'] ? strtolower($_GET['category']) : false;
      //var_dump($category);

      if($forum_ids && is_array($forum_ids) && count($forum_ids) > 0) {
        $stats['forums'] = array();
        $stats['forums_total'] = 0;

        $http = new UserVoiceHTTP();
        if($http) {
          foreach($forum_ids as $forum_id) {
            $api_url = $oauth->signTrustedUrl(USERVOICE_API_URL . "/forums/{$forum_id}/suggestions.json?per_page=10");
            $api_reply = $http->getUrl($api_url);

            $data = @json_decode($api_reply);
            if($data) {
              if(!empty($data->response_data->total_records)) {
                $stats['forums'][$forum_id] = $data->response_data->total_records;
                $stats['forums_total'] += $data->response_data->total_records;
              }
              else if(isset($data->errors)) {
                // Something went wrong...
                log_stats_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data->errors = " . print_r($data->errors, true));
              }
              else {
                // No error, but no suggestions either?!
                log_stats_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data = " . print_r($data, true));
              }

            }
            unset($data);
          }
        }
        unset($http);

      }
    }


  }
  unset($oauth);

  echo json_encode($stats);

?>
