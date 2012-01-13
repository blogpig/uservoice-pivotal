<?php

  include_once('config.php');

  function log_event($filename, $event, $timestamp = true) {
    $result = false;

    if($filename && $event) {
      $result = file_put_contents($filename, ($timestamp ? date('Y-m-d H:i:s ') : '') . $event . "\n", FILE_APPEND);
    }

    return $result;
  }

  function log_import_event($event, $timestamp = true) {
    $result = false;
    if(defined('LOG_IMPORTS') && LOG_IMPORTS) {
      $result = log_event(IMPORT_LOG_FILE, $event, $timestamp);
    }
    return $result;
  }

  function log_hook_event($event, $timestamp = true) {
    $result = false;
    if(defined('LOG_HOOKS') && LOG_HOOKS) {
      $result = log_event(HOOK_LOG_FILE, $event, $timestamp);
    }
    return $result;
  }

  function log_stats_event($event, $timestamp = true) {
    $result = false;
    if(defined('LOG_STATS') && LOG_STATS) {
      $result = log_event(STATS_LOG_FILE, $event, $timestamp);
    }
    return $result;
  }

  function log_error_event($event, $timestamp = true) {
    $result = false;
    if(defined('LOG_ERRORS') && LOG_ERRORS) {
      $result = log_event(ERROR_LOG_FILE, $event, $timestamp);
    }
    return $result;
  }


  function xml_utf8_encode($data) {
    $result = $data;

    $result = htmlspecialchars($result);
    $result = fix_latin($result);

    return $result;
  }

  function array_to_xml(array $arr, SimpleXMLElement $xml) {
    foreach ($arr as $k => $v) {
      is_array($v) ? array_to_xml($v, $xml->addChild($k)) : $xml->addChild($k, $v);
    }
    return $xml;
  }

  function apply_url_params($needle, $url_params) {
    $result = $needle;

    if($url_params) {
      foreach($url_params as $name => $value) {
        $result = str_replace('%' . $name . '%', $value, $result);
      }
    }

    return $result;
  }

  function match_custom_fields($needle, $haystack, $match_all = false) {
    $result = false;

    if($needle && $haystack) {
      $needles = explode(',', strtolower($needle));

      $keys_and_values = array();
      foreach($haystack as $field) {
        $keys_and_values[] = trim(strtolower($field->key . '=' . $field->value));
      }

      $found = $match_all;
      foreach($needles as $ndl) {
        $ndl = strtolower($ndl);
        $found = ($match_all ? $found : true) && in_array($ndl, $keys_and_values);
        if((!$match_all && $found) || ($match_all && !$found)) {
          break;
        }
      }

      $result = $found;
    }

    return $result;
  }


  /* BEGIN:: from http://php.net/manual/en/function.utf8-encode.php by squeegee */

  function init_byte_map(){
    global $byte_map;
    for($x=128;$x<256;++$x){
      $byte_map[chr($x)]=utf8_encode(chr($x));
    }
    $cp1252_map=array(
      "\x80"=>"\xE2\x82\xAC",    // EURO SIGN
      "\x82" => "\xE2\x80\x9A",  // SINGLE LOW-9 QUOTATION MARK
      "\x83" => "\xC6\x92",      // LATIN SMALL LETTER F WITH HOOK
      "\x84" => "\xE2\x80\x9E",  // DOUBLE LOW-9 QUOTATION MARK
      "\x85" => "\xE2\x80\xA6",  // HORIZONTAL ELLIPSIS
      "\x86" => "\xE2\x80\xA0",  // DAGGER
      "\x87" => "\xE2\x80\xA1",  // DOUBLE DAGGER
      "\x88" => "\xCB\x86",      // MODIFIER LETTER CIRCUMFLEX ACCENT
      "\x89" => "\xE2\x80\xB0",  // PER MILLE SIGN
      "\x8A" => "\xC5\xA0",      // LATIN CAPITAL LETTER S WITH CARON
      "\x8B" => "\xE2\x80\xB9",  // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
      "\x8C" => "\xC5\x92",      // LATIN CAPITAL LIGATURE OE
      "\x8E" => "\xC5\xBD",      // LATIN CAPITAL LETTER Z WITH CARON
      "\x91" => "\xE2\x80\x98",  // LEFT SINGLE QUOTATION MARK
      "\x92" => "\xE2\x80\x99",  // RIGHT SINGLE QUOTATION MARK
      "\x93" => "\xE2\x80\x9C",  // LEFT DOUBLE QUOTATION MARK
      "\x94" => "\xE2\x80\x9D",  // RIGHT DOUBLE QUOTATION MARK
      "\x95" => "\xE2\x80\xA2",  // BULLET
      "\x96" => "\xE2\x80\x93",  // EN DASH
      "\x97" => "\xE2\x80\x94",  // EM DASH
      "\x98" => "\xCB\x9C",      // SMALL TILDE
      "\x99" => "\xE2\x84\xA2",  // TRADE MARK SIGN
      "\x9A" => "\xC5\xA1",      // LATIN SMALL LETTER S WITH CARON
      "\x9B" => "\xE2\x80\xBA",  // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
      "\x9C" => "\xC5\x93",      // LATIN SMALL LIGATURE OE
      "\x9E" => "\xC5\xBE",      // LATIN SMALL LETTER Z WITH CARON
      "\x9F" => "\xC5\xB8"       // LATIN CAPITAL LETTER Y WITH DIAERESIS
    );
    foreach($cp1252_map as $k=>$v){
      $byte_map[$k]=$v;
    }
  }

  function fix_latin($instr){
    if(function_exists('mb_check_encoding') && mb_check_encoding($instr,'UTF-8'))return $instr; // no need for the rest if it's all valid UTF-8 already
    global $nibble_good_chars,$byte_map;
    $outstr='';
    $char='';
    $rest='';
    while((strlen($instr))>0){
      if(1==preg_match($nibble_good_chars,$instr,$match)){
        $char=$match[1];
        $rest=$match[2];
        $outstr.=$char;
      }elseif(1==preg_match('@^(.)(.*)$@s',$instr,$match)){
        $char=$match[1];
        $rest=$match[2];
        $outstr.=$byte_map[$char];
      }
      $instr=$rest;
    }
    return $outstr;
  }

  $byte_map=array();
  init_byte_map();
  $ascii_char='[\x00-\x7F]';
  $cont_byte='[\x80-\xBF]';
  $utf8_2='[\xC0-\xDF]'.$cont_byte;
  $utf8_3='[\xE0-\xEF]'.$cont_byte.'{2}';
  $utf8_4='[\xF0-\xF7]'.$cont_byte.'{3}';
  $utf8_5='[\xF8-\xFB]'.$cont_byte.'{4}';
  $nibble_good_chars = "@^($ascii_char+|$utf8_2|$utf8_3|$utf8_4|$utf8_5)(.*)$@s";

  /* END:: from http://php.net/manual/en/function.utf8-encode.php by squeegee */

?>
