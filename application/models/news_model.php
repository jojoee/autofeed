<?php

class News_model extends CI_Model {

	private $tbl_url;
	private $tbl_name;
	private $tbl_scraped_log;
	private $tbl_published_log;
	private $tbl_error_log;
	private $tbl_admin;

	private $current_timestamp;
	
	function __construct()
	{
		parent::__construct();
		
		$this->tbl_url = 'dm_url';
		$this->tbl_name = 'dm_name';
		$this->tbl_scraped_log = 'dm_scraped_log';
		$this->tbl_published_log = 'dm_published_log';
		$this->tbl_error_log = 'dm_error_log';
		$this->tbl_admin = 'dm_admin';

		$this->current_timestamp = date('Y-m-d H:i:s');
	}

	/**
	 * [update_published_log description]
	 * @param  integer $url_id    [description]
	 * @param  string  $action_id [description]
	 * @return [type]             [description]
	 */
	public function insert_published_log($url_id = 0, $action_id = '')
	{
		$data = array(
			'url_id'          => $url_id,
			'action_id'       => $action_id,
			'published_date'  => $this->current_timestamp
		);
		$this->db->insert($this->tbl_published_log, $data);
	}

	/**
	 * [update_scraped_log description]
	 * @param  integer $name_id [description]
	 * @param  integer $number  [description]
	 * @return [type]           [description]
	 */
	public function insert_scraped_log($name_id = 0, $number = 0)
	{
		$data = array(
			'name_id'         => $name_id,
			'number'          => $number,
			'scraped_date'    => $this->current_timestamp
		);

		$this->db->insert($this->tbl_scraped_log, $data);
	}

	public function get_sites($type = '')
	{
		$where = array('is_active' => 1 );

		if ( ! is_null_or_empty_string($type)) $where['type'] = $type;

		$this->db->where($where);
		$results = $this->db->get($this->tbl_name);

		return $results->result_array();
	}

	/**
	 * [get_all_sites description]
	 * @return [type] [description]
	 */
	public function get_all_sites()
	{
		return $this->get_sites();
	}

	/**
	 * [update_error_log description]
	 * @param  string $function_name [description]
	 * @param  string $message       [description]
	 * @return [type]                [description]
	 */
	public function insert_error_log($function_name = '', $message = '')
	{
		$data = array(
			'function_name'   => $function_name,
			'message'         => $message,
			'created_date'    => $this->current_timestamp
		);

		$this->db->insert($this->tbl_error_log, $data);
	}

	/**
	 * [get_site_by_id description]
	 * NOT USED
	 * 
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function get_site_by_id($id)
	{
		$this->db->where('id', $id);
		$results = $this->db->get($this->tbl_name);

		return $results->result_array();
	}

	/**
	 * [update_broken_link description]
	 * @param  [type] $url_id [description]
	 * @return [type]         [description]
	 */
	public function update_broken_link($url_id)
	{
		$data = array('is_broken' => 1);
		$this->db->where('id', $url_id);
		$this->db->update($this->tbl_url, $data);
	}

	/**
	 * [update_published_link description]
	 * @param  [type] $url_id [description]
	 * @return [type]         [description]
	 */
	public function update_published_link($url_id)
	{
		$data = array('is_publish' => 1);
		$this->db->where('id', $url_id);
		$this->db->update($this->tbl_url, $data);
	}

	/**
	 * [stop_news description]
	 * @return [type] [description]
	 */
	public function stop_news()
	{
		// $this->db->query('UPDATE '.$this->tbl_url.' SET is_active=0 WHERE is_active=1');

		$data = array('is_active' => 0);
		$this->db->where('is_active', 1);
		$this->db->update($this->tbl_url, $data);
	}

	// public function get_random_news($news_limit)
	// {
	// 	$where = array(
	// 		'is_active'   => 1,
	// 		'is_broken'   => 0,
	// 		'is_publish'  => 0,
	// 		'type'				=> 'news'
	// 	);
	// 	$this->db->where($where);
	// 	$this->db->order_by('id', 'RANDOM');
	// 	$this->db->limit($news_limit);
	// 	$query = $this->db->get($this->tbl_url);

	// 	return $query->result_array();
	// }
	
	public function get_random_urls($type, $limit)
	{
		$where = array(
			$this->tbl_url.'.is_active'   => 1,
			$this->tbl_url.'.is_broken'   => 0,
			$this->tbl_url.'.is_publish'  => 0,
			$this->tbl_name.'.type'       => $type
		);
		$join_where = $this->tbl_url.'.name_id = '.$this->tbl_name.'.id';
		$select = '*';

		$this->db->select($select);
		$this->db->from($this->tbl_url);
		$this->db->join($this->tbl_name, $join_where);
		$this->db->where($where);
		$this->db->order_by('id', 'RANDOM');
		$this->db->limit($limit);
		$query = $this->db->get();

		return $query->result_array();
	}

	/**
	 * [get_random_news description]
	 * @param  [type] $news_limit [description]
	 * @return [type]             [description]
	 */
	public function get_random_news($limit) { return $this->get_random_urls('news', $limit); }

	public function get_random_pantip($limit) { return $this->get_random_urls('pantip', $limit); }

	/**
	 * [get_news_by_id description]
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function get_news_by_id($id)
	{
		$where = array('id' => $id);
		$this->db->where($where);
		$query = $this->db->get($this->tbl_url);

		return $query->result_array()[0];
	}

	/**
	 * [truncate_url_table description]
	 * @return [type] [description]
	 */
	public function truncate_url_table()
	{
		$this->db->truncate($this->tbl_url);	
	}

	/**
	 * [start_news description]
	 * @return [type] [description]
	 */
	public function start_news()
	{
		// $this->db->query('UPDATE '.$this->tbl_url.' SET is_active=1 WHERE is_active=0');

		$data = array('is_active' => 1);
		$this->db->where('is_active', 0);
		$this->db->update($this->tbl_url, $data);
	}

	/**
	 * [save_news_link description]
	 * @param  [type] $name_id [description]
	 * @param  [type] $items   [description]
	 * @return [type]          [description]
	 */
	public function save_link($name_id, $items)
	{
		$data = array(
			'name_id'         => $name_id,
			'created_date'    => $this->current_timestamp
		);

		foreach ($items as $item)
		{
			$query = $this->db->get_where($this->tbl_url, array(
				'name_id'     => $name_id,
				'request_uri' => $item['request_uri']
			), 1);

			if ($query->num_rows() == 0)
			{
				$data['title'] = $item['title'];
				$data['request_uri'] = $item['request_uri'];

				$this->db->insert($this->tbl_url, $data);
			}
		}
	}

	/**
	 * [get_random_admin description]
	 * @return [type] [description]
	 */
	public function get_random_admin($limit = 1)
	{
		$this->db->where(array(
			'is_active'   => 1
		));
		$this->db->order_by('id', 'RANDOM');
		$this->db->limit($limit);
		$query = $this->db->get($this->tbl_admin);

    return $query->result_array();
	}

	/**
	 * [remove_old_url description]
	 *
	 * @link http://community.sitepoint.com/t/finding-the-date-30-days-ago/4406
	 * @link  http://stackoverflow.com/questions/4134799/codeigniter-active-record-greater-than-statement
	 * 
	 * @return [type] [description]
	 */
	public function remove_old_record()
	{
		$one_week_ago = get_date_ago('7');
		$one_month_ago = get_date_ago('30');
		$two_month_ago = get_date_ago('60');
		$three_month_ago = get_date_ago('90');

		$this->remove_old_news_url($one_week_ago);
		// $this->remove_old_pantip_url($three_month_ago);
		// $this->remove_old_edu_url($three_month_ago);
		$this->remove_old_jojoee_url($two_month_ago);
		$this->remove_old_youv_url($two_month_ago);

		$this->remove_old_scraped_log($one_month_ago);
		$this->remove_old_published_log($one_month_ago);
	}

	/**
	 * [remove_old_url description]
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	public function remove_old_url($type, $date)
	{
		$sites = $this->get_sites($type);

		foreach ($sites as $site)
		{
			$where = array(
				'created_date <' => $date,
				'name_id'        => $site['id']
			);	
			$this->db->where($where);
			$this->db->delete($this->tbl_url);
		}
	}

	private function remove_old_news_url($date) { $this->remove_old_url('news', $date); }

	private function remove_old_pantip_url($date) { $this->remove_old_url('pantip', $date); }

	private function remove_old_edu_url($date) { $this->remove_old_url('edu', $date); }

	private function remove_old_jojoee_url($date) { $this->remove_old_url('jojoee', $date); }

	private function remove_old_youv_url($date) { $this->remove_old_url('youv', $date); }

	/**
	 * [remove_old_scraped_log description]
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	public function remove_old_scraped_log($date)
	{
		$this->db->where('scraped_date <', $date);
		$this->db->delete($this->tbl_scraped_log);
	}

	/**
	 * [remove_old_published_log description]
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	public function remove_old_published_log($date)
	{
		$this->db->where('published_date <', $date);
		$this->db->delete($this->tbl_published_log);
	}

	/**
	 * [remove_old_error_log description]
	 * @param  [type] $date [description]
	 * @return [type]       [description]
	 */
	public function remove_old_error_log($date)
	{
		$this->db->where('created_date <', $date);
		$this->db->delete($this->tbl_error_log);
	}
}
