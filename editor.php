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
require_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM));

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

// Get fields
$sfield = '';
$fields_form = new user_bulk_editor_action_form();
if ($data = $fields_form->get_data()) {
    $sfield = $data->field;
};

$actionurl = "$CFG->wwwroot/local/user_bulk_editor/action.php?field=$sfield";
$change_form = new user_bulk_editor_change_form($actionurl, array('field' => $sfield));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('ubfieldseditor', 'local_user_bulk_editor'));

echo $fields_form->display();

echo $change_form->display();

echo $OUTPUT->footer();
