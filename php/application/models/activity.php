<?php 
class Activity extends CI_Model {
    private $collection_name = 'ACTIVITY';

    function __construct() {
        parent::__construct();
    }

    function listAll() {
        return $this->mongo_db->order_by(array('code' => 'ASC'))->get($this->collection_name);
    }

    function getByCode($code) {
        $result = $this->mongo_db-> where(array(
            'code' => $code
        ))->get($this->collection_name);
        if(count($result) >= 1) {
            return $result[0];
        }
        return $result;
    }

    function insert($process, $session) {
        $process_default = array(
            'code' => '',
            'summary' => '',
            'type' => '',
            'date_start' => '',
            'date_finish' => '',
            'state' => 'INACTIVE',
            'created_by' => $session->userdata('user_id')
        );

        $process = array_merge($process_default, $process);
        $process['code'] = strtoupper($process['code']);
        $process_id = $this->mongo_db->insert($this->collection_name, $process);
        return $process_id;
    }

    function update($process, $session) {
        $persisted_process = array_merge($this->getByCode($process['code']), array(
            'modificado_por' => $session->userdata('user_id')
        ));
        $process = array_merge($persisted_process, $process);
        unset($process['_id']);
        $process_id = $this->mongo_db->where(array('code'=>$process['code']))->update($this->collection_name, $process);
        return $process_id;
    }

    function add_activity($process_code, $activity, $session) {

        $this->mongo_db->where(array('code'=>$process_code))->inc(array('cantidad_activities' => 1))->update($this->collection_name);
        $this->mongo_db->where(array('code'=>$process_code))->push('activities', $activity)->update($this->collection_name);
    }

    function remove_activity($activity_code, $session) {
        $query = array("code" => 'COD7');

        $command = array( '$pull' => 
            array( "activities" => 
                array( '$elemMatch' =>
                    array( "code" => $activity_code)
                )
            )
        );

        print(json_encode($command));
        $this->db->PROCESOS->update($query, $command);

    }

    function remove() {

    }

    function reorder_activities($process_code, $activities_order, $session) {
        foreach($activities_order as $activity) {
            $activity_persisted = $this->get_activity($activity->code);
            print_r($activity);
            $this->mongo_db->pull('activities', array('code'=>$activity->code))->update($this->collection_name);
            $activity_persisted['order'] = $activity->order;
            $this->mongo_db->where(array('code'=>$process_code))->push('activities', $activity_persisted)->update($this->collection_name);
        }
    }

    function update_activity($process_code, $activity, $session) {
        $activity_code = $activity['code'];

        $activity_old = get_activity($process_code, $activity_code);
        $activity_to_persist = array_merge($activity_old, $activity);
        $this->mongo_db->where(array('code'=>$process_code))->pull('activities', array('code'=>$activity_code))->update($this-collection_name);
        $this->mongo_db->where(array('code'=>$process_code))->push('activities', $activity_to_persist)->update($this->collection_name);
    }

    function get_activity($activity_code) {
        $result = $this->mongo_db->where(array('activities.code'=>$activity_code))->get($this->collection_name);
        if(count($result) >= 1) {
            $activity_found = NULL;
            foreach($result[0]['activities'] as $activity) {
                if($activity['code'] == $activity_code) {
                    $activity_found = $activity;
                }
            } 
            return $activity_found;
        }
        return $result;
    }


    function get_next_activity($activities, $current_activity_code) {
        if(!isset($current_activity_code)) {
            return $activities[0];
        }
        $_next = false;
        $next_activity = NULL;
        usort($activities, function ($a, $b) {
            if($a['order'] > $b['order']) return 1;
            if($a['order'] < $b['order']) return -1;
            return 0;
        });

        foreach($activities as $activity) {
            if($_next) {
                $next_activity = $activity;
            }
            $_next = ($activity['code'] == $current_activity_code);
        } 
        return $next_activity;
    }

}
