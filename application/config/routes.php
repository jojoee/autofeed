<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

/*
| -------------------------------------------------------------------------
| Welcome
| -------------------------------------------------------------------------
*/

$route['default_controller'] = 'welcome';
$route['quote'] = 'welcome/quote';

/*
| -------------------------------------------------------------------------
| News
| -------------------------------------------------------------------------
*/

// $route['nn'] = 'news';
$route['nn/link/(:any)'] = 'news/update_link/$1';
$route['nn/seelink/(:any)'] = 'news/see_link/$1';
// $route['nn/view/(:any)'] = 'news/update_view/$1';
// $route['nn/seeview/(:any)'] = 'news/see_link/$1';

$route['nn/post'] = 'news/post_news';
$route['nn/alllink'] = 'news/update_all_links';
$route['nn/stop'] = 'news/stop';
$route['nn/start'] = 'news/start';

$route['nn/fbullss'] = 'news/facebook_user_long_lived_session';
$route['nn/fbpllss'] = 'news/facebook_page_long_lived_session';

$route['nn/test'] = 'news/test';
$route['nn/reset'] = 'news/reset';

/*
| -------------------------------------------------------------------------
| CI
| -------------------------------------------------------------------------
*/

$route['404_override'] = 'welcome/error_404';
// $route['404_override'] = '';

/* End of file routes.php */
/* Location: ./application/config/routes.php */