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
require_once($CFG->dirroot.'/auth/mcae/auth.php');
require_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM));

$return = $CFG->wwwroot.'/local/user_bulk_editor/index.php';

if (!isset($SESSION->bulk_users) || empty($SESSION->bulk_users)) {
    redirect($return);
}

$users = $SESSION->bulk_users;

$mcae = get_auth_plugin('mcae');

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_pagelayout('admin');

$url = $CFG->wwwroot.'/local/user_bulk_editor/process_mcae.php';
$PAGE->set_url($url);

$PAGE->set_title(get_string('processmcae', 'local_user_bulk_editor'));
$PAGE->set_heading(get_string('processmcae', 'local_user_bulk_editor'));
$PAGE->navbar->add(get_string('processmcae', 'local_user_bulk_editor'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('processmcae', 'local_user_bulk_editor'));

// For all selected users do $mcae->user_authenticated_hook($USER,$USER->username,"");
// and print user's fullname on the page. . . 

$progress = new progressbar('mcae');
$progress->create();

$count = count($users);
$errors = 0;
$err_msg = array();

for ($current = 0; $current <= $count; $current++) {
    $userid = $users[$current];
    if (!$current_user = $DB->get_record('user', array('id'=>$userid))) {
        $errors++;
        $error_msg[] = $userid;
    } else {
        $mcae->user_authenticated_hook($current_user,$current_user->username,"");
    };
    $progress->update($current, $count, get_string('progress', 'local_user_bulk_editor', array('n'=>$current,'c'=>$count)));
};

echo get_string('processmcaeerrors', 'local_user_bulk_editor', array('e'=>$errors,'m'=>implode(', ', $err_msg)));

echo $OUTPUT->footer();
