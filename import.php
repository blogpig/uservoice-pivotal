<?php

  include_once('config.php');
  include_once(LOCAL_PATH . '/uservoice_http.class.php');
  include_once(LOCAL_PATH . '/uservoice_oauth.class.php');
  include_once(LOCAL_PATH . '/common.php');

  header("Content-Type: text/xml");
  echo '<?xml version="1.0" encoding="UTF-8"?>
<external_stories type="array">
';

  $story_xml = '  <external_story>
    <external_id>%EXTERNAL_ID%</external_id>
    <name>%NAME%</name>
    <description>%DESCRIPTION%</description>
    <requested_by>%REQUESTED_BY%</requested_by>
    <created_at type="datetime">%CREATED_AT%</created_at>
    <story_type>%STORY_TYPE%</story_type>
    <estimate type="integer">%ESTIMATE%</estimate>
  </external_story>
';

  $oauth = new UserVoiceOAuth();
  if($oauth) {

    /*
     * Tickets...
     */

    if(defined('USERVOICE_IMPORT_TICKETS') && USERVOICE_IMPORT_TICKETS && USERVOICE_IMPORT_TICKETS != 'none') {
      $api_url = $oauth->signTrustedUrl(USERVOICE_API_URL . "/tickets.json?state=open&per_page=100");
      $http = new UserVoiceHTTP();
      if($http) {
        $api_reply = $http->getUrl($api_url);

        $data = @json_decode($api_reply);

        if($data) {
          if(isset($data->tickets)) {
            //var_dump($data->tickets);
            foreach($data->tickets as $ticket) {
              //var_dump($ticket);

              if(USERVOICE_IMPORT_TICKETS == 'all' || match_custom_fields(USERVOICE_IMPORT_TICKETS, $ticket->custom_fields)) {
                $story = $story_xml;

                $story = str_replace('%EXTERNAL_ID%', $ticket->id . '-uvt-' . $ticket->ticket_number, $story);
                $story = str_replace('%NAME%', xml_utf8_encode('Ticket #' . $ticket->ticket_number . (isset($ticket->created_by) && isset($ticket->created_by->name) ? ' by ' . $ticket->created_by->name : '')), $story);
                $story = str_replace('%DESCRIPTION%', xml_utf8_encode($ticket->subject), $story);
                $story = str_replace('%REQUESTED_BY%', xml_utf8_encode(isset($ticket->created_by) && isset($ticket->created_by->name) ? $ticket->created_by->name : ''), $story);
                $story = str_replace('%CREATED_AT%', $ticket->created_at, $story);
                $story = str_replace('%STORY_TYPE%', 'bug', $story);
                $story = str_replace('%ESTIMATE%', '', $story);
                echo $story;

                unset($story);
              }
            }
          }
          else if(isset($data->errors)) {
            // Something went wrong...
            log_import_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data->errors = " . print_r($data->errors, true));
          }
          else {
            // No error, but no tickets either?!
            log_import_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data = " . print_r($data, true));
          }
        }
        unset($data);
      }
      unset($http);
    }



    /*
     * Get forum discussions...
     */

    $forum_ids = array();
    if(defined('USERVOICE_IMPORT_FORUMS') && USERVOICE_IMPORT_FORUMS && USERVOICE_IMPORT_FORUMS != 'none') {
      if(USERVOICE_IMPORT_FORUMS != 'all') {
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
              log_import_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data->errors = " . print_r($data->errors, true));
            }
            else {
              // No error, but no forums either?!
              log_import_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data = " . print_r($data, true));
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
        $http = new UserVoiceHTTP();
        if($http) {
          foreach($forum_ids as $forum_id) {
            $api_url = $oauth->signTrustedUrl(USERVOICE_API_URL . "/forums/{$forum_id}/suggestions.json?per_page=100");
            $api_reply = $http->getUrl($api_url);

            $data = @json_decode($api_reply);
            if($data) {
              if(isset($data->suggestions)) {
                //var_dump($data->suggestions);
                // Got suggestions list...
                foreach($data->suggestions as $suggestion) {
                  //var_dump($suggestion);

                  if(!$category || ($category && isset($suggestion->category) && isset($suggestion->category->name) && strtolower($suggestion->category->name) == $category)) {
                    $story = $story_xml;

                    $story = str_replace('%EXTERNAL_ID%', $suggestion->id . '-uvs-' . $forum_id, $story);
                    $story = str_replace('%NAME%', xml_utf8_encode((isset($suggestion->category) && isset($suggestion->category->name) && $suggestion->category->name ? $suggestion->category->name . ': ' : '') . preg_replace('/\n.*$/is', '', $suggestion->title)), $story);
                    $story = str_replace('%DESCRIPTION%', xml_utf8_encode('Suggestion URL: ' . $suggestion->url  . "\n\n" . $suggestion->text), $story);
                    $story = str_replace('%REQUESTED_BY%', xml_utf8_encode(isset($suggestion->creator) && isset($suggestion->creator->name) ? $suggestion->creator->name : ''), $story);
                    $story = str_replace('%CREATED_AT%', $suggestion->created_at, $story);
                    $story = str_replace('%STORY_TYPE%', 'feature', $story);
                    $story = str_replace('%ESTIMATE%', '', $story);
                    echo $story;
                    
                    unset($story);
                  }
                }
              }
              else if(isset($data->errors)) {
                // Something went wrong...
                log_import_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data->errors = " . print_r($data->errors, true));
              }
              else {
                // No error, but no suggestions either?!
                log_import_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  data = " . print_r($data, true));
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

  echo '</external_stories>';

?>
