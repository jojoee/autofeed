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
	 * @param  [number] $site_id
	 * @return [array]
	 */
	private function get_site_by_site_id($site_id)
	{
		$results = array();

		foreach ($this->sites as $site)
		{
			if ($site['id'] == $site_id)
			{
				$results = $site;
				break;
			}
		}

		return $results;
	}

	private function set_facebook_page()
	{
		$this->page_id = $this->fb_model->get_facebook_page_id($this->page_type);
		$this->page_access_token = $this->fb_model->get_facebook_page_access_token($this->page_type);
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

		$this->set_facebook_page();

		// initialize your app using your key and secret
		FacebookSession::setDefaultApplication($this->app_id , $this->app_secret);
		
		$this->user_session = new FacebookSession($this->user_access_token);
		$this->page_session = new FacebookSession($this->page_access_token);
	}

	/**
	 * Publish the facebook status
	 * 
	 * @param  [string] $post_title
	 * @param  [string] $post_url
	 * @return [array]  facebook action array
	 */
	private function publish_facebook($post_title, $post_url)
	{
		$this->set_facebook_app();

		$admin_name = '';
		if ($this->page_type == 'news') $admin_name = '- '.$this->news_model->get_random_admin()[0]['name'];

		$message = sprintf('%s %s %s',
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

	private function is_allowed_time()
	{
		// TODO
		// 
		// check the current time such as 10.00 - 24.00
		// 
		
		return true;
	}

	/**
	 * Format all links
	 * 
	 * @param  [array] $links all link data
	 * @param  [array] $site current site info
	 * @return [type]  formatted data
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
	 * Get selected element
	 * 
	 * @param  [string] $link_url     url
	 * @param  [string] $link_element target element
	 * @return [array]  array of selected element
	 */
	private function get_targeted_data($link_url, $link_element)
	{
		// 
		// TODO
		// don't know why it doesn't work
		// e.g. http://lab.jojoee.com/nn/link/eduzonesstudy
		// 
		// if ( ! is_url_exists($link_url)) return array();
		// 

		require_once('vendor/php-simple-html-dom-parser/Src/Sunra/PhpSimple/simplehtmldom_1_5/simple_html_dom.php');

		try
		{
			$html = file_get_html($link_url);
		}
		catch (Exception $ex)
		{
			printf('Caught exception: %s', $ex->getMessage());
			$this->news_model->insert_error_log('get_targeted_data', 'file_get_html : '.$e->getMessage());

			return array();
		}

		if ( ! isset($html) || is_null_or_empty_string($html)) return array();

		try
		{
			$items = $html->find($link_element);
		}
		catch (Exception $ex)
		{
			printf('Caught exception: %s', $ex->getMessage());
			$this->news_model->insert_error_log('get_targeted_data', 'find : '.$e->getMessage());

			return array();
		}

		return $items;
	}

	/**
	 * Get link data from `site name`
	 * 
	 * @param  [string] $site_name
	 * @return [array]  all links
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

	private function set_request_uri($uri, $full_domain_name = '', $is_full_path = 0)
	{
		$results = $uri;
		if ($is_full_path == 0) $results = get_request_uri($uri, $full_domain_name);
		
		return urldecode($results);
	}

	/**
	 * Get the correctly format of url
	 * 
	 * @param  [string] $url
	 * @param  [array]  $site site info
	 * @return [string] proper url format
	 */
	private function get_post_url($request_uri, $site)
	{
		$post_url = $request_uri;
		if ($site['is_full_path'] == 0) $post_url = remove_trailing_slash($site['url']).$request_uri;

		return $post_url;
	}

	private function get_published_posts() { return $this->news_model->get_random_urls($this->page_type, $this->post_limit, 1); }
	private function get_random_posts() { return $this->news_model->get_random_urls($this->page_type, $this->post_limit); }
	private function get_latest_posts() { return $this->news_model->get_latest_urls($this->page_type, $this->post_limit); }

	private function get_posts()
	{
		// random posts
		$urls = $this->get_random_posts();
		
		// latest posts
		// $urls = $this->get_latest_posts();

		// if no non-published
		if (empty($urls)) $urls = $this->get_published_posts();;
		
		return $urls;
	}

	/**
	 * Update link by `site_name`
	 * 
	 * @param [string] $site_name
	 */
	private function update_link($site_name)
	{
		if ( ! isset($this->sites[$site_name])) return false;

		$started_time = microtime(true);
		$items = $this->get_link_data($site_name, $this->page_type);
		
		if (count($items) == 0)
		{
			$this->news_model->insert_error_log('update_link', $site_name.' : site down or not found link');
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

	// private function update_site($type, $site_name)
	// {
	// 	$this->page_type = $type;
	// 	$this->set_sites($this->page_type);
	// 	$this->update_link($site_name);
	// }
	
	private function is_type_exists($type)
	{
		$site = $this->news_model->get_sites_by_type($type);
		
		if ( ! empty($site)) return true;
		else return false;
	}

	private function is_site_name_exists($site_name)
	{
		$site = $this->news_model->get_site_by_name($site_name);
		
		if ( ! empty($site)) return true;
		else return false;
	}

	/*================================================================
		#Public
		================================================================*/

	// Why ????
	public function log($name)
	{
		$this->news_model->update_log($name);
		echo 'Logged';
	}

	public function update_site($site_name)
	{
		// if site name is exists
		if ( ! $this->is_site_name_exists($site_name)) return false;

		$this->page_type = $this->news_model->get_type_by_site_name($site_name);
		$this->update_link($site_name);
	}

	public function update_all_sites($type)
	{
		// if type is exists
		if ( ! $this->is_type_exists($type)) return false;

		$this->page_type = $type;
		$this->set_sites($this->page_type);

		ini_set('max_execution_time', 300);
		$started_time = microtime(true);

		foreach ($this->sites as $site) $this->update_link($site['name']);
		
		$time_elapsed_secs = microtime(true) - $started_time;
		printf('<br /><code>%d links found, Total execution time %f<br />', $this->found_link, $time_elapsed_secs);
	}
	
	/**
	 * Update facebook page status
	 */
	public function post($type)
	{
		// if type is exists
		if ( ! $this->is_type_exists($type)) return false;

		// if ( ! is_allowed_time() ) return false;

		$count = 0;
		$this->page_type = $type;

		$urls = $this->get_posts();

		if (empty($urls)) return false;

		$index = range(0, $this->post_limit - 1);
		shuffle($index);

		while ($count < 8)
		{
			$url = $urls[$index[$count]];
			$site = $this->get_site_by_site_id($url['site_id']);

			$post_title = $url['title'];
			
			// get post url
			$post_url = $this->get_post_url($url['request_uri'], $site);

			$is_url_exists = is_url_exists(urldecode($post_url));

			//
			// TODO
			// don't know why some URL isn't exists
			// e.g. https://blog.eduzones.com/studyabroad/143177
			// 
			if ($this->page_type == 'edu') $is_url_exists = true;
			if ($this->page_type == 'youv') $is_url_exists = true;

			if ($is_url_exists)
			{
				// 
				// TODO - Use comment as a title
				// 
				// $comment = array();
				// $comment_element = $site['comment_element'];
				// if ($site['has_comment'] == 1) $comment = $this->get_targeted_data($post_url, $comment_element);
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
					$this->news_model->insert_error_log('post', 'FacebookRequestException : '.$e->getMessage());

					return false;
				}
				catch (\Exception $ex)
				{
					printf('Caught exception: %s', $ex->getMessage());
					$this->news_model->insert_error_log('post', 'Exception : ' .$e->getMessage());

					return false;
				}

				break;
			}
			else
			{
				$this->news_model->insert_error_log('post', 'link number '.$url['id'].' is broken');
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
			$this->news_model->insert_error_log('post', 'all urls are not exist');
			printf('<code>Can not publish (all urls are not exist)</code>');
		}
	}

	/**
	 * See all scraped links by `site name`
	 * 
	 * @param [string] $site_name
	 */
	public function see_link($site_name)
	{
		header('Content-Type: text/html; charset=utf-8');
		$data = $this->get_link_data($site_name);

		dd($data);
	}

	public function update_view($site)
	{
		// 
		// TODO
		// 
		// update the view, fb_like, fb_share
		// 
		
		printf('<code>%s</code>', $site_name.' views have been scraped');
	}

	public function clean()
	{
		$this->news_model->remove_old_record();
		printf('<code>%s</code>', 'Cleaned');
	}

	public function reset() { $this->news_model->truncate_url_table(); }

	public function stop()
	{
		$this->news_model->stop_news();
		printf('<code>%s</code>', 'Stopped');
	}

	public function start()
	{
		$this->news_model->start_news();
		printf('<code>%s</code>', 'Started');
	}

	public function facebook_user_long_lived_session()
	{
		$this->set_facebook_app();
		$user_session = $this->user_session->getLongLivedSession();

		dd($user_session);
	}

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

	public function cron()
	{
		$items = $this->news_model->get_all_types();

		foreach ($items as $item) {
			$type = $item['type'];
			$sites = $this->news_model->get_sites_by_type($type);

			printf('<h4>%s</h4>', $type);
			foreach ($sites as $site) {
				printf('%s/%s<br />', '/usr/bin/GET http://lab.jojoee.com/nn/link', $site['name']);
			}

		}
	}

	/*================================================================
		#Test
		================================================================*/

	public function test()
	{
		$this->load->library('unit_test');

		$this->test_utility_helper();
		$this->test_news();

		$results = $this->unit->result();
		foreach ($results as $result) if ($result['Result'] == 'Failed') da($result);

		echo $this->unit->report();
	}

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

		for ($i=0; $i < 20; $i++) { 
			$urls = $this->get_posts();

			foreach ($urls as $url) {
				$this->unit->run($url['is_publish'], '0', 'get_posts()');
			}	
		}
	}

	private function test_news() {

		$sites = $this->sites;
		$this->unit->run($this->sites, 'is_array', '$this->sites');

		foreach ($sites as $site) $this->unit->run($site, 'is_array', '$sites');
		foreach ($sites as $site) $this->unit->run($this->get_site_by_site_id($site['id']), 'is_array', 'get_site_by_site_id()');
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */