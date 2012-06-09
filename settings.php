<?php

/**
 * User bulk editor local plugin version information
 *
 * @package    local
 * @subpackage user_bulk_editor
 * @copyright  2012 Andrew "Kama" (kamasutra12@yandex.ru) 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $ADMIN->add('root', new admin_externalpage('userbulkeditor',
            get_string('userbulkeditor', 'local_user_bulk_editor'),
            new moodle_url('/local/user_bulk_editor/index.php')));

}


