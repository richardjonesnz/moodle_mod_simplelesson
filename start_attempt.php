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
 * Start a simplelesson attempt (question asnwers recorded)
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\questions;
use \mod_simplelesson\local\attempts;
use \mod_simplelesson\local\pages;
require_once('../../config.php');
global $DB, $USER;
// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT); 
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
// Set up the page.
$PAGE->set_url('/mod/simplelesson/start_attempt.php', 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid));
require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

// Check attempts (for students)

if (!has_capability('mod/simplelesson:manage', $modulecontext)) {
    $maxattempts = $moduleinstance->maxattempts;
    $userattempts = attempts::get_number_of_attempts($USER->id, $simplelessonid);

    if ( ($userattempts >= $maxattempts) && ($maxattempts != 0) ) {
        // Max attempts is exceeded.
        $returnview = new moodle_url('/mod/simplelesson/view.php',
                array('simplelessonid' => $simplelessonid));
        redirect($returnview,
                get_string('max_attemps_exceeded', 'mod_simplelesson', 2));
    }
}
// Check for questions.
$question_entries = questions::fetch_questions($moduleinstance->id);
if (!empty($question_entries)) {
    $qubaid = attempts::create_usage(
            $modulecontext, 
            $moduleinstance->behaviour,
            $question_entries,
            $moduleinstance->id);  
}
// Save slots here.
questions::set_slots($simplelessonid);

// Count this as starting an attempt, record it.
$attempt_data = new stdClass();
$attempt_data->courseid = $courseid;
$attempt_data->simplelessonid = $simplelessonid;
$attempt_data->pageid = 0;  // Set this later, per page.
$attempt_data->userid = $USER->id;
$attempt_data->status = 1;
$attempt_data->sessionscore = 0;
$attempt_data->timecreated = time();
$attempt_data->timemodified = 0;

// Record an attempt in attempts table.
$attemptid = attempts::set_attempt_start($attempt_data);

// Go to to the first lesson page.
$pageid = pages::get_page_id_from_sequence($simplelessonid, 1);

$returnshowpage = new moodle_url(
        '/mod/simplelesson/showpage.php', 
        array('courseid' => $courseid, 
        'simplelessonid' => $simplelessonid, 
        'pageid' => $pageid,
        'mode' => 'attempt',
        'attemptid' => $attemptid));

redirect($returnshowpage, 
            get_string('starting_attempt', 
            'mod_simplelesson'), 2);

echo $OUTPUT->header();
echo 'put the cancel form in here';
echo $OUTPUT->footer();