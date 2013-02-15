<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Participants extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Processes');
        $this->load->model('Participant');
        $this->load->helper('file');
        $this->load->helper('url');
        if (!$this->session->userdata('user_id')) {
            redirect('admin/login');
        }
    }

	function index() {
        $this->listing();
    }

	function listing() {
        $participant_batches = $this->Participant->list_batches();
        $this->tal->batches = $participant_batches;
		$this->tal->display('admin/participants/participants_batches.html');
	}

	function import() {
        $this->tal->phase_determine_format = FALSE;
		$this->tal->display('admin/participants/batch_import.html');
	}

	function upload_batch() {
        if(isset($_FILES['batch_file']) && ($_FILES['batch_file']['error'] == UPLOAD_ERR_OK)) {
            $batch_path = APPPATH.'../static/resources/projects/lotes_participantes/'.basename($_FILES['batch_file']['name']);
            $batch_name = $_FILES['batch_file']['name'];
            if(move_uploaded_file($_FILES['batch_file']['tmp_name'], $batch_path)) {
                $this->preprocess_batch($batch_name, $batch_path, $_POST);
            } else {
                $this->tal->phase_determine_format = FALSE;
                $this->tal->display('admin/participants/batch_import.html');
                $this->tal->error = $_FILES['batch_file']['error'];
            }
         } else {
            $this->tal->phase_determine_format = FALSE;
            $this->tal->display('admin/participants/batch_import.html');
            $this->tal->error = $_FILES['batch_file']['error'];
         }
	}

    function obtain_pattern_from_delimiters($delimiters) {
        $delimiter_chars = '';
        $delimiters_map = $this->get_delimiters_map();
        if(is_array($delimiters)) {
            foreach( $delimiters as $delimiter) {
               $delimiter_chars = $delimiter_chars.$delimiters_map[$delimiter]; 
            }
        } else {
            $delimiter_chars = $delimiter_chars.$delimiters_map[$delimiters]; 
        }
        return '/['.$delimiter_chars.']/';
    }

    function batch_show_results($batch_id) {
        $batch = $this->Participant->get_batch_by_id($batch_id);
        if(isset($batch)) {
            $participants = $batch['participants'];
            $descriptors = $batch['meta_definitions'];
            $records = array();
            foreach($participants as $participant) {
                $record = array();
                $this->array_keys_multi($participant, $record);
                array_push($records, $record);
            }

            $this->tal->batch_id = $batch_id;
            $this->tal->batch = $batch;
            $this->tal->records = $records;
            $this->tal->descriptors = $descriptors;
            $this->tal->display('admin/participants/batch_result.html');
        } else {
            print_r($batch);
        }
    }

    function transform_plain_participants($participants) {
        $records = array();
        foreach($participants as $participant) {
            $record = array();
            $this->array_keys_multi($participant, $record);
            array_push($records, $record);
        }
        return $records;
    }

    function batch_preview() {
        $batch_id = $_POST['batch_id'];
        $definitions = '';
        $has_delimiters = FALSE;
        if(isset($_POST['batch_delimiter'])) {
            $delimiters = $_POST['batch_delimiter'];
            $has_delimiters = TRUE;
        }
        $batch = $this->Participant->get_batch_by_id($batch_id);
        if(isset($batch)) {
            $file_path = $batch['file_path'];
            if($has_delimiters) {
                $pattern = $this->obtain_pattern_from_delimiters($delimiters);
            }

            if($fh = fopen($file_path, 'r')) {
                $lines = 0;
                $lines_test = array();
                while (! feof($fh) && $lines < 4) {
                    if($line_read = fgets($fh)) {
                        $line_read = utf8_encode($line_read);
                        $lines_test[$lines] = $has_delimiters ? preg_split($pattern, $line_read) : $line_read;
                        $lines++;
                    }
                }
                $this->tal->lines = $lines_test;
                $this->tal->has_delimiters = $has_delimiters;
                $this->tal->display('admin/participants/batch_preview.html');
            }
        }

    }

    function get_batch() {
        $batch_id = $_GET['batch_id'];
        $batch = $this->Participant->get_batch_by_id($batch_id);
        header('Content-Type: application/json');
        print(json_encode($batch));
    }

    function associate_batch_process() {
        $batch_id = $_GET['batch_id'];
        $process_code = $_GET['process_code'];
        $process = $this->Processes->getByCode(urldecode($process_code));
        $batch = $this->Participant->get_batch_by_id($batch_id);

        $this->Participant->associate_batch_process($batch, $process, $this->session);
        header('Content-Type: application/json');
        print(json_encode(array('result' => 'ok')));
    }

    function set_format_batch() {
        $batch_id = $_POST['batch_id'];
        $delimiters = '';
        $has_delimiters = FALSE;
        $head_definition = FALSE;
        $max_fields = 0;
        $meta_definitions = array();
        if(isset($_POST['batch_delimiter'])) {
            $delimiters = $_POST['batch_delimiter'];
            $has_delimiters = TRUE;
        }

        if(isset($_POST['batch_first_head'])) {
            $head_definition = TRUE;
        }

        $batch = $this->Participant->get_batch_by_id($batch_id);

        if(isset($batch)) {
            $file_path = $batch['file_path'];
            if($has_delimiters) {
                $pattern = $this->obtain_pattern_from_delimiters($delimiters);
            }

            if($fh = fopen($file_path, 'r')) {
                $lines = 0;
                $lines_test = array();
                while (! feof($fh) && $lines < 4) {
                    if($line_read = fgets($fh)) {
                        $line_read = utf8_encode($line_read);
                        $line_read = preg_replace('/\s\s+/', '', $line_read);
                        $fields = $has_delimiters ? preg_split($pattern, $line_read) : $line_read;
                        if($lines == 0) {
                            $meta_definitions = array();
                            if($head_definition) {
                                for($item = 0, $size = count($fields); $item < $size; $item++) {
                                    $column_name = trim($fields[$item]);
                                    if($column_name != '') {
                                        $meta_definitions[$item] = array(
                                            '_id' => new MongoId(),
                                            'name' => $column_name,
                                            'describe' => FALSE,
                                            'login' => FALSE,
                                            'order' => $item
                                        );
                                    }
                                }
                            } else {
                                for($item = 0, $size = count($fields); $item < $size; $item++) {
                                    $column_name = 'columna_'.$item;
                                    if($column_name != '') {
                                        $meta_definitions[$item] = array(
                                            '_id' => new MongoId(),
                                            'name' => $column_name,
                                            'describe' => FALSE,
                                            'login' => FALSE,
                                            'order' => $item
                                        );
                                    }
                                }
                            }
                        }
                        $lines_test[$lines] = $fields;
                        $lines++;
                        $count_fields = count($fields);
                        if($count_fields > $max_fields) {
                            $max_fields = $count_fields;
                        }
                    }
                }

                $this->Participant->update_batch($batch_id, $batch, array(
                    'has_delimiters' => $has_delimiters,
                    'has_head' => $head_definition,
                    'delimiters' => $delimiters,
                    'count_fields' => $max_fields,
                    'meta_definitions' => $meta_definitions
                ));

                $this->tal->lines = $lines_test;
                $this->tal->batch_id = $batch_id;
                $this->tal->has_delimiters = $has_delimiters;
                $this->tal->columns_definition = $meta_definitions;
                $this->tal->display('admin/participants/batch_structure.html');
            }
        }
    }

    function preprocess_batch($file_name, $file_path, $options) {
        $result = $this->Participant->init_batch($file_name, $file_path, $options);
        $batch_id = $result['_id'];
        if($fh = fopen($file_path, 'r')) {
            $lines = 0;
            $lines_test = array();
            while (! feof($fh) && $lines < 4) {
                if($line_read = fgets($fh)) {
                    $line_read = utf8_encode($line_read);
                    $lines_test[$lines] = $line_read;
                    $lines++;
                }
            }
            $this->tal->lines = $lines_test;
            $this->tal->phase_determine_format = TRUE;
            $this->tal->delimiters = $this->get_delimiters_definition();
            $this->tal->batch_id = $batch_id;
        }
        $this->tal->display('admin/participants/batch_format.html');
    }

    function batch_remove($batch_id) {

        $batch = $this->Participant->get_batch_by_id($batch_id);

        if(isset($batch)) {
            $file_path = $batch['file_path'];
            if(is_file($file_path)) {
                unlink($file_path);
            }
            $this->Participant->batch_remove($batch_id);
        }
        $this->listing();
    }

    function process_batch() {
        $batch_id = $_POST['batch_id'];
        $delimiters = '';
        $has_delimiters = FALSE;
        $head_definition = FALSE;
        $max_fields = 0;

        $group_identify_fields = isset($_POST['group_identify_fields']);

        $meta_definitions = array();

        $batch = $this->Participant->get_batch_by_id($batch_id);

        if(isset($batch)) {
            $has_delimiters = $batch['has_delimiters'];
            $head_definition = $batch['has_head'];
            $delimiters = $batch['delimiters'];
            $count_fields = $batch['count_fields'];
            $old_meta_definitions = $batch['meta_definitions'];
            $meta_definitions = array();
            $describe_column = $_POST['name'];
            foreach($old_meta_definitions as $definition) {
                $order = $definition['order'];
                array_push($meta_definitions, array_merge($definition, array(
                    'name' => $_POST['name_'.$order],
                    'describe' => ($order == $describe_column),
                    'login' => isset($_POST['login_'.$order])
                )));
            }
            $file_path = $batch['file_path'];

            if($has_delimiters) {
                $pattern = $this->obtain_pattern_from_delimiters($delimiters);
            }

            if($fh = fopen($file_path, 'r')) {
                $lines = 0;
                $records = array();
                while (! feof($fh)) {
                    if($line_read = fgets($fh)) {
                        if(!($lines == 0 && $head_definition)) {
                            $line_read = utf8_encode($line_read);
                            $line_read = preg_replace('/\s\s+/', '', $line_read);
                            $line_read = preg_replace('/\n*/', '', $line_read);
                            $fields = $has_delimiters ? preg_split($pattern, $line_read) : $line_read;
                            array_push(
                                $records, 
                                $this->Participant->create_record($batch_id, $fields, $meta_definitions)
                            );
                        }
                        $lines++;
                    }
                }

                $this->Participant->update_batch($batch_id, $batch, array(
                    'participants' => $records,
                    'meta_definitions' => $meta_definitions,
                    'count' => count($records),
                    'group_identify_fields' => $group_identify_fields
                ));

                $this->listing();
                //$this->batch_show_results($batch_id, TRUE);
            }
        }
    }

    function add_record($batch_id) {
        $this->Participant->add_record($batch_id, $_GET);
        header('Content-Type: application/json');
        print(json_encode(array('result'=>'OK')));
    }

    function update_record($batch_id) {
        $success = $this->Participant->update_record($batch_id, $_GET);
        header('Content-Type: application/json');
        print(json_encode(array('result'=> $success ? 'OK' : 'ERROR')));
    }

    function remove_record($batch_id) {
        $this->Participant->remove_record($batch_id, $_GET);
        header('Content-Type: application/json');
        print(json_encode(array('result'=>'OK')));
    }

    function batch_finish() {
        $batch_id = $_POST['batch_id'];

        $this->listing();
    }

    function get_delimiters_map() {
        return array(
            'COMA' => ',',
            'PCOMA' => ';',
            'TAB' => '\t',
            'SPACE' => ' ',
        );
 
    }

    function get_delimiters_definition() {
        return array(
            array(
                'label' => 'Colon', 'value' => 'COMA', 'char' => ','
            ),
            array(
                'label' => 'Semicolon', 'value' => 'PCOMA', 'char' => ';'
            ),
            array(
                'label' => 'Tab', 'value' => 'TAB', 'char' => '\t'
            ),
            array(
                'label' => 'Space', 'value' => 'SPACE', 'char' => ' '
            )
        );
    }

    function validate() {
        return true;
    }

    function array_keys_multi($array,&$vals) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->array_keys_multi($value,$vals);
            } else {
                $vals[] = $value; 
            }
        }
        return $vals;
    }

}

