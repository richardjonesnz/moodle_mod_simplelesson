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
use \mod_simplelesson\local\questions;
use \mod_simplelesson\event\attempt_completed;
use \mod_simplelesson\local\pages;
use \question_engine;
use \core\output\notification;
require_once('../../config.php');
global $DB;
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$mode = optional_param('mode', 'preview', PARAM_TEXT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
$pageid = optional_param('pageid', 0, PARAM_INT);

$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson',
            array('id' => $simplelessonid), '*', MUST_EXIST);
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
/*
    If we got here, the user got to the last page
    and hit the exit link.
    Mode is either preview or attempt.
*/
if ($mode == 'attempt') {

    // Summary data for this attempt by this user.
    // If all questions answered, these should be the same.
    $answerdata = attempts::get_lesson_answer_data($attemptid);
    $questionentries = questions::fetch_attempt_questions(
            $simplelesson->id);
    echo 'answers: ' . count($answerdata) . ' entries: ' . count($questionentries);
    $completed = ( count($questionentries) == count($answerdata) );
    // Is it complete?
    if (!$completed) {
        $firstpage =
                pages::get_page_id_from_sequence($simplelessonid, 1);
        $returnfirst = new moodle_url('/mod/simplelesson/showpage.php',
                array('courseid' => $courseid,
                'simplelessonid' => $simplelessonid,
                'pageid' => $firstpage,
                'mode' => 'attempt',
                'attemptid' => $attemptid ));
        redirect($returnfirst,
                get_string('answerquestions', 'mod_simplelesson'), 2,
                notification::NOTIFY_ERROR);
    }
    echo $OUTPUT->header();
    attempts::save_lesson_answerdata($answerdata);
    echo $OUTPUT->heading(get_string('summary_header', 'mod_simplelesson'), 2);
    $user = attempts::get_attempt_user($attemptid);
    $name = $user->firstname . ' ' . $user->lastname;
    echo get_string('summary_user', 'mod_simplelesson', $name);
    echo $renderer->lesson_summary($answerdata);

    // Log the event.
    $event = attempt_completed::create(array(
        'objectid' => $attemptid,
        'context' => $modulecontext,
    ));

    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot($cm->modname, $simplelesson);
    $event->trigger();

    // Record attempt completion data.
    $sessiondata = attempts::get_sessiondata($answerdata);
    echo $renderer->get_summary_data($sessiondata);

    // Clean up our attempt data.
    attempts::set_attempt_completed($attemptid,
            $sessiondata);

    // Clean up question usage and attempt data.
    $qubaid = attempts::get_usageid($attemptid);
    attempts::remove_usage_data($qubaid);
    $DB->set_field('simplelesson_attempts', 'qubaid', 0,
            array('id' => $attemptid));

    echo $renderer->show_attempt_completion_link($courseid,
            $simplelessonid, $attemptid);
} else {
    // It's a preview, go back to the home page.
    $returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
    redirect($returnview,
            get_string('preview_completed', 'mod_simplelesson'), 1,
            notification::NOTIFY_INFO);
}

echo $renderer->footer();