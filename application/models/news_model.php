<?php

class News_model extends CI_Model {

	private $tbl_url;
	private $tbl_name;
	private $tbl_scraped_log;
	private $tbl_published_log;
	private $tbl_error_log;

	private $current_timestamp;
	
	function __construct()
	{
		parent::__construct();
		
		$this->tbl_url = 'dm_url';
		$this->tbl_name = 'dm_name';
		$this->tbl_scraped_log = 'dm_scraped_log';
		$this->tbl_published_log = 'dm_published_log';
		$this->tbl_error_log = 'dm_error_log';

		$this->current_timestamp = date('Y-m-d H:i:s');
	}

	public function update_published_log($url_id = 0, $action_id = '')
	{
		$data = array(
			'url_id'          => $url_id,
			'action_id'       => $action_id,
			'published_date'  => $this->current_timestamp
		);
		$this->db->insert($this->tbl_published_log, $data);
	}

	public function update_scraped_log($name_id = 0, $number = 0)
	{
		$data = array(
			'name_id'         => $name_id,
			'number'          => $number,
			'scraped_date'    => $this->current_timestamp
		);

		$this->db->insert($this->tbl_scraped_log, $data);
	}

	public function get_all_sites()
	{
		$this->db->where('is_active', 1);
		$results = $this->db->get($this->tbl_name);

		return $results->result_array();
	}

	public function update_error_log($function_name = '', $message = '')
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
	 *
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

	public function update_broken_link($url_id)
	{
		$data = array('is_broken' => 1);
		$this->db->where('id', $url_id);
		$this->db->update($this->tbl_url, $data);
	}

	public function update_published_link($url_id)
	{
		$data = array('is_publish' => 1);
		$this->db->where('id', $url_id);
		$this->db->update($this->tbl_url, $data);
	}

	public function stop_news()
	{
		// $this->db->query('UPDATE '.$this->tbl_url.' SET is_active=0 WHERE is_active=1');

		$data = array('is_active' => 0);
		$this->db->where('is_active', 1);
		$this->db->update($this->tbl_url, $data);
	}

	public function get_random_news($news_limit)
	{
		$this->db->where(array(
			'is_active'   => 1,
			'is_broken'   => 0,
			'is_publish'  => 0
		));
    $this->db->order_by('id', 'RANDOM');
    $this->db->limit($news_limit);
    $query = $this->db->get($this->tbl_url);

    return $query->result_array();
	}

	public function truncate_url_table()
	{
		$this->db->truncate($this->tbl_url);	
	}
	
	public function start_news()
	{
		// $this->db->query('UPDATE '.$this->tbl_url.' SET is_active=1 WHERE is_active=0');

		$data = array('is_active' => 1);
		$this->db->where('is_active', 0);
		$this->db->update($this->tbl_url, $data);
	}

	public function save_news_link($name_id, $items)
	{
		$data = array(
			'name_id'         => $name_id,
			'updated_date'    => $this->current_timestamp
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
}
