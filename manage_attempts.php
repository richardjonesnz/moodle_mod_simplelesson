<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage the attempt records.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_pairwork
 */
use \mod_simplelesson\local\reporting;
use \mod_simplelesson\local\attempts;
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'none', PARAM_TEXT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid),
        '*', MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/manage_attempts.php',
        array('courseid' => $courseid));

require_login($course, true);
$coursecontext = context_course::instance($courseid);

require_capability('mod/simplelesson:manageattempts', $coursecontext);

$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));
$returnmanage = new moodle_url('/mod/simplelesson/manage_attempts.php',
        array('courseid' => $courseid));

if ( ($action == 'delete') && ($attemptid != 0) ) {
    $status = attempts::delete_attempt($attemptid);
    if ($status) {
        $message = get_string('attempt_deleted', 'mod_simplelesson');
    } else {
        $message = get_string('attempt_not_deleted', 'mod_simplelesson');
    }

    redirect($returnmanage, $message);
}

$simplelesson = $PAGE->cm;
echo $OUTPUT->header();
$records = reporting::fetch_course_attempt_data($courseid);
echo reporting::show_course_attempt_report($records, $courseid);
echo $OUTPUT->footer();