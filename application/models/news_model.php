<?php

class News_model extends CI_Model {

	private $tbl_url;
	private $tbl_site;
	private $tbl_admin;

	private $tbl_scraped_log;
	private $tbl_published_log;
	private $tbl_error_log;
	private $tbl_log;
	
	private $current_timestamp;
	
	function __construct()
	{
		parent::__construct();
		
		$this->tbl_url = 'dm_url';
		$this->tbl_site = 'dm_site';
		$this->tbl_admin = 'dm_admin';

		$this->tbl_scraped_log = 'dm_scraped_log';
		$this->tbl_published_log = 'dm_published_log';
		$this->tbl_error_log = 'dm_error_log';
		$this->tbl_log = 'dm_log';

		$this->current_timestamp = date('Y-m-d H:i:s');
	}

	public function get_all_types()
	{
		$where = array(
			'is_active' => 1
		);

		$this->db->select('type');
		$this->db->where($where);
		$this->db->distinct();
		$query = $this->db->get($this->tbl_site);

		return $query->result_array();
	}

	// Why ????
	public function update_log($name)
	{
		switch ($name) {
			case 'new':
				$this->update_log_new_link();
				break;
			
			default:
				break;
		}
	}

	// Why ????
	public function update_log_new_link()
	{
		$one_day_ago = get_date_ago('1');
		$two_day_ago = get_date_ago('2');

		$sites = $this->get_all_sites();

		foreach ($sites as $site) {
			$site_id = $site['id'];

			$where = array(
				'created_date <' => $one_day_ago,
				'created_date >' => $two_day_ago,
				'site_id'        => $site_id
			);

			$this->db->select('id');
			$this->db->from($this->tbl_url);
			$this->db->where($where);
			$query = $this->db->get();

			$data = array(
				'name'      => 'new link',
				'site_id'   => $site_id,
				'period'    => 'daily',
				'value'			=> $query->num_rows()
			);

			$this->insert_log($data);
		}
	}

	public function insert_log($data)
	{
		$data['created_date'] = $this->current_timestamp;
		$this->db->insert($this->tbl_log, $data);
	}

	public function insert_published_log($url_id = 0, $action_id = '')
	{
		$data = array(
			'url_id'          => $url_id,
			'action_id'       => $action_id,
			'created_date'    => $this->current_timestamp
		);

		$this->db->insert($this->tbl_published_log, $data);
	}

	public function insert_scraped_log($site_id = 0, $number = 0)
	{
		$data = array(
			'site_id'         => $site_id,
			'number'          => $number,
			'created_date'    => $this->current_timestamp
		);

		$this->db->insert($this->tbl_scraped_log, $data);
	}

	public function get_sites($type = '')
	{
		$where = array('is_active' => 1 );

		if ( ! is_null_or_empty_string($type)) $where['type'] = $type;

		$this->db->where($where);
		$query = $this->db->get($this->tbl_site);

		return $query->result_array();
	}

	public function get_all_sites() { return $this->get_sites(); }

	public function insert_error_log($function_name = '', $message = '')
	{
		$data = array(
			'function_name'   => $function_name,
			'message'         => $message,
			'created_date'    => $this->current_timestamp
		);

		$this->db->insert($this->tbl_error_log, $data);
	}

	public function get_type_by_site_name($name)
	{
		$where = array(
			'name'      => $name,
			'is_active' => 1
		);

		$this->db->where('name', $name);
		$query = $this->db->get($this->tbl_site);

		return $query->first_row()->type;
	}

	public function get_site_by_id($id)
	{
		$where = array(
			'id'        => $id,
			'is_active' => 1
		);

		$this->db->where($where);
		$query = $this->db->get($this->tbl_site);

		return $query->result_array();
	}

	public function get_site_by_name($name)
	{
		$where = array(
			'name'      => $name,
			'is_active' => 1
		);

		$this->db->where('name', $name);
		$query = $this->db->get($this->tbl_site);

		return $query->first_row('array');
	}

	public function get_sites_by_type($type)
	{
		$where = array(
			'type'      => $type,
			'is_active' => 1
		);

		$this->db->where($where);
		$query = $this->db->get($this->tbl_site);

		return $query->result_array();
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
		$data = array('is_active' => 0);
		$this->db->where('is_active', 1);
		$this->db->update($this->tbl_url, $data);
	}
	
	public function get_random_urls($type, $limit, $is_publish = 0)
	{
		$where = array(
			$this->tbl_url.'.is_active'   => 1,
			$this->tbl_url.'.is_broken'   => 0,
			$this->tbl_url.'.is_publish'  => $is_publish,
			$this->tbl_site.'.type'       => $type
		);
		$join_where = $this->tbl_url.'.site_id = '.$this->tbl_site.'.id';
		$select = '*';

		$this->db->select($select);
		$this->db->from($this->tbl_url);
		$this->db->join($this->tbl_site, $join_where);
		$this->db->where($where);
		$this->db->order_by('id', 'RANDOM');
		$this->db->limit($limit);
		$query = $this->db->get();

		return $query->result_array();
	}

	public function get_latest_urls($type, $limit)
	{
		$where = array(
			$this->tbl_url.'.is_active'   => 1,
			$this->tbl_url.'.is_broken'   => 0,
			$this->tbl_url.'.is_publish'  => 0,
			$this->tbl_site.'.type'       => $type
		);
		$join_where = $this->tbl_url.'.site_id = '.$this->tbl_site.'.id';
		$select = '*';

		$this->db->select($select);
		$this->db->from($this->tbl_url);
		$this->db->join($this->tbl_site, $join_where);
		$this->db->where($where);
		$this->db->order_by('created_date', 'DESC');
		$this->db->limit($limit);
		$query = $this->db->get();

		return $query->result_array();
	}

	public function get_url_by_id($id)
	{
		$where = array('id' => $id);
		$this->db->where($where);
		$query = $this->db->get($this->tbl_url);

		return $query->result_array()[0];
	}

	public function truncate_url_table() { $this->db->truncate($this->tbl_url); }

	public function start_news()
	{
		$data = array('is_active' => 1);
		$this->db->where('is_active', 0);
		$this->db->update($this->tbl_url, $data);
	}

	public function save_link($site_id, $items)
	{
		$data = array(
			'site_id'         => $site_id,
			'created_date'    => $this->current_timestamp
		);

		foreach ($items as $item)
		{
			$query = $this->db->get_where($this->tbl_url, array(
				'site_id'     => $site_id,
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
	 * Remove old url data
	 *
	 * @link http://community.sitepoint.com/t/finding-the-date-30-days-ago/4406
	 * @link http://stackoverflow.com/questions/4134799/codeigniter-active-record-greater-than-statement
	 */
	public function remove_old_record()
	{
		$one_week_ago = get_date_ago('7');
		$one_month_ago = get_date_ago('30');
		$two_month_ago = get_date_ago('60');
		$three_month_ago = get_date_ago('90');

		$this->remove_old_news_url($one_week_ago);
		$this->remove_old_pantip_url($three_month_ago);
		$this->remove_old_edu_url($two_month_ago);
		$this->remove_old_jojoee_url($three_month_ago);
		$this->remove_old_youv_url($three_month_ago);

		$this->remove_old_scraped_log($one_month_ago);
		$this->remove_old_published_log($one_month_ago);
	}

	public function remove_old_url($type, $date)
	{
		$sites = $this->get_sites($type);

		foreach ($sites as $site)
		{
			$where = array(
				'created_date <' => $date,
				'site_id'        => $site['id']
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

	public function remove_old_scraped_log($date)
	{
		$this->db->where('created_date <', $date);
		$this->db->delete($this->tbl_scraped_log);
	}

	public function remove_old_published_log($date)
	{
		$this->db->where('created_date <', $date);
		$this->db->delete($this->tbl_published_log);
	}

	public function remove_old_error_log($date)
	{
		$this->db->where('created_date <', $date);
		$this->db->delete($this->tbl_error_log);
	}
}
