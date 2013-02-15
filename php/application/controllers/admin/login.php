<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {
    function __construct() {
        parent::__construct();
        $this->load->model('User');
        $this->load->library('session');
        $this->load->helper('url');
    }


	function index() {
        $this->tal->authentication = array();
        if ( isset($_SESSION['user_id']) ) {
            redirect('admin/process');
        } else {
            $this->tal->display('admin/login.php');
        }
	}

	function do_login() {
        $authentication = $this->User->authenticate($_POST['user'], $_POST['password']);
        if(count($authentication) == 1) {
            $authentication->user = $_POST['user'];
            $this->session->set_userdata('user_id', $_POST['user']);
            $_SESSION['authentication'] = $authentication;
            $this->session->set_userdata('is_admin', TRUE);
            $this->tal->authentication = $authentication;
            redirect('admin/process');
        } else {
            redirect('admin/login');
        } 
	}

    public function logout($process_code) {
        $this->session->unset_userdata('user_id');
        $this->session->unset_userdata('is_admin');
        if(isset($process_code)) {
            redirect('/p/'.$process_code);
        } else {
            redirect('admin/login');
        }
    }
}

