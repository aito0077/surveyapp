<?php 
class Participant extends CI_Model {
    private $collection_name = 'CANDIDATES';
    private $participant_collection = 'PARTICIPANT';
    private $process_collection = 'PROCESSES';

    function __construct() {
        parent::__construct();
    }

    function list_batches() {
        $batches = $this->mongo_db->get($this->collection_name);
        return $batches;
    }

    function get_batch_by_id($id) {
        $result = $this->mongo_db->where(array('_id' => new MongoId($id)))->get($this->collection_name);
        if(count($result) >= 1) {
            return $result[0];
        } else {
            return NULL;
        }
    }

    function update_batch($batch_id, $batch, $options) {
        $persisted_batch = array_merge($batch, $options);
        unset($persisted_batch['_id']);
 
        $this->mongo_db->where(array('_id' => new MongoId($batch_id)))->update($this->collection_name, $persisted_batch);
        return TRUE;
    }

    function batch_remove($batch_id) {
        $batch = $this->get_batch_by_id($batch_id);

        $this->mongo_db->where(array('_id' => new MongoId($batch_id)))->delete($this->collection_name);

        $processes = $this->mongo_db->where(array('batch_id' => new MongoId($batch_id)))->get($this->process_collection);

        foreach($processes as $process) {
            $this->mongo_db->where(array('code' => $process['code']))->unset_field('batch_id')->update($this->process_collection);
            $this->mongo_db->where(array('process' => $process['code']))->delete($this->participant_collection);
        }

        return TRUE;
    }

    function create_record($batch_id, $values, $definitions) {
        $record = array();
        foreach($definitions as $definition) {
            $index = $definition['order'];
            $record[$definition['name']] = $values[$index];
        }
        return $record;
    }

    function add_record($batch_id, $values) {
        $this->mongo_db->where(array('_id' => new MongoId($batch_id)))->push(array('participants' => $values))->update($this->collection_name);
        $this->mongo_db->where(array('_id' => new MongoId($batch_id)))->inc(array('count'=> 1))->update($this->collection_name);
        $processes = $this->mongo_db->where(array('batch_id' => new MongoId($batch_id)))->get($this->process_collection);

        $batch = $this->get_batch_by_id($batch_id);
        $definitions = $batch['meta_definitions'];
        
        foreach($processes as $process) {
            $record = array();
            $record['process'] = $process['code'];
            $record['steps'] = array();

            foreach($definitions as $field) {
                $field_name = $field['name'];
                if($field['describe']) {
                    $record['describe'] = $record[$field_name];
                }
            }  

            $record_to_persist = array_merge($values, $record);
            $this->mongo_db->insert($this->participant_collection, $record_to_persist);
        }
    }

    function get_identity_fields_from_definition($batch) {
        $identities = array();
        $definitions = $batch['meta_definitions'];
        foreach($definitions as $definition) {
            if($definition['login']) {
                array_push($identities, $definition['name']);
            }
        }
        return $identities;
    }

    function remove_record($batch_id, $values) {
        $batch = $this->get_batch_by_id($batch_id);
        $identities = $this->get_identity_fields_from_definition($batch);
        $query = array();
        foreach($identities as $identity) {
            $query[$identity] =  $values[$identity];
        }

        $this->mongo_db->where(array('_id' => new MongoId($batch_id)))->pull('participants', $query)->update($this->collection_name);
        $this->mongo_db->where(array('_id' => new MongoId($batch_id)))->inc(array('cantidad'=> -1))->update($this->collection_name);

        $processes = $this->mongo_db->where(array('batch_id' => new MongoId($batch_id)))->get($this->process_collection);

        foreach($processes as $process) {
            $process_query = array_merge($query, array('process' => $process['code']));
            $this->mongo_db->where($process_query)->delete($this->participant_collection);
        }
    }

    function update_activity($process_code, $activity, $session) {
        $activity_code = $activity['code'];
        $activity_old = $this->get_activity($process_code, $activity_code);
        $activity_to_persist = array_merge($activity_old, $activity);

        $this->mongo_db->where(array('code'=>$process_code))->pull('activities', array('code'=>$activity_code))->update($this->collection_name);
        $this->mongo_db->where(array('code'=>$process_code))->push('activities', $activity_to_persist)->update($this->collection_name);
    }

    function update_record($batch_id, $values) {
        $batch = $this->get_batch_by_id($batch_id);
        $identities = $this->get_identity_fields_from_definition($batch);

        $record_old = $this->get_batch_record($batch, $identities, $values);

        if($record_old == NULL) {
            return FALSE;
        }

        unset($values['id']);
        $record_to_persist = array_merge($record_old, $values);

        $query = array();
        foreach($identities as $identity) {
            $query[$identity] =  $values[$identity];
        }

        $this->mongo_db->where(array('_id' => new MongoId($batch_id)))->pull('participants', $query)->update($this->collection_name);
        $this->mongo_db->where(array('_id' => new MongoId($batch_id)))->push('participants', $record_to_persist)->update($this->collection_name);


        $processes = $this->mongo_db->where(array('batch_id' => new MongoId($batch_id)))->get($this->process_collection);

        foreach($processes as $process) {

            $process_query = array_merge($query, array('process' => $process['code']));
            $old_participant = $this->mongo_db->where($process_query)->get($this->participant_collection);
            unset($old_participant['_id']);
            $participant_to_persist = array_merge($old_participant, $values);
            $this->mongo_db->where(array('code'=>$process['code']))->update($this->collection_name, $participant_to_persist);
        }

        return TRUE;
    }

    function get_batch_record($batch, $identities, $values) {
        $record_found = NULL;
        foreach($batch['participants'] as $participant) {
            $find = FALSE;
            foreach($identities as $identity) {
                $find = ($participant[$identity] == $values[$identity]);
            }
            if($find) {
                $record_found = $participant;
            }
        }
        return $record_found;
    }

    function init_batch($file_name, $file_path, $options) {
        $batch = array(
            'file_name' => $file_name,
            'file_path' => $file_path,
            'description' => $options['batch_description'],
            'count' => 0
        );
        $batch_id = $this->mongo_db->insert($this->collection_name, $batch);
        return $batch;
    }

    function associate_batch_process($batch, $process, $session) {
        $batch_id = $batch['_id'];
        $process_code = $process['code'];
        $participants = array();
        $candidates = $batch['participants'];
        $definitions = $batch['meta_definitions'];
        $identity_fields = array();
        foreach($definitions as $definition) {
            if($definition['login']) {
                array_push($identity_fields, $definition);
            }
        }
        

        $this->mongo_db->where(array('process'=>$process_code))->delete_all($this->participant_collection);

        foreach($candidates as $candidate) {
            $record = array();
            $record['process'] = $process_code;
            $record['steps'] = array();
            foreach($definitions as $field) {
                $field_name = $field['name'];
                $record[$field_name] = $candidate[$field_name];   
                if($field['describe']) {
                    $record['describe'] = $record[$field_name];
                }
            }  
            $this->mongo_db->insert($this->participant_collection, $record);
        }

        $this->update_batch($batch_id, $batch, array(
            'code_process' => $process_code 
        ));

        $persisted_process = array_merge($process, array(
            'batch_id' => $batch_id,
            'modify_by' => $session->userdata('user_id'),
            'identity_fields' => $identity_fields
        ));
        $process = array_merge($process, $persisted_process);

        unset($process['_id']);
        $this->mongo_db->where(array('code'=>$process_code))->update($this->process_collection, $process);

    }


    function get_participant($participant_id) {
        $participant = $this->mongo_db->where(array('_id' => new MongoId($participant_id)))->limit(1)->get($this->participant_collection);
        return $participant[0];
    }

    function identify_participant($process_code, $values_map) {
        $process = $this->Processes->getByCode(urldecode($process_code));
        $identity_fields = $process['identity_fields'];
        $filter = array();
        foreach($identity_fields as $field) {
            $filter[$field['name']] = $values_map[$field['name']];
        }
        return $this->mongo_db->where($filter)->limit(1)->get($this->participant_collection);
    }


    function get_step($process_code, $activity_code, $participant_id) {
        $participant = $this->mongo_db->where(array(
            '_id' => new MongoId($participant_id)
        ))->get($this->participant_collection);
        $steps = $participant[0]['steps'];
        $result = array();
        foreach($steps as $step) {
            if($step['activity_code'] = $activity_code) {
                $result = $step;
                break;
            }
        }

        return $result;

    }

    function save_value($process_code, $activity_code, $participant_id, $resultado, $time_spent) {
        //$this->mongo_db->where(array('activities.code'=>$activity->code))->set('activities.$.order', $activity->order)->update($this->collection_name);

        $value_actividad = array(
            'activity_code' => $activity_code,
            'timestamp' => $this->mongo_db->date(),
            'time_spent' => $time_spent
        );
        if(isset($resultado)) {
            $value_actividad['activity_value'] = $resultado;
        }
        $this->mongo_db->where(array('_id' => new MongoId($participant_id)))->push('steps', $value_actividad)->update($this->participant_collection);

    }


    function ranking($process) {
        $participants = $this->mongo_db->where(array('process' => $process['code']))->get($this->participant_collection);
        $activities = $process['activities'];
        $responses = array();
        foreach($activities as $activity) {
            if(isset($activity['answers'])) {
                $answers = $activity['answers']; 
                foreach($answers as $answer) {
                    if($answer['right']) {
                        $responses[$activity['code']] = $answer['value'];
                        break;
                    }
                }
            }
        }
        $results = array();
        foreach($participants as $participant) {
            $ranking = array_reduce($participant['steps'], function($result, $item) { 
                if(isset($item['time_spent'])) {
                    $result['time_spent'] += $item['time_spent']; 
                }
                $activity_code = $item['activity_code'];
                if(array_key_exists($activity_code, $result['responses'])) {
                    if($result['responses'][$activity_code] == $item['activity_value']) {
                        $result['answers_rights'] += 1; 
                    }
                }
                $result['answered_amount'] += 1; 
                return $result; 
            }, array(
                'responses' => $responses,
                'participant' => $participant['_id'],
                'id' => $participant[$process['identity_fields'][0]['name']],
                'describe' => $participant['describe'],
                'answered_amount' => 0,
                'answers_rights' => 0,
                'time_spent' => 0
            )); 
            array_push($results, array(
                'participant_id' => $ranking['participant'],
                'describe' => $ranking['describe'],
                'id' => $ranking['id'],
                'time_spent' => $ranking['time_spent'],
                'time' => $this->millis_to_seconds($ranking['time_spent']),
                'answered_amount' => $ranking['answered_amount'],
                'answered_amount_correct' => $ranking['answers_rights']
            ));     
        }
        usort($results, function ($b, $a) {
            if($a['answered_amount'] > $b['answered_amount']) return 1;
            if($a['answered_amount'] < $b['answered_amount']) return -1;
            if($a['answered_amount_correct'] > $b['answered_amount_correct']) return 1;
            if($a['answered_amount_correct'] < $b['answered_amount_correct']) return -1;
            if($a['time_spent'] > $b['time_spent']) return -1;
            if($a['time_spent'] < $b['time_spent']) return 1;
            return 0;
        });

        return ($results);

    }

    function participant_performance($process, $participant_id) {
        $participant = $this->mongo_db->where(array( '_id' => new MongoId($participant_id)))->get($this->participant_collection);
        $activities = $process['activities'];
        $result = array();

        foreach($activities as $activity) {
            $activity_record = array();
            foreach($participant[0]['steps'] as $step) {
                if($step['activity_code'] == $activity['code']) {
                    if(isset($step['activity_value'])) {
                        $activity_record['value'] = $step['activity_value'];
                    }
                    $activity_record['time_milis'] = $step['time_spent'];
                    $activity_record['time'] = $this->millis_to_seconds($step['time_spent']);
                    break;
                }
            }
            $activity_record['code'] = $activity['code'];
            $activity_record['order'] = $activity['order'];
            if(isset($activity['answers'])) {
                $activity_record['step'] = $activity['description'];
                $answers = $activity['answers']; 
                foreach($answers as $answer) {
                    if($answer['right']) {
                        $activity_record['answer_right'] = $answer['value'];
                        $activity_record['code_answer_right'] = $answer['code'];
                        $activity_record['do_right'] = (isset($answer['value'], $activity_record['value']) && $answer['value'] == $activity_record['value']) ? TRUE: FALSE;
                        break;
                    }
                }
            } else {
                $activity_record['step'] = '-';
                $activity_record['answer_right'] = '-';
                $activity_record['code_answer_right'] = '-';
                $activity_record['do_right'] = FALSE;
                $activity_record['value'] = '-';

            }
            if(!isset($activity_record['value'])) {
                $activity_record['value'] = '-';
            }
            if(!isset($activity_record['time'])) {
                $activity_record['time']  = array(
                    'seconds' => '-',
                    'minutes' => '-'
                );
            }
            array_push($result, $activity_record);
        }
        return $result; 
    }

    function millis_to_seconds($input) {
        $uSec = $input % 1000;
        $input = floor($input / 1000);
        $seconds = $input % 60;
        $input = floor($input / 60);
        $minutes = $input % 60;
        $input = floor($input / 60); 

        return array(
            'millis' => $uSec,
            'seconds' => ($seconds < 10 ? '0'.$seconds : $seconds),
            'minutes' => ($minutes < 10 ? '0'.$minutes: $minutes)
        );
    }
}



