<?php

  include_once("config.php");
  include_once(LOCAL_PATH . '/oauth/OAuth.php');
  include_once(LOCAL_PATH . '/common.php');

  class UserVoiceOAuth {

    function UserVoiceOAuth() {
      // Any init code?
    }

    function signUrl($url, $token = NULL, $method = 'GET', $key = false, $secret = false) {
      $result = false;

      if($url) {
        $parsed = parse_url($url);
        $params = array();
        if(isset($parsed['query'])) {
          parse_str($parsed['query'], $params);
        }

        $consumer = new OAuthConsumer($key, $secret, NULL);
        $req = OAuthRequest::from_consumer_and_token($consumer, $token, $method, $url, $params);
        $sig_method = new OAuthSignatureMethod_HMAC_SHA1();
        $req->sign_request($sig_method, $consumer, $token);

        $result = $req->to_url();
      }

      return $result;
    }

    function signTrustedUrl($url, $token = NULL, $method = 'GET') {
      return $this->signUrl($url, $token, $method, USERVOICE_KEY, USERVOICE_SECRET);
    }

    function signAdminUrl($url, $token = NULL, $method = 'GET') {
      return $this->signUrl($url, $token, $method, USERVOICE_KEY, USERVOICE_SECRET);
    }

    function getAccessToken() {
      $result = NULL;

      //https://blogpig.uservoice.com/api/v1/oauth/request_token
      $req_token = NULL;
      $request_url = $this->signTrustedUrl(USERVOICE_REQUEST_TOKEN_URL);
      log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  request_url = " . print_r($request_url, true));
      $http = new UserVoiceHTTP();
      if($http) {
        $reply = $http->getUrl($request_url);
        log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  reply = " . print_r($reply, true));
        $req_tokens = @json_decode($reply);
        log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  req_tokens = " . print_r($req_tokens, true));
      }

      if(isset($req_tokens->token->oauth_token) && $req_tokens->token->oauth_token) {
        //$req_token = new OAuthToken($req_tokens->token->oauth_token, $req_tokens->token->oauth_token_secret);
        // https://blogpig.uservoice.com/api/v1/oauth/authorize?request_token=mciP4L2qxWTDFHD7JNB6w
        $access_url = $this->signTrustedUrl(USERVOICE_AUTHORIZE_URL . "?request_token=" . $req_tokens->token->oauth_token . "&email=" . USERVOICE_ADMIN_EMAIL . "&password=" . USERVOICE_ADMIN_PASSWORD); //, $req_token);
        log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  access_url = " . print_r($access_url, true));
        if($http) {
          $reply = $http->getUrl($access_url);
          log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  reply = " . print_r($reply, true));
          $acc_tokens = @json_decode($reply);
          log_hook_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  acc_tokens = " . print_r($acc_tokens, true));

          if($acc_tokens && isset($acc_tokens->token->oauth_token) && isset($acc_tokens->token->oauth_token_secret)) {
            $result = new OAuthToken($acc_tokens->token->oauth_token, $acc_tokens->token->oauth_token_secret);
          }
        }
      }
      else {
        log_error_event(__FILE__ . ' @ ' . __LINE__ . " ::\n  missing/invalid request token");
      }
      unset($http);

      return $result;
    }

  }

?>
