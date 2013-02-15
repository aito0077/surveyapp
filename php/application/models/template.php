<?php 
class Template extends CI_Model {

    private $collection_name = 'TEMPLATES';
    function __construct() {
        parent::__construct();
    }

    function generate_templates($process_code, $tal) {
        $process = $this->Proceso->getByCode(urldecode($process_code));

        $has_generations = isset($process['template_generation']);
        if($has_generations) {
            $this->mongo_db->where(array('process'=>$process_code))->delete_all($this->collection_name);
        }

        if($process['tipo'] == 'contenido') {

            $tal->process = $process;
            $template_name = 'workflow/wrapper/content_main.html';
            $result_html = $this->tal->display($template_name, TRUE);
            $to_persist = array(
                'process' => $process_code,
                'html' => $result_html,
                '$inc' => array(
                    'version' => 1
                )
            );
            $this->mongo_db->insert($this->collection_name, $to_persist);

        } else {

            $activities = $process['activities'];
            foreach($activities as $activity) {
                $activity_code = $activity['codigo'];
                $this->tal->process = $process;
                $this->tal->activity = $activity;
                $this->tal->fragment = strtolower($activity['clase'].'_'.$activity['tipo']);
                //$template_name = $this->get_activity_template($activity);
                $template_name = 'workflow/wrapper/fragments/content_each.html';
                $result_html = $this->tal->display($template_name, TRUE);
                $to_persist = array(
                    'process' => $process_code,
                    'activity' => $activity_code,
                    'html' => $result_html,
                    'version' => 1
                    /*
                    '$inc' => array(
                        'version' => 1
                    )
                    */
                );
                $this->mongo_db->insert($this->collection_name, $to_persist);
            }

            $this->tal->process = $process;
            $this->tal->activity = array(
                'codigo' => '' 
            );
            $this->tal->fields = $process['identity_fields'];
            $this->tal->fragment = 'login';

            //$template_name = 'workflow/steps/login.html';
            $template_name = 'workflow/wrapper/fragments/content_each.html';
            $result_html = $this->tal->display($template_name, TRUE);
            $to_persist = array(
                'process' => $process_code,
                'activity' => 'login',
                'html' => $result_html,
                'version' => 1
                /*
                '$inc' => array(
                    'version' => 1
                )
                */
            );
 
            $this->mongo_db->insert($this->collection_name, $to_persist);
        }
	}

    function get_activity_template($activity) {
        $activity_class = $activity['clase'];
        $activity_type = $activity['tipo'];
        return strtolower('workflow/steps/'.$activity_class.'_'.$activity_type.'.html');
    }

    function get_template($process_code, $activity_code) {

        $result = $this->mongo_db-> where(array(
             'process' => $process_code,
             'activity' => $activity_code
        ))->get($this->collection_name);
        return $result;
    }

}
