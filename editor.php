<?php

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/user_bulk_editor/lib.php');

$return = $CFG->wwwroot.'/local/user_bulk_editor/index.php';

if (!isset($SESSION->bulk_users) || empty($SESSION->bulk_users)) {
    redirect($return);
}

$SESION->ube_orig = array();

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_pagelayout('admin');

$url = $CFG->wwwroot.'/local/user_bulk_editor/editor.php';
$PAGE->set_url($url);

$PAGE->set_title(get_string('ubfieldseditor', 'local_user_bulk_editor'));
$PAGE->set_heading(get_string('ubfieldseditor', 'local_user_bulk_editor'));

$PAGE->navbar->add(get_string('ubfieldseditor', 'local_user_bulk_editor'));

$debug_div = '<div style="margin: 3px; padding: 5px; border: 1px solid #b7b7b7; background-color: #e7e7e7">';
$debug = '<div style="margin: 3px; padding: 5px; border: 1px solid #b7b7b7; background-color: #fafafa">SESSION->bulk_users array: ' . $debug_div . implode(', ', $SESSION->bulk_users) . '</div><br>';

// Get fields
$sfield = '';
$fields_form = new user_bulk_editor_action_form();
if ($data = $fields_form->get_data()) {
    $sfield = $data->field;
};

$actionurl = "$CFG->wwwroot/local/user_bulk_editor/action.php?field=$sfield";
$change_form = new user_bulk_editor_change_form($actionurl, array('field' => $sfield));

$debug .= "Selected field: $sfield <br>";

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('ubfieldseditor', 'local_user_bulk_editor'));

echo $debug . '</div>';
echo $fields_form->display();

echo $change_form->display();

echo $OUTPUT->footer();
