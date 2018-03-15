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

require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/resourcelib.php');
//fetch URL parameters
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT); 
$pageid = required_param('pageid', PARAM_INT);

$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);

$return_showpage = new moodle_url(
        '/mod/simplelesson/showpage.php', 
        array('courseid' => $courseid, 
        'simplelessonid' => $simplelessonid, 
        'pageid' => $pageid,
        'mode' => 'attempt'));

//set up the page
$PAGE->set_url('/mod/simplelesson/start_attempt.php', 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid));

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

// Check for questions
$question_entries = \mod_simplelesson\local\questions::
        fetch_questions($moduleinstance->id);

if (!empty($question_entries)) {
    $qubaid = \mod_simplelesson\local\attempts::create_usage(
            $modulecontext, 
            $moduleinstance->behaviour,
            $question_entries,
            $moduleinstance->id);  
}
redirect($return_showpage, 
            get_string('starting_attempt', MOD_SIMPLELESSON_LANG), 2);

echo $OUTPUT->header();
echo 'put the cancel form in here';
echo $OUTPUT->footer();