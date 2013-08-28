<?php

/**
 * User bulk editor local plugin version information
 *
 * @package    local
 * @subpackage user_bulk_editor
 * @copyright  2012 Andrew "Kama" (kamasutra12@yandex.ru) 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');

class user_bulk_editor_action_form extends moodleform {
    function definition() {
        global $DB, $CFG;

        $mform =& $this->_form;
/*        $fields = array('username'  => 'username',
                        'email'     => 'email',
                        'firstname' => 'firstname',
                        'lastname'  => 'lastname',
                        'idnumber'  => 'idnumber',
                        'institution' => 'institution',
                        'department' => 'department',
                        'phone1'    => 'phone1',
                        'phone2'    => 'phone2',
                        'city'      => 'city',
                        'url'       => 'url',
                        'icq'       => 'icq',
                        'skype'     => 'skype',
                        'aim'       => 'aim',
                        'yahoo'     => 'yahoo',
                        'msn'       => 'msn',
                        'country'   => 'country');*/

        $fields = array('suspended' => 'suspended',
                        'institution' => 'institution',
                        'department' => 'department',
                        'city'      => 'city',
                        'country'   => 'country');



        if ($extrafields = $DB->get_records('user_info_field')) {
            foreach ($extrafields as $n=>$v){
                $fields['profile_field_'.$v->shortname] = 'profile_field_'.$v->shortname;
            }
        }

        $objs = array();
        $objs[] =& $mform->createElement('select', 'field', null, $fields);
        $objs[] =& $mform->createElement('submit', 'doaction', get_string('go'));
        $mform->addElement('group', 'actionsgrp', get_string('selectfield', 'local_user_bulk_editor'), $objs, ' ', false);
    }
}

class user_bulk_editor_change_form extends moodleform {
    function definition() {
        global $DB, $SESSION;
        $mform =& $this->_form;

        $field = $this->_customdata['field'];
        $users = $SESSION->bulk_users;
        $isCustom = false;
        $fld_type = 'text';
        $max_change = 20;
        $supported_types = array('text', 'menu', 'datetime', 'checkbox');

        if (!$field) {
            return;
        };

        if (preg_match('/(?<=profile_field_)[\W\w]*/', $field, $cpf) !== FALSE){
            if (!empty($cpf)) {
                $field = $cpf[0];
                $isCustom = true;
            };
        };

        // Get data
        list($insql, $inparams) = $DB->get_in_or_equal($users);
        if (!$isCustom) {
            $result = $DB->get_fieldset_sql("SELECT DISTINCT $field FROM {user} WHERE id $insql", $inparams);
        } else {
            if ($field_info = $DB->get_record('user_info_field', array('shortname' => $field))){
                $params = array_merge(array($field_info->id), $inparams);
                $fld_type = $field_info->datatype;
                $result = $DB->get_fieldset_sql("SELECT DISTINCT data FROM {user_info_data} WHERE fieldid = ? AND userid $insql", $params);
            };
        };

        // Generate form elements for new data

            if (count($result) > $max_change) {
                $cnt = $max_change;
                $attributes = array('optional' => true);
            } else {
                $cnt = count($result);
                $attributes = array('disabled' => 'disabled');
            };

            if (!in_array($fld_type, $supported_types)) {
                $mform->addElement('html', 'This function is not available for type '.$fld_type);
                return;
            };

            $SESSION->ube_orig = $result;

        $mform->addElement('hidden', 'custom', $isCustom);
        $mform->setType('custom', PARAM_INT);
        $mform->addElement('hidden', 'fldtype', $fld_type);
        $mform->setType('fldtype', PARAM_TEXT);
        $mform->addElement('hidden', 'count', $cnt);
        $mform->setType('count', PARAM_INT);

        if ($fld_type == 'checkbox') {
            $chk_radio = array();
            $chk_radio[] = &$mform->createElement('radio', 'chk_all', '', get_string('check', 'local_user_bulk_editor'), 2);
            $chk_radio[] = &$mform->createElement('radio', 'chk_all', '', get_string('uncheck', 'local_user_bulk_editor'), 3);
            $chk_radio[] = &$mform->createElement('radio', 'chk_all', '', get_string('reverse', 'local_user_bulk_editor'), 4);
            $mform->addGroup($chk_radio, 'radio_grp', get_string('replaceall', 'local_user_bulk_editor'), array(' '), false);
            $this->add_action_buttons($cancel = true, $submitlabel=get_string('submit'));
            return;
        } else {
            $mform->addElement('checkbox', 'chk_all', get_string('replaceall', 'local_user_bulk_editor'));
            if ($fld_type == 'menu') {
                $param1 = explode("\n", $field_info->param1);
                $options[''] = get_string('choose').'...';
                foreach($param1 as $key => $option) {
                    $options[$key] = format_string($option);//multilang formatting
                }

                $mform->addElement('select', 'repl_all', get_string('to', 'local_user_bulk_editor'), $options);
//                $mform->setType('repl_all', PARAM_RAW);

            } else if ($fld_type == 'datetime') {
                $attributes_dt = array(
                    'startyear' => $field_info->param1,
                    'stopyear'  => $field_info->param2,
                    'timezone'  => 99,
                    'applydst'  => true,
                    'optional'  => false
                );

                // Check if they wanted to include time as well
                if (!empty($field_info->param3)) {
                    $mform->addElement('date_time_selector', 'repl_all', get_string('to', 'local_user_bulk_editor'), $attributes_dt);
                    $mform->setType('repl_all', PARAM_INT);
                } else {
                    $mform->addElement('date_selector', 'repl_all', get_string('to', 'local_user_bulk_editor'), $attributes_dt);
                    $mform->setType('repl_all', PARAM_INT);
                }

            } else {
                $mform->addElement('text', 'repl_all', get_string('to', 'local_user_bulk_editor'));
                $mform->setType('repl_all', PARAM_RAW);
            };
        };

        if ($cnt < 2) {
            // Replace all function only
            $mform->setDefault('chk_all', 1);
            $this->add_action_buttons($cancel = true, $submitlabel=get_string('submit'));
            return;
        };

        for ($i=0;$i<$cnt;$i++){
// $rgroup[] = $mform->create
            $rgroup = array();
            // Add an element with specified type
            switch ($fld_type){
                case 'text':
                    $rgroup[1] = $mform->createElement('select', 'orig_'.$i, get_string('replace', 'local_user_bulk_editor', array('num' => $i)), $result, $attributes);
//                    $mform->setType('orig_'.$i, PARAM_TEXT);

                    $rgroup[2] = $mform->createElement('text', 'repl_'.$i, get_string('to', 'local_user_bulk_editor'));
                    $mform->setType('group_'.$i.'[repl_'.$i.']', PARAM_TEXT);
                  break;
                case 'menu';
                    $rgroup[1] = $mform->createElement('select', 'orig_'.$i, get_string('replace', 'local_user_bulk_editor', array('num' => "$i")), $result, $attributes);
                    //$mform->setDefault('orig_'.$i, $i);

                    $param1 = explode("\n", $field_info->param1);
                    $options[''] = get_string('choose').'...';
                    foreach($param1 as $key => $option) {
                        $options[$key] = format_string($option);//multilang formatting
                    }

                    $rgroup[2] = $mform->createElement('select', 'repl_'.$i, get_string('to', 'local_user_bulk_editor'), $options);
                    // $mform->setType('group_'.$i.'[repl_'.$i.']', PARAM_INT);
                  break;
                case 'datetime':
                    $date_result = array();
                    foreach ($result as $key => $val) {
                        $date_result[$key] = userdate($val);
                    };

                    $rgroup[1] = $mform->createElement('select', 'orig_'.$i, get_string('replace', 'local_user_bulk_editor', array('num' => "$i")), $date_result, $attributes);
                    //$mform->setDefault('orig_'.$i, $i);

                    $attributes_dt = array(
                        'startyear' => $field_info->param1,
                        'stopyear'  => $field_info->param2,
                        'timezone'  => 99,
                        'applydst'  => true,
                        'optional'  => false
                    );

                    // Check if they wanted to include time as well
                    if (!empty($field_info->param3)) {
                        $rgroup[2] = $mform->createElement('date_time_selector', 'repl_'.$i, get_string('to', 'local_user_bulk_editor'), $attributes_dt);
                    } else {
                        $rgroup[2] = $mform->createElement('date_selector', 'repl_'.$i, get_string('to', 'local_user_bulk_editor'), $attributes_dt);
                    }

                    $mform->setType('group_'.$i.'[repl_'.$i.']', PARAM_INT);
                    $mform->setDefault('group_'.$i.'[repl_'.$i.']', time());

                  break;
                default:
                    $mform->addElement('html', 'Error / type is not defined');
                    return;
                  break;
            };
            if (count($result) > $max_change) {
                $mform->disabledIf('group_'.$i.'[orig_'.$i.']', 'chk_all', 'checked');
            };
            $mform->addGroup($rgroup, 'group_'.$i, get_string('replace', 'local_user_bulk_editor', array('num' => "$i")));
            $mform->setDefault('group_'.$i.'[orig_'.$i.']', $i);
            $mform->disabledIf('group_'.$i.'[repl_'.$i.']', 'chk_all', 'checked');
        };
        // Buttons
        $this->add_action_buttons($cancel = true, $submitlabel=get_string('submit'));
    }
}
