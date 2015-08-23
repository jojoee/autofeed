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

$route['default_controller'] = 'welcome';
$route['quote'] = 'welcome/quote';

// $route['nn/link'] = 'news/update_all_news_links';
// $route['nn/link/(:any)'] = 'news/update_news_link/$1';

// $route['nn/pantiplink'] = 'news/update_all_pantip_links';
// $route['nn/pantiplink/(:any)'] = 'news/update_pantip_link/$1';

// $route['nn/edulink'] = 'news/update_all_edu_links';
// $route['nn/edulink/(:any)'] = 'news/update_edu_link/$1';

// $route['nn/jojoeelink'] = 'news/update_all_jojoee_links';
// $route['nn/jojoeelink/(:any)'] = 'news/update_jojoee_link/$1';

// $route['nn/youvlink'] = 'news/update_all_youv_links';
// $route['nn/youvlink/(:any)'] = 'news/update_youv_link/$1';

// $route['nn/post/news'] = 'news/post/news';
// $route['nn/pantippost'] = 'news/post/pantip';

$route['nn/link/(:any)'] = 'news/update_site/$1';
$route['nn/all/(:any)'] = 'news/update_all_sites/$1';
$route['nn/post/(:any)'] = 'news/post/$1';

$route['nn/log/(:any)'] = 'news/log/$1';

$route['nn/seelink/(:any)'] = 'news/see_link/$1';

$route['nn/cron'] = 'news/cron';
$route['nn/stop'] = 'news/stop';
$route['nn/start'] = 'news/start';
$route['nn/clean'] = 'news/clean';

// $route['nn/fbullss'] = 'news/facebook_user_long_lived_session';
// $route['nn/fbpllss'] = 'news/facebook_page_long_lived_session';

$route['nn/test'] = 'news/test';
$route['nn/reset'] = 'news/reset';

$route['404_override'] = 'welcome/error_404';

/* End of file routes.php */
/* Location: ./application/config/routes.php */