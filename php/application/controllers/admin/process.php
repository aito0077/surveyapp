<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Process extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Processes');
        $this->load->model('Resource');
        $this->load->model('Participant');
        $this->load->helper('url');
        if (!$this->session->userdata('user_id')) {
            redirect('admin/login');
        }
    }

	function index() {
        $this->listing();
    }

	function listing() {
        $processes = $this->Processes->listAll();
        $this->tal->processes = $processes;
		$this->tal->display('admin/process/list.html');
	}

	function add() {
        $this->tal->batches = $this->Participant->list_batches();
		$this->tal->display('admin/process/edit_process.html');
	}

	function edit($cod) {
        $this->tal->code_process =  urldecode($cod);
        $this->tal->batches = $this->Participant->list_batches();
		$this->tal->display('admin/process/edit_process.html');
	}

	function view($cod) {
        $process = $this->Processes->getByCode(urldecode($cod));
        if(!empty($process)) {
            $result = array(
                'process' => $process
            );
            $this->tal->result = $result;
            $this->tal->ranking = $this->ranking_general($process['code']);
            $this->tal->display('admin/process/view.html');
        } else {
            $this->tal->error = "Such process doesn't exist.";
            $this->listing();
        }
	}

    function insert_process() {
        if($this->validate()) { 
            $process_id = $this->Processes->insert($_GET, $this->session);
            header('Content-Type: application/json');
            $result = array(
                'id' => $process_id
            );
            if(isset($process_id, $_GET['code'])) {
                $code_process = trim(strtoupper($_GET['code']));
                try {
                    if(isset($_GET['theme'])) {
                        $this->Resource->copy_process_resource_repository($code_process, $_GET['theme']);
                    } else {
                        $this->Resource->create_process_resource_repository($code_process);
                    }
                } catch(Exception $e) {
                    $result['error'] = $e;
                }
            }
 
            print(json_encode($result));
        }
    }

    function update_process() {
        if($this->validate()) { 
            $result = $this->Processes->update($_GET, $this->session);
            header('Content-Type: application/json');
            print($result);
        }
    }

    function remove_process($process_code) {
        if(isset($process_code)) {
            $this->Processes->remove($process_code);
            try {
                $this->Resource->remove_process_repository($process_code);
            } catch(Exception $e) {
            }
        }
        $this->listing();
    }


    function add_activity() {
        $activity = array(
            'code' => strtoupper($_GET['code']),
            'order' => $_GET['order'],
            'description' => $_GET['description'],
            'super_type' => $_GET['super_type'],
            'datapath' => $_GET['datapath'],
            //'filename' => $_GET['filename'],
            'type' => $_GET['type']
        );
        if(isset($_GET['question'])) {
            $activity['question'] = $_GET['question'];
        }
        if(isset($_GET['answers'])) {
            $activity['answers'] = json_decode($_GET['answers']);
        }

        if(isset($_GET['text'])) {
            $activity['text'] = json_decode($_GET['text']);
        }
        switch ($activity['super_type']) {
            case 'content':
                break;
            case 'especial':
                if($activity['type'] == 'VIDEO' || $activity['type'] == 'FLASH') {
                    $activity['media_url'] = $activity['datapath'];
                }
                $activity['description'] = $activity['type'];
                break;
        }

        $result = $this->Processes->add_activity(strtoupper($_GET['code_process']), $activity,  $this->session);
        header('Content-Type: application/json');
        print(json_encode($activity));
    }

    function get_activity($activity_code) {
        $activity = $this->Processes->get_activity($activity_code);
        print_r($activity);
    }

    function get_activities() {
        $activities = $this->Processes->get_activities($_GET['process_code']);
        header('Content-Type: application/json');
        print(json_encode($activities));
    }
       
    function remove_activity() {
        $this->Processes->remove_activity($_GET['process_code'], $_GET['activity_code'],  $this->session);
        header('Content-Type: application/json');
        print('{"result":"ok"}');
    }

    function reorder_activities() {
        $activities_order = json_decode($_GET['activities_order']);
        $result = $this->Processes->reorder_activities(strtoupper($_GET['code_process']), $activities_order,  $this->session);
        header('Content-Type: application/json');
        print(json_encode(array('result'=> 'OK')));
     }

    function update_activity() {
        $activity = array(
            'code' => strtoupper($_GET['code']),
            'order' => $_GET['order'],
            'description' => $_GET['description'],
            'super_type' => $_GET['super_type'],
            'datapath' => $_GET['datapath'],
            'type' => $_GET['type']
        );

        if(isset($_GET['question'])) {
            $activity['question'] = $_GET['question'];
        }
        if(isset($_GET['answers'])) {
            $activity['answers'] = json_decode($_GET['answers']);
        }

        if(isset($_GET['text'])) {
            $activity['text'] = json_decode($_GET['text']);
        }
        switch ($activity['super_type']) {
            case 'content':
                break;
            case 'especial':
                $activity['description'] = $activity['type'];
                break;
        }

        $result = $this->Processes->update_activity(strtoupper($_GET['code_process']), $activity,  $this->session);
        header('Content-Type: application/json');
        print(json_encode($activity));
    }

    function get_process() {
        header('Content-Type: application/json');
        if(isset($_GET['code'])) { 
            $process = $this->Processes->getByCode($_GET['code']);
            if(!empty($process)) {
                if(isset($process['batch_id'])) {
                    $process['batch_id'] = (string)$process['batch_id'];
                }
                $result = array(
                    'process' => $process
                );
                print(json_encode($result));
            } else {

            }
        }
    }

    function validate() {
        return true;
    }

    function ranking_general($process_code) {
        $process = $this->Processes->getByCode(urldecode($process_code));

        $results = $this->Participant->ranking($process);

        $index = 1;
        $order_results = array();
        foreach($results as $item) {
           array_push($order_results, array_merge($item, array('order'=>$index)));
           $index++; 
        }
        return $order_results;
    }

    function view_participant_performance($process_code, $participant_id) {
        $process = $this->Processes->getByCode(urldecode($process_code));

        $results = $this->Participant->participant_performance($process, $participant_id);
        $participant = $this->Participant->get_participant($participant_id);

        $this->tal->results = $results;
        $this->tal->participant = $participant;

        $this->tal->display('admin/process/view_participant.html');

    }

}

