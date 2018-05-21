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
 * Prints the simplelesson summary page
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\attempts; 
require_once('../../config.php');
//require_once(dirname(__FILE__).'/lib.php');
//require_once($CFG->libdir.'/resourcelib.php');
//fetch URL parameters
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);  
$mode = optional_param('mode', 'preview', PARAM_TEXT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);

$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
//set up the page
$PAGE->set_url('/mod/simplelesson/summary.php', 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid, 
              'mode' => $mode,
              'attemptid' => $attemptid));
require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
$lessontitle = $moduleinstance->name;
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));
$renderer = $PAGE->get_renderer('mod_simplelesson');

// If we got here, the user got to the last page and hit the exit link. 
// Mode is either preview or attempt.

if ($mode == 'attempt') {
    echo $OUTPUT->header();
    attempts::set_attempt_completed($attemptid);

    // Summary data for this attempt by this user
    $answerdata = attempts::get_lesson_answer_data($attemptid);
    // var_dump($answer_data);exit;
    echo $OUTPUT->heading(get_string('summary_header', 'mod_simplelesson'), 2);
    echo $renderer->lesson_summary($answerdata);
    echo $renderer->show_home_page_link($simplelessonid);
} else {
    // It's a preview, go back to the home page.
    $returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
    redirect($returnview, 
            get_string('preview_completed', 'mod_simplelesson', 1));
}
//if we are teacher we see buttons.
if(has_capability('mod/simplelesson:manage', $modulecontext)) {
    echo $renderer->lesson_editing_links($course->id, $cm->id,
            $moduleinstance->id);
}
// Finish the page.
echo $renderer->footer();