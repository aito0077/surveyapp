<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Process_Viewer extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Processes');
        $this->load->model('Participant');
        $this->load->helper('url');
    }

	function index() {

    }

    function login($process_id = NULL, $error_message = NULL) {
        $process_code = $process_id;
        if(!isset($process_code)) {
            if(isset($_POST['process_code'])) {
                $process_code = $_POST['process_code'];
            } else {
                if(isset($_GET['process_code'])) {
                    $process_code = $_GET['process_code'];
                } else {
                    if($this->session->userdata('process_code') != NULL) {
                        $process_code = $this->session->userdata('process_code');
                    } else {
                        $process_code = 'COD1';
                    }
                }
            }
        }
        $this->session->set_userdata('cod_process', $process_code);
        $process = $this->Processes->getByCode(urldecode($process_code));
        $theme = $process['theme'];
        $this->tal->process = $process;
        $this->tal->activity = array(
            'code' => '' 
        );
        $this->tal->fields = $process['identity_fields'];
        $this->tal->mensaje = $error_message;

        $template_name = 'workflow/wrapper/'.$theme.'/login.html';
		$this->tal->display($template_name);
    }


    function logout($process_code) {
        $this->session->unset_userdata('participant_id', NULL);
        redirect('/login/'.$process_code);
    }

    function do_login() {
        $process_id = $_POST['process_code'];

        if(!isset($_POST['login_base'])) {
            redirect('/login/'.$process_id);
        }
        
        $participant = $this->Participant->identify_participant($process_id, $_POST);
        if(count($participant) == 1) {
            $this->session->set_userdata('participant_id', $participant[0]['_id']);
            $this->session->set_userdata('is_admin', FALSE);
        } else {
            redirect('/login/'.$process_id);
        }
    }

	function bases($process_code) {
        $process = $this->Processes->getByCode(urldecode($process_code));
        $this->tal->process = $process;
        if(isset($process['bases'])) {
            $this->tal->bases = $process['bases'];
        } else {
            $this->tal->bases = array();
        }
        $theme = $process['theme'];
        $template_name = 'workflow/wrapper/'.$theme.'/bases.html';
        $this->tal->display($template_name);
    }

	function render($process_code) {
        if(isset($_POST['login']) && $_POST['login'] == 'DO' ) {
            $this->do_login();
        }
        $current_activity_code = NULL;
        $participant_id = $this->session->userdata('participant_id');
        $is_admin = $this->session->userdata('is_admin');
        if (!$this->session->userdata('participant_id') && !$is_admin) {
            redirect('/login/'.$process_code);
        }
        $participant = array();
        $process = $this->Processes->getByCode(urldecode($process_code));
        $theme = $process['theme'];
        if($process['type'] == 'content') {
            $this->tal->process = $process;
            $template_name = 'workflow/wrapper/'.$theme.'/content_main.html';
        } else {
                $activities = $process['activities'];
                if(isset($_POST['activity_code'])) {
                    $current_activity_code = $_POST['activity_code'];
                } else {
                    $current_activity_code = $activities[0]['code'];
                }
                $result_activity = $is_admin || $this->evaluate_activity($process, $current_activity_code, $participant_id, $_POST);
                if($is_admin) {
                    $participant = array(
                        'describe' => 'demo',
                        'name' => 'demo'
                    );
                } else {
                    $participant = $this->Participant->get_participant($participant_id);
                }
                $this->tal->participant = $participant;
                if($is_admin) {
                    if(isset($_POST['activity_code'])) {
                        $current_activity = $this->get_demo_activity($activities, $current_activity_code);
                    } else {
                        $current_activity = $activities[0];
                    }
                } else {
                    $current_activity = $this->get_next_activity($activities, $participant, $current_activity_code, $result_activity);

                }
                if(!isset($current_activity)) {
                    $this->tal->process = $process;
                    $this->tal->activity = array(
                        'code' => $current_activity_code
                    );
                    $template_name = 'workflow/wrapper/'.$theme.'/logout.html';
                } else {
                    $this->tal->process = $process;
                    $this->tal->activity = $current_activity;
                    $this->tal->activity_template = $this->get_activity_template($current_activity);
                    $template_name = 'workflow/wrapper/'.$theme.'/cuestionario_wrapper.html';
                }

        }
        $this->tal->display($template_name);
       
	}

    function get_next_activity($activities, $participant, $current_activity, $result_activity) {
        $current_activity = $this->Processes->get_next_activity($activities, $participant, $current_activity, $result_activity); 
        return $current_activity;
    }

    function get_demo_activity($activities, $current_activity_code) {
        $_next = FALSE;
        $next_activity = NULL;
        usort($activities, function ($a, $b) {
            if($a['order'] > $b['order']) return 1;
            if($a['order'] < $b['order']) return -1;
            return 0;
        });

        if(!isset($current_activity_code)) {
            return $activities[0];
        }

        foreach($activities as $activity) {
            if($_next) {
                $next_activity = $activity;
                break;
            }
            $_next = ($activity['code'] == $current_activity_code);
        } 
        return $next_activity;
    }

    function get_activity_template($activity) {
        $activity_class = $activity['super_type'];
        $activity_type = $activity['type'];
        return strtolower($activity_class.'_'.$activity_type);
    }

    function evaluate_activity($process_code, $activity_code, $participant_id, $values) {
        if(!isset($activity_code)) {
            return TRUE;
        }
        $activity = $this->Processes->get_activity($activity_code);
        $super_type = $activity['super_type'];
        if($super_type == 'content' || $super_type == 'specials') {

            $result = NULL;
            $this->Participant->save_value($process_code, $activity_code, $participant_id, $result, 0);
            return TRUE;
        }
        if(!isset($values[$activity_code])) {
            return FALSE;
        }
        $result = $values[$activity_code];
        $time_spent = $values['time_spent'];
        $this->Participant->save_value($process_code, $activity_code, $participant_id, $result, $time_spent);

        return TRUE;
    }

    function get_fields_login($process_code) {
        
    }

    function get_step($process_code, $activity_code, $participant_id) {
        $step = $this->Participant->get_step($process_code, $activity_code, $participant_id);
        print_r($step);
    }

    function ranking($process_code) {
        $process = $this->Processes->getByCode(urldecode($process_code));
        $ranking = $this->Participant->ranking($process);
        print_r($ranking);
    }
     
}

