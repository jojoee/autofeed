<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;
use Facebook\FacebookRedirectLoginHelper;

class News extends CI_Controller {

	private $sites;
	private $news_limit;
	private $found_link;
	private $url_limit;

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
		$this->found_link = 0;
		$this->news_limit = 8;

		$this->set_sites();
	}

	private function set_sites()
	{
		$sites = $this->news_model->get_all_sites();

		$results = array();
		foreach ($sites as $site) $results[$site['name']] = $site;

		$this->sites = $results;
	}

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

	private function set_facebook_app()
	{
		require_once('vendor/facebook-php-sdk/autoload.php');

		$this->app_id = get_facebook_app_id();
		$this->app_secret = get_facebook_app_secret();

		$this->user_id = get_facebook_user_id();
		$this->user_access_token = get_facebook_user_access_token();

		$this->page_id = get_facebook_page_id();
		$this->page_access_token = get_facebook_page_access_token();

		// initialize your app using your key and secret
		FacebookSession::setDefaultApplication($this->app_id , $this->app_secret);
		
		$this->user_session = new FacebookSession($this->user_access_token);
		$this->page_session = new FacebookSession($this->page_access_token);
	}

	private function publish_facebook($post_title, $post_url)
	{
		$this->set_facebook_app();

		$message = sprintf('%s : %s - %s',
			html_entity_decode($post_title),
			$post_url,
			'#adminChai' );

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

	public function get_post_url($url, $site)
	{
		$post_url = $url['request_uri'];
		if ( ! $site['is_full_path']) $post_url = remove_trailing_slash($site['link_url']).$url['request_uri'];

		return $post_url;
	}

	public function post_news()
	{
		$count = 0;
		$urls = $this->news_model->get_random_news($this->news_limit);

		if (empty($urls)) return false;

		$index = range(0, $this->news_limit - 1);
		shuffle($index);

		while ($count < 8)
		{
			$url = $urls[$index[$count]];
			$site = $this->get_site_by_name_id($url['name_id']);

			// get post title
			// 
			// TODO
			// HOW ABOUT USE COMMENT AS A TITLE
			// 
			$post_title = $url['title'];
			
			// get post url
			$post_url = $this->get_post_url($url, $site);

			if (is_url_exists(urldecode($post_url)))
			{
				try
				{
					$action = $this->publish_facebook($post_title, $post_url);
					$action_id = $action['id'];
					$this->news_model->update_published_link($url['id']);
				}
				catch (FacebookRequestException $ex)
				{
					echo 'FacebookRequestException: '.$ex->getMessage();
					$this->news_model->update_error_log('post_news', $e->getMessage());

					return false;
				}
				catch (\Exception $ex)
				{
					echo 'Caught exception: '.$ex->getMessage();
					$this->news_model->update_error_log('post_news', $e->getMessage());

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
			$this->news_model->update_published_log($url['id'], $action_id);
			echo '<code>Published</code>';
		}
	}

	private function get_link_data($site_key)
	{
		require_once('vendor/php-simple-html-dom-parser/Src/Sunra/PhpSimple/simplehtmldom_1_5/simple_html_dom.php');
		
		$site = $this->sites[$site_key];

		if (is_null_or_empty_string($site['link_url']) || is_null_or_empty_string($site['link_element'])) return array();

		$domain_name = get_domain_name($site['link_url']);

		try
		{
			$html = file_get_html($site['link_url']);
		}
		catch (Exception $e)
		{
			echo 'Caught exception: '.$e->getMessage();
			$this->news_model->update_error_log('get_link_data', $e->getMessage());

			return false;
		}

		if ( ! isset($html) && is_null_or_empty_string($html)) return array();

		$items = array();
		$articles = $html->find($site['link_element']);

		foreach ($articles as $article)
		{
			$item = array();
			$anchor = $article;

			if (isset($anchor->plaintext) &&
				isset($anchor->href) &&
				! is_null_or_empty_string($anchor->plaintext) &&
				! is_null_or_empty_string($anchor->href))
			{
				$item['title'] = trim($anchor->plaintext);
				$item['request_uri'] = $this->set_request_uri(trim($anchor->href), $domain_name, $site['is_full_path']);
			}

			if (isset($view->plaintext) && ! is_null_or_empty_string($view->plaintext)) $item['view'] = $view->plaintext;
			
			if ( ! empty($item)) $items[] = $item;
		}

		return $items;
	}

	private function set_request_uri($uri, $domain_name = '', $is_full_path = 0)
	{
		$results = $uri;
		if ( ! $is_full_path) $results = get_request_uri($uri, $domain_name);
		
		return urldecode($results);
	}

	public function update_link($site_key)
	{
		$started_time = microtime(true);
		$this->found_link = 0;

		$items = $this->get_link_data($site_key);
		$this->news_model->save_news_link($this->sites[$site_key]['id'], $items);
		$this->found_link += count($items);

		$time_elapsed_secs = microtime(true) - $started_time;
		$this->news_model->update_scraped_log($this->sites[$site_key]['id'], $this->found_link);

		printf('<code>%d %s (%f secs)</code><br />',
			count($items),
			$site_key.' links have been scraped',
			$time_elapsed_secs
		);
	}

	public function update_all_links()
	{
		ini_set('max_execution_time', 300);
		$started_time = microtime(true);

		foreach ($this->sites as $site) $this->update_link($site['name']);
		
		$time_elapsed_secs = microtime(true) - $started_time;
		printf('<br /><code>total execution time %f<br />', $time_elapsed_secs);
	}

	public function see_link($site_key)
	{
		header('Content-Type: text/html; charset=utf-8');
		$data = $this->get_link_data($site_key);

		dd($data);
	}

	public function update_view($site)
	{
		printf('<code>%s</code>', $site_key.' views have been scraped');
	}

	/*================================================================
		#Debug
		================================================================*/

	public function reset()
	{
		$this->news_model->truncate_url_table();
	}

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
		dd($this->get_facebook_user_long_lived_session());
	}

	private function get_facebook_user_long_lived_session()
	{
		$this->set_facebook_app();
		return $this->user_session->getLongLivedSession();
	}

	private function get_facebook_page_long_lived_session()
	{
		$this->set_facebook_app();

		$request = new FacebookRequest($this->user_session, 'GET', '/'.$this->page_id.'?fields=access_token');
		$results = $request->execute()->getGraphObject()->asArray();

		$page_access_token = $results['access_token'];
		$page_session = new FacebookSession($page_access_token);

		return $page_session->getLongLivedSession();
	}

	public function facebook_page_long_lived_session()
	{
		dd($this->get_facebook_page_long_lived_session());
	}

	/*================================================================
		#Test
		================================================================*/

	public function test()
	{
		$url = 'http://fashion.spokedark.tv/2015/04/24/dichan-magazine/';
		$url = 'http://fashion.spokedark.tv/?p=6600';
		$url = 'http://movies.spokedark.tv?p=10054/';

		if(is_url_redirects($url)) echo 'redirect<br />';
		if(is_url_exists($url)) echo 'exists<br />';
		die();

		printf('<code>%s</code>', 'Tested');
	}


}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */