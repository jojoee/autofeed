<?php

class FB_model extends CI_Model {

	private $tbl_fb_meta;

	private $app_name;
	private $user_name;

	private $page_news;
	private $page_pantip;
	private $page_edu;
	private $page_jojoee;
	private $page_youv;

	private $current_timestamp;
	
	function __construct()
	{
		parent::__construct();
		
		$this->tbl_fb_meta = 'dm_fb_meta';
		
		$this->current_timestamp = date('Y-m-d H:i:s');
		
		$this->user_name = 'joe';
		$this->app_name = 'jojoeenews';

		// $this->page_news = 'news';
		// $this->page_pantip = 'pantip';
		// $this->page_edu = 'edu';
		// $this->page_jojoee = 'jojoee';
		// $this->page_youv = 'youv';
	}

	private function get_fb_meta($key = '', $type = '')
	{
		$query = $this->get_fb_meta_query($key, $type);

		return $query->first_row()->value;
	}

	private function get_fb_meta_query($key, $type)
	{
		$where = array();

		if ( ! is_null_or_empty_string($key)) $where['key'] = $key;
		if ( ! is_null_or_empty_string($type)) $where['type'] = $type;

		$this->db->select('value');
		$this->db->where($where);
		$query = $this->db->get($this->tbl_fb_meta);

		return $query;
	}

	private function get_fb_meta_array($key, $type)
	{
		$query = $this->get_fb_meta_query($key, $type);
		
		return $query->result_array();
	}

	public function get_all_page_ids() { return $this->get_fb_meta_array('', 'page_id'); }

	public function get_facebook_app_id() { return $this->get_fb_meta($this->app_name, 'app_id'); }
	public function get_facebook_app_secret() { return $this->get_fb_meta($this->app_name, 'app_secret'); }

	public function get_facebook_user_id() { return $this->get_fb_meta($this->user_name, 'user_id'); }
	public function get_facebook_user_access_token() { return $this->get_fb_meta($this->user_name, 'user_access_token'); }

	// public function get_facebook_news_page_id() { return $this->get_fb_meta($this->page_news, 'page_id'); }
	// public function get_facebook_pantip_page_id() { return $this->get_fb_meta($this->page_pantip, 'page_id'); }
	// public function get_facebook_edu_page_id() { return $this->get_fb_meta($this->page_edu, 'page_id'); }
	// public function get_facebook_jojoee_page_id() { return $this->get_fb_meta($this->page_jojoee, 'page_id'); }
	// public function get_facebook_youv_page_id() { return $this->get_fb_meta($this->page_youv, 'page_id'); }

	// public function get_facebook_news_page_access_token() { return $this->get_fb_meta($this->page_news, 'page_access_token'); }
	// public function get_facebook_pantip_page_access_token() { return $this->get_fb_meta($this->page_pantip, 'page_access_token'); }
	// public function get_facebook_edu_page_access_token() { return $this->get_fb_meta($this->page_edu, 'page_access_token'); }
	// public function get_facebook_jojoee_page_access_token() { return $this->get_fb_meta($this->page_jojoee, 'page_access_token'); }
	// public function get_facebook_youv_page_access_token() { return $this->get_fb_meta($this->page_youv, 'page_access_token'); }

	public function get_facebook_page_id($name) { return $this->get_fb_meta($name, 'page_id'); }
	public function get_facebook_page_access_token($name) { return $this->get_fb_meta($name, 'page_access_token'); }

}
