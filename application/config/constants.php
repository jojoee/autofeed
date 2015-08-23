<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',                            'rb');
define('FOPEN_READ_WRITE',                      'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',        'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',   'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',                    'ab');
define('FOPEN_READ_WRITE_CREATE',               'a+b');
define('FOPEN_WRITE_CREATE_STRICT',             'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',        'x+b');

/*
|--------------------------------------------------------------------------
| Custom
|--------------------------------------------------------------------------
*/

define('GITHUB_URL'               , 'GITHUB_URL');
define('FACEBOOK_URL'             , 'FACEBOOK_URL');
define('TWITTER_URL'              , 'TWITTER_URL');

define('GOOGLE_MAP_KEY'           , 'GOOGLE_MAP_KEY');
define('GA_CODE'                  , 'UA-XXXXXXXX-X');

define('FB_APP_ID'                , 'FB_APP_ID');
define('FB_APP_SECRET'            , 'FB_APP_SECRET');
define('FB_USER_ID'               , 'FB_USER_ID');
define('FB_USER_ACCESS_TOKEN'     , 'FB_USER_ACCESS_TOKEN');
define('FB_PAGE_ID'               , 'FB_PAGE_ID');
define('FB_PAGE_ACCESS_TOEKN'     , 'FB_PAGE_ACCESS_TOEKN');

/* End of file constants.php */
/* Location: ./application/config/constants.php */