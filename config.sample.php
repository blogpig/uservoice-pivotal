<?php

  // UserVoice info - fill in your details...
  define('USERVOICE_SUBDOMAIN', 'yoursubdomain');
  define('USERVOICE_KEY', '');
  define('USERVOICE_SECRET', '');
  define('USERVOICE_ADMIN_EMAIL', '');
  define('USERVOICE_ADMIN_PASSWORD', '');
  define('USERVOICE_SITE_URL', 'http://yoursubdomain.uservoice.com');

  // Integration import config - set what to import...
  define('USERVOICE_IMPORT_FORUMS', 'all'); // 'none', 'all' or a list of forum IDs, e.g. '123,456'
  define('USERVOICE_IMPORT_TICKETS', 'all'); // 'none', 'all' or a list of custom fields, e.g. 'Type=Bug'

  // Integration update config - set what to update...
  define('USERVOICE_UPDATE_FORUMS', 'all'); // 'none' or 'all'
  define('USERVOICE_UPDATE_TICKETS', 'all'); // 'none' or 'all'


  // UserVoice API & OAuth info
  define('USERVOICE_API_URL', 'https://' . USERVOICE_SUBDOMAIN . '.uservoice.com/api/v1');
  define('USERVOICE_REQUEST_TOKEN_URL', USERVOICE_API_URL . '/oauth/request_token.json');
  define('USERVOICE_ACCESS_TOKEN_URL', USERVOICE_API_URL . '/oauth/access_token.json');
  define('USERVOICE_AUTHORIZE_URL', USERVOICE_API_URL . '/oauth/authorize.json');

  // Paths and files
  define('LOCAL_PATH', realpath(dirname(__FILE__)));
  define('LOGS_PATH', LOCAL_PATH . '/logs');
  define('ERROR_LOG_FILE', LOGS_PATH . '/error.log');
  define('IMPORT_LOG_FILE', LOGS_PATH . '/import.log');
  define('HOOK_LOG_FILE', LOGS_PATH . '/hook.log');

  // What to write to log files?
  define('LOG_ERRORS', true);
  define('LOG_IMPORTS', false);
  define('LOG_HOOKS', false);

?>
