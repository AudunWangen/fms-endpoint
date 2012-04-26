<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends Controller { // not CI_Controller (XXX: old-CI)

	function __construct()
	{
		parent::__construct();
		
		/* Standard Libraries */
		$this->load->database();
		$this->load->helper('url');
		/* ------------------ */	
		
		$this->load->library('grocery_CRUD');
		
		if (!$this->ion_auth->logged_in()) {
			redirect('auth/login');
		}
	}

	function _remap($method)
    {
      if (method_exists($this, $method))
      {
        $this->$method();
      }
      else {
        $this->index($method);
      }
    }

	function index() {
		try{
			/* This is only for the autocompletion */
			$crud = new grocery_CRUD();

			$crud->set_theme('flexigrid');
			$crud->set_table('reports');
			$crud->set_subject('Problem report');
			$crud->required_fields('report_id');
			$crud->display_as('requested_datetime', 'Received')
			      ->display_as('category_name', 'Category')
			      ->display_as('media_url', 'URL');

			$crud->callback_column('media_url',array($this,'_linkify'));
			$crud->set_relation('priority','priorities','<span class="fmse-prio fmse-prio{prio_value}">{prio_name}</span>',null,'prio_value ASC');

			$crud->columns('report_id','status', 'requested_datetime','priority','category_name','media_url','description','address');

			$output = $crud->render();
			
			$this->_admin_output($output);
			
		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}	
	
	function reports()
	{
	  $crud = new grocery_CRUD();

		$crud->set_theme('flexigrid');
		$crud->set_table('reports');
		$crud->set_relation('priority','priorities','prio_name',null,'priority ASC');
		$crud->display_as('requested_datetime', 'Received')
		      ->display_as('category_name', 'Category')
		      ->display_as('media_url', 'URL');
		
		$crud->callback_column('media_url',array($this,'_linkify'));
		$crud->set_relation('priority','priorities','<span class="fmse-prio fmse-prio{prio_value}">{prio_name}</span>',null,'prio_value ASC');


		$output = $crud->render();

		$this->_admin_output($output);
	}
	
	function reports_csv() {
	    $this->load->helper('csv');
	    $query = $this->db->get('reports');
	    $filename = date('Y-m-d_Hi') . '_reports.csv';
	    query_to_csv($query, TRUE, $filename);
	}
	
	function categories() {
	  $crud = new grocery_CRUD();

		$crud->set_theme('flexigrid');
		$crud->set_table('categories');
		
		if (!($this->ion_auth->is_admin() || $this->_config_true($this->config->item('can_edit_categories')))) {
		    $crud->unset_delete();
		    $crud->unset_add();
		    $crud->unset_edit();
		}
		$crud->set_subject('Open311 category');
		$output = $crud->render();

		$this->_admin_output($output);
	}
	
	function settings() {
		if (!$this->ion_auth->is_admin()) {
			redirect('admin/');
		} else {
			$crud = new grocery_CRUD();
			$crud->set_theme('datatables'); /* text wraps for the long descriptions in databables, not flexigrid */
			$crud->set_table('config_settings');
			$crud->display_as('desc', 'Explanation');
			$crud->callback_column('desc', array($this, '_full_description'));
			$crud->unset_texteditor('name','value');
			$crud->unset_delete();
			$crud->set_subject("configuration setting");
			$output = $crud->render();
			$this->_admin_output($output);
		}
	}

	function about() {
	    $output = array('output' => $this->load->view('about', '', true));
		$this->load->view('admin_view.php', $output);	
	}
	
	function _admin_output($output = null)
	{
		$this->load->view('admin_view.php', $output);	
	}
	
	function _linkify($value, $row) {
		$retval = '';
		if ($value) {
			if (preg_match('/https?:\/\/(\\w*\\.)fixmy/', $value)) {
				$retval = 'fmse-web-link-fms';
			} else {
				$retval = 'fmse-web-link';
			}
			$retval = '<a href="' . $value . '" class="' . $retval . '" target="_blank">link</a>';
		}
		return $retval;
	}
	
	function _full_description($value, $row) {
		
		return $value = wordwrap($row->desc, strlen($row->desc), "<br>", true);
	}

	function _config_true($raw_value) {
		$config_value = strtolower(trim($raw_value));
		return ($config_value != '' && $config_value != '0' && $config_value != 'f' && $config_value != 'false' && $config_value != 'no');
	}
}