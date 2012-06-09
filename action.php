<?php

/**
 * User bulk editor local plugin version information
 *
 * @package    local
 * @subpackage user_bulk_editor
 * @copyright  2012 Andrew "Kama" (kamasutra12@yandex.ru) 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/user_bulk_editor/lib.php');

$field = required_param('field', PARAM_RAW);
$return = $CFG->wwwroot.'/local/user_bulk_editor/index.php';

if (!isset($SESSION->bulk_users) || empty($SESSION->bulk_users)) {
    redirect($return);
}

if (!isset($SESSION->ube_orig) || empty($SESSION->ube_orig)) {
    redirect($return);
}

$isCustom = false;

$originals = $SESSION->ube_orig;
$users = $SESSION->bulk_users;

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_pagelayout('admin');

$url = $CFG->wwwroot.'/local/user_bulk_editor/action.php';
$PAGE->set_url($url);

$PAGE->set_title(get_string('ubfieldseditor', 'local_user_bulk_editor'));
$PAGE->set_heading(get_string('ubfieldseditor', 'local_user_bulk_editor'));

$PAGE->navbar->add(get_string('ubfieldseditor', 'local_user_bulk_editor'));

$change_form = new user_bulk_editor_change_form(null, array('field' => $field));

if (preg_match('/(?<=profile_field_)[\W\w]*/', $field, $cpf) !== FALSE){
    if (!empty($cpf)) {
        $field = $cpf[0];
        $isCustom = true;
        $field_info = $DB->get_record('user_info_field', array('shortname' => $field));
    };
};

if ($change_form->is_cancelled()) {
    // Form is cancelled
    redirect($return);
} else if ($formdata = $change_form->get_data()) {

    // If field type = 'Menu'
    $fld_options = array();
    if ($isCustom AND $field_info->datatype == 'menu') {
        $param1 = explode("\n", $field_info->param1);
        foreach($param1 as $key => $option) {
            $fld_options[$key] = format_string($option);//multilang formatting
        }
    };

    $prepare_upd = array();
    $action = (isset($formdata->chk_all)) ? $formdata->chk_all : 0;
    switch ($action) {
    case 0:
        for ($i=0;$i<$formdata->count;$i++) {
            $grp = 'group_'.$i;
            $group = $formdata->$grp;

            $from = (empty($fld_options)) ? $originals[$group["orig_$i"]] : $fld_options[$group["orig_$i"]];

            if ($isCustom AND $field_info->datatype == 'datetime') {
                // Dark magic for keys with sqare brackets.
                $dtrepl = "group_$i" . "[repl_$i]";
                $to = $formdata->$dtrepl;
            } else {
                $to = (empty($fld_options)) ? $group["repl_$i"] : $fld_options[$group["repl_$i"]];
            };

            if (!empty($to)) {
                $prepare_upd[$i] = array('from' => $from,
                                         'to' => $to);
            };
        };
      break;
    case 1:
        $replaceallto = (empty($fld_options)) ? $formdata->repl_all : $fld_options[$formdata->repl_all];
      break;
    case 2:
        $replaceallto = 1;
      break;
    case 3:
        $replaceallto = 0;
      break;
    case 4:
        $prepare_upd[] = array('from' => 0, 'to' => 1);
        $prepare_upd[] = array('from' => 1, 'to' => 0);
      break;
    };

    list($insql, $inparams) = $DB->get_in_or_equal($users);
    if ($isCustom){
        $field_info = $DB->get_record('user_info_field', array('shortname' => $field));
    };

    $queries = array();
    if ($action > 0 AND $action < 4) {
        // Replace all option
        if ($isCustom){
            $query = "fieldid = ? AND userid $insql";
            $params_upd = array_merge(array($field_info->id), $inparams);
            $queries[] = array('select' => $query,
                               'table' => 'user_info_data',
                               'newfield' => 'data',
                               'newvalue' => $replaceallto,
                               'params' => $params_upd);
        } else {
            $query = "id $insql";
            $queries[] = array('select' => $query,
                               'table' => 'user',
                               'newfield' => $field,
                               'newvalue' => $replaceallto,
                               'params' => $inparams);
        };
    } else {
        foreach($prepare_upd as $key => $val){
            if ($isCustom){
                $params = array_merge(array($field_info->id, $val['from']), $inparams);
                $sql = "SELECT userid FROM {user_info_data} WHERE fieldid = ? AND data = ? AND userid $insql";
                list($insql_upd, $inparams_upd) = $DB->get_in_or_equal($DB->get_fieldset_sql($sql, $params));
                $params_upd = array_merge(array($field_info->id), $inparams_upd);
                $query = "fieldid = ? AND userid $insql_upd";
                $queries[] = array('select' => $query,
                                   'table' => 'user_info_data',
                                   'newfield' => 'data',
                                   'newvalue' => $val['to'],
                                   'params' => $params_upd);
            } else {
                $params = array_merge(array($val['from']), $inparams);
                $sql = "SELECT id FROM {user} WHERE $field = ? AND id $insql";
                list($insql_upd, $inparams_upd) = $DB->get_in_or_equal($DB->get_fieldset_sql($sql, $params));
                $query = "id $insql_upd";
                //$params_upd = array_merge(array($val['to']), $inparams_upd);
                $queries[] = array('select' => $query,
                                   'table' => 'user',
                                   'newfield' => $field,
                                   'newvalue' => $val['to'],
                                   'params' => $inparams_upd);
            };
        };
    };

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('ubfieldseditoraction', 'local_user_bulk_editor'));

    // Process all queries
    echo '<center>';
    foreach ($queries as $query) {
        try {
            echo get_string('updatefield', 'local_user_bulk_editor', array('f' => $field, 'n' => $query['newvalue']));
            $DB->set_field_select($query['table'], $query['newfield'], $query['newvalue'], $query['select'], $query['params']);
            echo '<div class="success">'.get_string('success') . '</div><hr><br>';
        } catch (dml_exception $e) {
            echo '<div class="error">'.get_string('dbupdatefailed', 'error') . '</div><hr><br>';
        };
    };
    echo '</center>';

    echo $OUTPUT->continue_button($return);
    echo $OUTPUT->footer();

} else {
    // Form not validated?
    redirect($return);
};

