<?php

  include_once('config.php');
  include_once(LOCAL_PATH . '/uservoice_http.class.php');
  include_once(LOCAL_PATH . '/uservoice_oauth.class.php');
  include_once(LOCAL_PATH . '/common.php');

  $body = file_get_contents('php://input');
  $xml = @simplexml_load_string($body);
  log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  xml = " . print_r($xml, true));

  if($xml) {
    if(isset($xml->event_type) && $xml->event_type) {
      if(isset($xml->stories) && isset($xml->stories->story) && isset($xml->stories->story->other_id) && $xml->stories->story->other_id) {
        $uv = array(
          'id' => false,
          'type' => false,
          'type_id' => false,
        );

        $other_id = $xml->stories->story->other_id;
        $ids = array();
        if(preg_match('/^([0-9]+)\-uv([a-z])\-?([0-9]+)?$/i', $other_id, $ids)) {
          $uv['id'] = $ids[1];
          $uv['type'] = $ids[2];
          $uv['type_id'] = isset($ids[3]) ? $ids[3] : false;
        }
        log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  uv = " . print_r($uv, true));

        if($uv['id'] && $uv['type']) {

          log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  xml->event_type = " . print_r($xml->event_type, true));

          $oauth = new UserVoiceOAuth();
          if($oauth) {

            $api_url = false;
            $params = false;

            switch($xml->event_type) {
              case 'story_create':
                if($uv['type'] == 's') {
                  // Suggestions
                  if(defined('USERVOICE_UPDATE_FORUMS') && USERVOICE_UPDATE_FORUMS != 'none') {
                    // Set status to 'under review'...
                    /*
                     * respond  PUT /api/v1/forums/forum_id/suggestions/suggestion_id/respond.format
                     */
                    $access_token = $oauth->getAccessToken();
                    log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  access_token = " . print_r($access_token, true));

                    $api_url = $oauth->signTrustedUrl(USERVOICE_API_URL . "/forums/{$uv['type_id']}/suggestions/{$uv['id']}/respond.json", $access_token, 'PUT');
                    log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  api_url = " . print_r($api_url, true));
                    $params = array(
                      '_method' => 'put',
                      'response[status]' => 'under review',
                    );
                  }
                }
                else if($uv['type'] == 't') {
                  // Tickets
                  if(defined('USERVOICE_UPDATE_TICKETS') && USERVOICE_UPDATE_TICKETS != 'none') {
                    // Nothing to do... the status is already 'open'...
                  }
                }
                break;
              case 'story_update':

                $current_state = (isset($xml->stories->story->current_state)) && $xml->stories->story->current_state ? $xml->stories->story->current_state : false;

                if($uv['type'] == 's') {
                  // Suggestions
                  if(defined('USERVOICE_UPDATE_FORUMS') && USERVOICE_UPDATE_FORUMS != 'none') {
                    /*
                     * respond  PUT /api/v1/forums/forum_id/suggestions/suggestion_id/respond.format
                     */
                    switch($current_state) {
                      case 'unscheduled':
                        $response_status = 'under review';
                        break;
                      case 'unstarted':
                        $response_status = 'planned';
                        break;
                      case 'started':
                        $response_status = 'started';
                        break;
                      case 'delivered':
                        $response_status = 'started';
                        break;
                      case 'accepted':
                        $response_status = 'completed';
                        break;
                      default:
                        $response_status = false;
                        break;
                    }

                    if($response_status !== false) {
                      $access_token = $oauth->getAccessToken();
                      log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  access_token = " . print_r($access_token, true));

                      $api_url = $oauth->signTrustedUrl(USERVOICE_API_URL . "/forums/{$uv['type_id']}/suggestions/{$uv['id']}/respond.json", $access_token, 'PUT');
                      log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  api_url = " . print_r($api_url, true));
                      $params = array(
                        '_method' => 'put',
                        'response[status]' => $response_status,
                      );
                    }
                  }
                }
                else if($uv['type'] == 't') {
                  // Tickets
                  if(defined('USERVOICE_UPDATE_TICKETS') && USERVOICE_UPDATE_TICKETS != 'none') {
                    /*
                     * update PUT /api/v1/tickets/ticket_id.format
                     */
                    switch($current_state) {
                      case 'unscheduled':
                        $update_state = 'open';
                        break;
                      case 'unstarted':
                        $update_state = 'open';
                        break;
                      case 'started':
                        $update_state = 'open';
                        break;
                      case 'delivered':
                        $update_state = 'open';
                        break;
                      case 'accepted':
                        $update_state = 'closed';
                        break;
                      default:
                        $update_state = false;
                        break;
                    }

                    if($update_state !== false) {
                      $access_token = $oauth->getAccessToken();
                      log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  access_token = " . print_r($access_token, true));

                      $api_url = $oauth->signTrustedUrl(USERVOICE_API_URL . "/tickets/{$uv['id']}.json", $access_token, 'PUT');
                      log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  api_url = " . print_r($api_url, true));
                      $params = array(
                        '_method' => 'put',
                        'ticket[state]' => $update_state,
                      );
                    }
                  }
                }
                break;
              case 'story_delete':
                if($uv['type'] == 's') {
                  // Suggestions
                  if(defined('USERVOICE_UPDATE_FORUMS') && USERVOICE_UPDATE_FORUMS != 'none') {
                    /*
                     * respond  PUT /api/v1/forums/forum_id/suggestions/suggestion_id/respond.format
                     */

                    $access_token = $oauth->getAccessToken();
                    log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  access_token = " . print_r($access_token, true));

                    $api_url = $oauth->signTrustedUrl(USERVOICE_API_URL . "/forums/{$uv['type_id']}/suggestions/{$uv['id']}/respond.json", $access_token, 'PUT');
                    log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  api_url = " . print_r($api_url, true));
                    $params = array(
                      '_method' => 'put',
                      'response[status]' => '',
                    );
                  }
                }
                else if($uv['type'] == 't') {
                  // Tickets
                  if(defined('USERVOICE_UPDATE_TICKETS') && USERVOICE_UPDATE_TICKETS != 'none') {
                    // Back to open if deleted? No...
                  }
                }
                break;
              default:
                // Unsupported event type
                log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  xml->event_type = " . print_r($xml->event_type, true));
                break;
            }

            if($api_url && $params) {
              $http = new UserVoiceHTTP();
              if($http) {
                $reply = $http->postUrl($api_url, $params);
                log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  reply = " . print_r($reply, true));
              }
              unset($http);
            }
            
          }
          unset($oauth);
        }

      }
    }
    else {
      // No event type ?!
      log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  xml = " . print_r($xml, true));
    }
  }

?>
