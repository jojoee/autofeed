<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -	
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		// $this->load->view('welcome_message');
		$this->_display('hello');
	}

	public function quote()
	{
		$format = 'json';
		$max_lines = 1;
		$max_characters = 88;
		$url = sprintf('http://www.iheartquotes.com/api/v1/random?format=%s&max_lines=%d&max_characters=%d',
			$format,
			$max_lines,
			$max_characters);

		$json = file_get_contents($url);

		echo $json;
	}

	public function error_404()
	{
		$this->_display('error_404');
	}

	private function _display($view)
	{
		$this->load->view('header');
		$this->load->view($view);
		$this->load->view('footer');
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */