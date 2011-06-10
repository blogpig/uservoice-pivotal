<?php

  class UserVoiceHTTP {
    var $ch = false;
    var $user_agent = 'UserVoice Pivotal/1.0';
    var $cookies = false;


    // Constructor

    function UserVoiceHTTP($user_agent = '', $header = false, $follow = false, $debug = false) {
      if($user_agent) {
        $this->user_agent = $user_agent;
      }

      if(function_exists('curl_init')) {
        $this->cookies = tempnam('/tmp', 'uservoice');
        register_shutdown_function(array($this,'UserVoiceHTTPShutdown'));

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADER, $header);
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookies);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookies);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $follow);
        # DEBUG
        curl_setopt($this->ch, CURLINFO_HEADER_OUT, $debug);
      }
    }


    // Destructor

    function UserVoiceHTTPShutdown() {
      if($this->ch && function_exists('curl_close')) {
        curl_close($this->ch);
      }
      unset($this->ch);
      @unlink($this->cookies);
    }


    // Basic HTTP operations (GET, POST...)

    function getUrl($url, $username = '', $password = '', $follow = false, $timeout = false) {
      $result = false;

      $url = trim($url);
      if($url) {
        $result = "";

        if($this->ch) {
          if($timeout) {
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
          }
          curl_setopt($this->ch, CURLOPT_URL, $url);
          if($username || $password) {
            curl_setopt($this->ch, CURLOPT_USERPWD, "{$username}:{$password}");
          }
          curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $follow);
          $result = curl_exec($this->ch);
        }
        else {
          $result = @file_get_contents($url);
        }
      }

      return $result;
    }

    function postUrl($url, $params, $username = '', $password = '', $follow = false) {
      $result = false;

      if($url) {
        $result = "";

        if($this->ch) {
          curl_setopt($this->ch, CURLOPT_URL, $url);
          curl_setopt($this->ch, CURLOPT_POST, true);
          curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
          curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, $follow);
          $result = curl_exec($this->ch);
        }
      }

      return $result;
    }

  }

?>
