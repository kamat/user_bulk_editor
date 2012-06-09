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
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/lib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/user/user_bulk_forms.php');

admin_externalpage_setup('userbulkeditor');

if (!isset($SESSION->bulk_users)) {
    $SESSION->bulk_users = array();
}
// create the user filter form
$ufiltering = new user_filtering();

$user_bulk_form = new user_bulk_form(null, get_selection_data($ufiltering));

if ($data = $user_bulk_form->get_data()) {
    if (!empty($data->addall)) {
        add_selection_all($ufiltering);

    } else if (!empty($data->addsel)) {
        if (!empty($data->ausers)) {
            if (in_array(0, $data->ausers)) {
                add_selection_all($ufiltering);
            } else {
                foreach($data->ausers as $userid) {
                    if ($userid == -1) {
                        continue;
                    }
                    if (!isset($SESSION->bulk_users[$userid])) {
                        $SESSION->bulk_users[$userid] = $userid;
                    }
                }
            }
        }

    } else if (!empty($data->removeall)) {
        $SESSION->bulk_users= array();

    } else if (!empty($data->removesel)) {
        if (!empty($data->susers)) {
            if (in_array(0, $data->susers)) {
                $SESSION->bulk_users= array();
            } else {
                foreach($data->susers as $userid) {
                    if ($userid == -1) {
                        continue;
                    }
                    unset($SESSION->bulk_users[$userid]);
                }
            }
        }
    }

    // reset the form selections
    unset($_POST);
    $user_bulk_form = new user_bulk_form(null, get_selection_data($ufiltering));
}

$continue = $CFG->wwwroot.'/local/user_bulk_editor/editor.php';
// do output
echo $OUTPUT->header();

$ufiltering->display_add();
$ufiltering->display_active();

$user_bulk_form->display();

echo $OUTPUT->continue_button($continue);

echo $OUTPUT->footer();
