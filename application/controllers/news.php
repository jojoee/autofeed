<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;
use Facebook\FacebookRedirectLoginHelper;

class News extends CI_Controller {

	private $sites;
	private $post_limit;
	private $found_link;
	private $url_limit;
	private $page_type;

	private $app_id;
	private $app_secret;
	
	private $user_id;
	private $user_access_token;

	private $page_id;
	private $page_access_token;

	private $user_session;
	private $page_session;

	public function __construct() {
		
		parent::__construct();
		$this->load->model('News_model', 'news_model');
		$this->load->model('FB_model', 'fb_model');
		$this->found_link = 0;
		$this->post_limit = 8;
		$this->page_type = 'news';

		$this->set_sites();
	}

	/**
	 * Set all site name
	 * TESTED
	 */
	private function set_sites($type = '')
	{
		$sites = $this->news_model->get_sites($type);
		$results = array();

		foreach ($sites as $site) $results[$site['name']] = $site;

		$this->sites = $results;
	}

	/**
	 * Get `site` by name id
	 * 
	 * @param  [type] $name_id [description]
	 * @return [type]          [description]
	 */
	private function get_site_by_name_id($name_id)
	{
		$results = array();

		foreach ($this->sites as $site)
		{
			if ($site['id'] == $name_id)
			{
				$results = $site;
				break;
			}
		}

		return $results;
	}

	/**
	 * Setup Facebook application
	 */
	private function set_facebook_app()
	{
		require_once('vendor/facebook-php-sdk/autoload.php');

		$this->app_id = $this->fb_model->get_facebook_app_id();
		$this->app_secret = $this->fb_model->get_facebook_app_secret();

		$this->user_id = $this->fb_model->get_facebook_user_id();
		$this->user_access_token = $this->fb_model->get_facebook_user_access_token();

		if ($this->page_type == 'pantip')
		{
			$this->page_id = $this->fb_model->get_facebook_pandrift_page_id();
			$this->page_access_token = $this->fb_model->get_facebook_pandrift_page_access_token();	
		}
		else if ($this->page_type == 'news')
		{
			$this->page_id = $this->fb_model->get_facebook_drama_page_id();
			$this->page_access_token = $this->fb_model->get_facebook_drama_page_access_token();	
		}
		else
		{
			$this->page_id = $this->fb_model->get_facebook_drama_page_id();
			$this->page_access_token = $this->fb_model->get_facebook_drama_page_access_token();	
		}

		// initialize your app using your key and secret
		FacebookSession::setDefaultApplication($this->app_id , $this->app_secret);
		
		$this->user_session = new FacebookSession($this->user_access_token);
		$this->page_session = new FacebookSession($this->page_access_token);
	}

	/**
	 * Publish the facebook status
	 * 
	 * @param  [type] $post_title [description]
	 * @param  [type] $post_url   [description]
	 * @return [type]             [description]
	 */
	private function publish_facebook($post_title, $post_url)
	{
		$this->set_facebook_app();

		$admin_name = '';
		if ($this->$page_type == 'news') $admin_name = $this->news_model->get_random_admin()[0]['name'];

		$message = sprintf('%s %s - %s',
			html_entity_decode($post_title),
			$post_url,
			$admin_name
		);

		$post_url = remove_trailing_slash($post_url);

		$data = array(
			'access_token'  => $this->page_access_token,
			'message'       => $message
		);

		if ( ! is_url_redirects($post_url))
		{
			$data['link'] = $post_url;
		}
		else if ( ! is_url_redirects($post_url.'/'))
		{
			$data['link'] = $post_url.'/';
		}

		// Make a new request and execute it.
		$action = (new FacebookRequest(
			$this->page_session,
			'POST',
			'/'.$this->page_id.'/feed',
			$data
		))->execute()->getGraphObject()->asArray();

		return $action;
	}

	/**
	 * [is_allowed_time description]
	 * @return boolean [description]
	 */
	private function is_allowed_time()
	{
		// TODO
		// 
		// check the current time such as 10.00 - 24.00
		// 
		
		return true;
	}

	/**
	 * [format_link_data description]
	 * @param  [type] $links [description]
	 * @param  [type] $site  [description]
	 * @return [type]        [description]
	 */
	private function format_link_data($links, $site)
	{
		$items = array();
		$full_domain_name = get_full_domain_name($site['url']);

		$is_full_path = $site['is_full_path'];

		foreach ($links as $link)
		{
			$item = array();
			$anchor = $link;

			if (isset($anchor->plaintext) &&
				isset($anchor->href) &&
				! is_null_or_empty_string($anchor->plaintext) &&
				! is_null_or_empty_string($anchor->href))
			{
				$item['title'] = trim($anchor->plaintext);
				$item['request_uri'] = $this->set_request_uri(trim($anchor->href), $full_domain_name, $is_full_path);
			}

			if (isset($view->plaintext) && ! is_null_or_empty_string($view->plaintext)) $item['view'] = $view->plaintext;
			
			if ( ! empty($item)) $items[] = $item;
		}

		return $items;
	}

	/**
	 * [get_targeted_data description]
	 * 
	 * @param  [type] $link_url     [description]
	 * @param  [type] $link_element [description]
	 * @return [type]               [description]
	 */
	private function get_targeted_data($link_url, $link_element)
	{
		if ( ! is_url_exists($link_url)) return array();

		require_once('vendor/php-simple-html-dom-parser/Src/Sunra/PhpSimple/simplehtmldom_1_5/simple_html_dom.php');

		try
		{
			$html = file_get_html($link_url);
		}
		catch (Exception $ex)
		{
			printf('Caught exception: %s', $ex->getMessage());
			$this->news_model->insert_error_log('get_targeted_data - file_get_html', $e->getMessage());

			return array();
		}

		if ( ! isset($html) && is_null_or_empty_string($html)) return array();

		try
		{
			$links = $html->find($link_element);
		}
		catch (Exception $ex)
		{
			printf('Caught exception: %s', $ex->getMessage());
			$this->news_model->insert_error_log('get_targeted_data - find', $e->getMessage());

			return array();
		}

		return $links;
	}

	/**
	 * Get link data from `site_name`
	 * 
	 * @param  [type] $site_name [description]
	 * @return [type]           [description]
	 */
	private function get_link_data($site_name)
	{
		$items = array();
		$site = $this->sites[$site_name];

		if (is_null_or_empty_string($site['link_url']) || is_null_or_empty_string($site['link_element'])) return array();

		$links = $this->get_targeted_data($site['link_url'], $site['link_element']);
		if (empty($links)) return array();

		$items = $this->format_link_data($links, $site);

		return $items;
	}

	/**
	 * Set the request uri
	 * 
	 * @param [type]  $uri          [description]
	 * @param string  $full_domain_name  [description]
	 * @param integer $is_full_path [description]
	 */
	private function set_request_uri($uri, $full_domain_name = '', $is_full_path = 0)
	{
		$results = $uri;
		if ( ! $is_full_path) $results = get_request_uri($uri, $full_domain_name);
		
		return urldecode($results);
	}

	/*================================================================
		#Public
		================================================================*/

	/**
	 * Get the correctly format of url
	 * 
	 * @param  [type] $url  [description]
	 * @param  [type] $site [description]
	 * @return [type]       [description]
	 */
	public function get_post_url($request_uri, $site)
	{
		$post_url = $request_uri;
		if ( ! $site['is_full_path']) $post_url = remove_trailing_slash($site['url']).$request_uri;

		return $post_url;
	}

	/**
	 * Update facebook page status
	 * 
	 * @return [type] [description]
	 */
	public function post($type)
	{
		// if ( ! is_allowed_time() ) return false;

		$count = 0;
		$this->page_type = $type;

		if ($this->page_type == 'pantip')    $urls = $this->news_model->get_random_pantip($this->post_limit);
		else if ($this->page_type == 'news') $urls = $this->news_model->get_random_news($this->post_limit);
		else                                 $urls = $this->news_model->get_random_news($this->post_limit);

		if (empty($urls)) return false;

		$index = range(0, $this->post_limit - 1);
		shuffle($index);

		while ($count < 8)
		{
			$url = $urls[$index[$count]];
			$site = $this->get_site_by_name_id($url['name_id']);

			$post_title = $url['title'];
			
			// get post url
			$post_url = $this->get_post_url($url['request_uri'], $site);

			if (is_url_exists(urldecode($post_url)))
			{
				// 
				// TODO - Use comment as a title
				// 
				// $comment = array();
				// $comment_element = $site['comment_element'];
				// if ($site['has_comment']) $comment = $this->get_targeted_data($post_url, $comment_element);
				// 

				// Facebook
				try
				{
					$action = $this->publish_facebook($post_title, $post_url);
					$action_id = $action['id'];
					$this->news_model->update_published_link($url['id']);
				}
				catch (FacebookRequestException $ex)
				{
					printf('FacebookRequestException: %s', $ex->getMessage());
					$this->news_model->insert_error_log('post, is_url_exists, FacebookRequestException', $e->getMessage());

					return false;
				}
				catch (\Exception $ex)
				{
					printf('Caught exception: %s', $ex->getMessage());
					$this->news_model->insert_error_log('post, is_url_exists, Exception', $e->getMessage());

					return false;
				}

				break;
			}
			else
			{
				$this->news_model->update_broken_link($url['id']);
			}

			$count++;
		}

		if (isset($action_id) && ! is_null_or_empty_string($action_id))
		{
			$this->news_model->insert_published_log($url['id'], $action_id);
			printf('<code>Published</code>');
		}
		else
		{
			$this->news_model->insert_error_log('post, all url aren\'t exist', $e->getMessage());
			printf('<code>Can\'t publish (all url aren\'t exist)</code>');
		}
	}

	/**
	 * Update the url by `site_name`
	 * 
	 * @param  [type] $site_name [description]
	 * @return [type]            [description]
	 */
	public function update_link($site_name)
	{
		if ( ! isset($this->sites[$site_name])) return false;

		$started_time = microtime(true);
		$items = $this->get_link_data($site_name, $this->page_type);
		
		if (count($items) == 0)
		{
			$this->news_model->insert_error_log('update_link', 'site down or 0 link found from '.$site_name);
		}
		else
		{
			$this->news_model->save_link($this->sites[$site_name]['id'], $items);
			$this->found_link += count($items);	
		}

		$time_elapsed_secs = microtime(true) - $started_time;
		$this->news_model->insert_scraped_log($this->sites[$site_name]['id'], $this->found_link);

		printf('<code>%d %s (%f secs)</code><br />',
			count($items),
			$site_name.' links have been scraped',
			$time_elapsed_secs
		);
	}

	/**
	 * [update_news_link description]
	 * @param  [type] $site_name [description]
	 * @return [type]            [description]
	 */
	public function update_news_link($site_name)
	{
		$this->page_type = 'news';
		$this->set_sites($this->page_type);
		$this->update_link($site_name);
	}

	/**
	 * [update_pantip_link description]
	 * @param  [type] $site_name [description]
	 * @return [type]            [description]
	 */
	public function update_pantip_link($site_name)
	{
		$this->page_type = 'pantip';
		$this->set_sites($this->page_type);
		$this->update_link($site_name);
	}

	/**
	 * Update all url
	 * 
	 * @return [type] [description]
	 */
	public function update_all_news_links()
	{
		$this->page_type = 'news';
		$this->set_sites($this->page_type);
		$this->update_all_links();
	}

	/**
	 * [update_all_links description]
	 * @return [type] [description]
	 */
	public function update_all_links()
	{
		ini_set('max_execution_time', 300);
		$started_time = microtime(true);

		foreach ($this->sites as $site) $this->update_link($site['name']);
		
		$time_elapsed_secs = microtime(true) - $started_time;
		printf('<br /><code>%d links found, Total execution time %f<br />', $this->found_link, $time_elapsed_secs);
	}

	/**
	 * [update_pantip_links description]
	 * @return [type] [description]
	 */
	public function update_all_pantip_links()
	{
		$this->page_type = 'pantip';
		$this->set_sites($this->page_type);
		$this->update_all_links();
	}

	/**
	 * See the scraped link by `site_name`
	 * 
	 * @param  [type] $site_name [description]
	 * @return [type]            [description]
	 */
	public function see_link($site_name)
	{
		header('Content-Type: text/html; charset=utf-8');
		$data = $this->get_link_data($site_name);

		dd($data);
	}

	/**
	 * Update the view of each url
	 * 
	 * @param  [type] $site [description]
	 * @return [type]       [description]
	 */
	public function update_view($site)
	{
		// 
		// TODO
		// 
		// update the view, fb_like, fb_share
		// 
		
		printf('<code>%s</code>', $site_name.' views have been scraped');
	}

	/**
	 * [clean_data description]
	 * 
	 * @return [type] [description]
	 */
	public function clean()
	{
		$this->news_model->remove_old_record();
		printf('<code>%s</code>', 'Cleaned');
	}

	/*================================================================
		#Debug
		================================================================*/

	/**
	 * Remove all data from url table
	 */
	public function reset()
	{
		$this->news_model->truncate_url_table();
	}

	/**
	 * [stop description]
	 * @return [type] [description]
	 */
	public function stop()
	{
		$this->news_model->stop_news();
		printf('<code>%s</code>', 'Stopped');
	}

	/**
	 * [start description]
	 * @return [type] [description]
	 */
	public function start()
	{
		$this->news_model->start_news();
		printf('<code>%s</code>', 'Started');
	}

	/**
	 * [facebook_user_long_lived_session description]
	 * @return [type] [description]
	 */
	public function facebook_user_long_lived_session()
	{
		$this->set_facebook_app();
		$user_session = $this->user_session->getLongLivedSession();

		dd($user_session);
	}

	/**
	 * [facebook_page_long_lived_session description]
	 * @return [type] [description]
	 */
	public function facebook_page_long_lived_session()
	{
		$this->set_facebook_app();
		$page_ids = $this->fb_model->get_all_page_ids();
		$results = array();

		foreach ($page_ids as $page_id)
		{
			$page_id = $page_id['value'];

			if ( ! is_null_or_empty_string($page_id))
			{
				if (is_null_or_empty_string($page_id)) $page_id = $this->page_id;

				$request = new FacebookRequest($this->user_session, 'GET', '/'.$page_id.'?fields=access_token');
				$request_results = $request->execute()->getGraphObject()->asArray();

				$page_access_token = $request_results['access_token'];
				$page_session = new FacebookSession($page_access_token);

				$results[] = array(
					'info'       => $page_session->getSessionInfo(),
					'longlive'   => $page_session->getLongLivedSession()
				);
			}
		}

		dd($results);
	}

	/*================================================================
		#Test
		================================================================*/

	/**
	 * [test description]
	 * @return [type] [description]
	 */
	public function test()
	{
		$this->load->library('unit_test');

		$this->test_utility_helper();
		$this->test_news();

		$results = $this->unit->result();
		foreach ($results as $result) if ($result['Result'] == 'Failed') da($result);

		echo $this->unit->report();
	}

	/**
	 * [test_utility_helper description]
	 * @return [type] [description]
	 */
	private function test_utility_helper()
	{
		$this->unit->use_strict(true);

		$this->unit->run(get_github_url(), 'is_string', 'get_github_url()');
		$this->unit->run(get_facebook_url(), 'is_string', 'get_facebook_url()');
		$this->unit->run(get_twitter_url(), 'is_string', 'get_twitter_url()');
		$this->unit->run(get_google_map_key(), 'is_string', 'get_google_map_key()');
		$this->unit->run(get_ga_code(), 'is_string', 'get_ga_code()');

		$this->unit->run(is_null_or_empty_string(''), 'is_true', 'is_null_or_empty_string()');
		$this->unit->run(is_null_or_empty_string('test'), 'is_false', 'is_null_or_empty_string()');
		$this->unit->run(is_null_or_empty_string(1), 'is_false', 'is_null_or_empty_string()');

		$this->unit->run(start_with('abcdef', 'ab'), 'is_true', 'start_with()');
		$this->unit->run(start_with('abcdef', 'cd'), 'is_false', 'start_with()');
		$this->unit->run(start_with('abcdef', 'ef'), 'is_false', 'start_with()');
		$this->unit->run(start_with('abcdef', ''), 'is_true', 'start_with()');
		$this->unit->run(start_with('', 'abcdef'), 'is_false', 'start_with()');

		$this->unit->run(end_with("abcdef", "ab"), 'is_false', 'end_with()');
		$this->unit->run(end_with("abcdef", "cd"), 'is_false', 'end_with()');
		$this->unit->run(end_with("abcdef", "ef"), 'is_true', 'end_with()');
		$this->unit->run(end_with("abcdef", ""), 'is_true', 'end_with()');
		$this->unit->run(end_with("", "abcdef"), 'is_false', 'end_with()');

		$this->unit->run(get_domain_name('http://somedomain.co.uk'), 'somedomain.co.uk', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://www2.manager.co.th'), 'manager.co.th', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://test.manager.co.th'), 'manager.co.th', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://manager.co.th'), 'manager.co.th', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://thaiware.com'), 'thaiware.com', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://www.thaiware.com'), 'thaiware.com', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://test.thaiware.com'), 'thaiware.com', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://www.studentloan.ktb.co.th/'), 'ktb.co.th', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://www.studentloan.ktb.co.th/dasdasdasd.html'), 'ktb.co.th', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://www.studentloan.ktb.co.th?quewadsas=2faddasdas'), 'ktb.co.th', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://www.studentloan.ktb.co.th/2011/20/01?=asdasdasdasd'), 'ktb.co.th', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://pantip.com/forum/siam'), 'pantip.com', 'get_domain_name()');
		$this->unit->run(get_domain_name('http://www.wegointer.com/category/variety/'), 'wegointer.com', 'get_domain_name()');
		$this->unit->run(get_domain_name(), 'lab.jojoee.com', 'get_domain_name()');

		$this->unit->run(get_full_domain_name('http://www.wegointer.com/category/variety/'), 'www.wegointer.com', 'get_domain_name()');
		$this->unit->run(get_full_domain_name('http://somedomain.co.uk'), 'somedomain.co.uk', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://www2.manager.co.th'), 'www2.manager.co.th', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://test.manager.co.th'), 'test.manager.co.th', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://manager.co.th'), 'manager.co.th', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://thaiware.com'), 'thaiware.com', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://www.thaiware.com'), 'www.thaiware.com', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://test.thaiware.com'), 'test.thaiware.com', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://www.studentloan.ktb.co.th/'), 'www.studentloan.ktb.co.th', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://www.studentloan.ktb.co.th/dasdasdasd.html'), 'www.studentloan.ktb.co.th', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://www.studentloan.ktb.co.th?quewadsas=2faddasdas'), 'www.studentloan.ktb.co.th', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://www.studentloan.ktb.co.th/2011/20/01?=asdasdasdasd'), 'www.studentloan.ktb.co.th', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://pantip.com/forum/siam'), 'pantip.com', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name('http://www.wegointer.com/category/variety/'), 'www.wegointer.com', 'get_full_domain_name()');
		$this->unit->run(get_full_domain_name(), 'lab.jojoee.com', 'get_full_domain_name()');

		$url = 'http://sub.wegointer.com/category/variety/';
		$this->unit->run(get_request_url($url, get_full_domain_name($url)), '/category/variety', 'get_request_url()');
		$url = 'http://www.wegointer.com/category/variety/';
		$this->unit->run(get_request_url($url, get_full_domain_name($url)), '/category/variety', 'get_request_url()');

		$this->unit->run(get_full_url(), 'http://lab.jojoee.com/nn/test', 'get_full_url()');

		// 404, 301
		// $this->unit->run(is_url_exists('http://jojoee.com/404'), 'is_true', 'is_url_exists()');
		// $this->unit->run(is_url_exists('http://fashion.spokedark.tv/2015/04/24/dichan-magazine/'), 'is_true', 'is_url_exists()');
		// $this->unit->run(is_url_exists('http://www.jojoee.com/'), 'is_true', 'is_url_exists()');
		// $this->unit->run(is_url_exists('http://test4041.com/'), 'is_false', 'is_url_exists()');
		// $this->unit->run(is_url_exists('http://test4041.com/'), 'is_false', 'is_url_exists()');
		// $this->unit->run(is_url_redirects('http://www.jojoee.com/'), 'is_true', 'is_url_exists()');
		// $this->unit->run(is_404('http://jojoee.com/404'), 'is_true', 'is_404()');

		// $url = 'http://fashion.spokedark.tv/?p=6600';
		// $this->unit->run(is_url_exists($url), 'is_true', 'is_url_exists()');
		// $this->unit->run(is_url_redirects($url), 'is_true', 'is_url_redirects()');

		// don't know why it doesn't work
		// $url = 'http://movies.spokedark.tv?p=10054/';
		// $this->unit->run(is_url_exists($url), 'is_true', 'is_url_exists()');
		// $this->unit->run(is_url_redirects($url), 'is_true', 'is_url_redirects()');
		
		$this->unit->run(get_extension('file.jpeg'), 'jpeg', 'get_extension()');
		$this->unit->run(get_extension('file.bk.zip'), 'zip', 'get_extension()');

		$this->unit->run(remove_trailing_slash('/category/product/'), '/category/product', 'remove_trailing_slash()');
		$this->unit->run(remove_trailing_slash('/category/product'), '/category/product', 'remove_trailing_slash()');
		$this->unit->run(remove_trailing_slash('category/product/'), 'category/product', 'remove_trailing_slash()');
	}

	/**
	 * [test_news description]
	 * @return [type] [description]
	 */
	private function test_news() {

		$sites = $this->sites;
		$this->unit->run($this->sites, 'is_array', '$this->sites');

		foreach ($sites as $site) $this->unit->run($site, 'is_array', '$sites');
		foreach ($sites as $site) $this->unit->run($this->get_site_by_name_id($site['id']), 'is_array', 'get_site_by_name_id()');
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */