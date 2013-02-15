<?php 
class Processes extends CI_Model {
    private $collection_name = 'PROCESSES';
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
            'bases' => array('text' => '', 'description' => ''),
            'created_by' => $session->userdata('user_id')
        );

        $process = array_merge($process_default, $process);
        $process['code'] = trim(strtoupper($process['code']));
        $process_id = $this->mongo_db->insert($this->collection_name, $process);
        return $process_id;
    }

    function update($process, $session) {
        $persisted_process = array_merge($this->getByCode($process['code']), array(
            'modify_by' => $session->userdata('user_id')
        ));

        if(isset($process['bases'], $process['bases']['text'])) {
            $process['bases']['text'] = json_decode($process['bases']['text']);
        }
        $process = array_merge($persisted_process, $process);

        unset($process['_id']);
        $process_id = $this->mongo_db->where(array('code'=>$process['code']))->update($this->collection_name, $process);
        return $process_id;
    }

    function remove($process_code) {
        $this->mongo_db->where(array('code'=>$process_code))->delete($this->collection_name);
    }

    function add_activity($process_code, $activity, $session) {

        if($activity['type'] != 'BASES') {
            $this->mongo_db->where(array('code'=>$process_code))->inc(array('activities_count' => 1))->update($this->collection_name);
            $this->mongo_db->where(array('code'=>$process_code))->push('activities', $activity)->update($this->collection_name);
        } else {
            $process_bases = array(
                'code' => $process_code,
                'bases' => $activity
            );
            $this->update($process_bases, $session);
        }
    }

    function remove_activity($process_code, $activity_code, $session) {
        $this->mongo_db->where(array('code'=>$process_code))->pull('activities', array('code' => $activity_code))->update($this->collection_name);
    }

    function reorder_activities($process_code, $activities_order, $session) {
        foreach($activities_order as $activity) {
            $this->mongo_db->where(array('activities.code'=>$activity->code))->set('activities.$.order', $activity->order)->update($this->collection_name);
        }
    }

    function update_activity($process_code, $activity, $session) {
        $activity_code = $activity['code'];

        $activity_old = $this->get_activity($process_code, $activity_code);
        $activity_to_persist = array_merge($activity_old, $activity);
        $this->mongo_db->where(array('code'=>$process_code))->pull('activities', array('code'=>$activity_code))->update($this->collection_name);
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

    function get_activities($process_code) {
        $activities = array();
        $process = $this->mongo_db->where(array('code'=>$process_code))->select(array('activities'))->get($this->collection_name);
        if(count($process) >= 1) {
            $activities = $process[0]['activities'];
        }
        return $activities;
    }



    function get_next_activity($activities, $participant, $current_activity = NULL, $result_activity) {
        $next_activity = NULL;
        usort($activities, function ($a, $b) {
            if($a['order'] > $b['order']) return 1;
            if($a['order'] < $b['order']) return -1;
            return 0;
        });

        if(!isset($current_activity) && count($participant['steps']) == 0 ) {
            return $activities[0];
        }

        foreach($activities as $activity) {
            if(!$this->activity_is_done($activity['code'], $participant, $current_activity, $result_activity)) {
                $next_activity = $activity;
                break;
            }
        } 
        return $next_activity;
    }

    function activity_is_done($activity_code, $participant, $current_activity, $result_activity) {
        $result = FALSE;
        foreach($participant['steps'] as $step) {
            if($step['activity_code'] == $activity_code) { 
                return TRUE;
            } else {
                if($current_activity == $activity_code && $result_activity) {
                    return TRUE;
                }
            }
        }
        return $result;
    }

}
