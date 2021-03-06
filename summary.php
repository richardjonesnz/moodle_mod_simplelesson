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
use \mod_simplelesson\local\display_options;
use \mod_simplelesson\output\link_data;
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
// Get the question feedback type. Get display options.
$options = new display_options();
$returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
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
    echo $OUTPUT->header();
    $answerdata = attempts::get_lesson_answer_data(
            $attemptid, $options);
    attempts::save_lesson_answerdata($answerdata);
    $sessiondata = attempts::get_sessiondata($answerdata);
    // Update gradebook.
    $user = attempts::get_attempt_user($attemptid);

    // Show review page (if allowed).
    $review = ( ($simplelesson->allowreview) || has_capability('mod/simplelesson:manage', $modulecontext) );

    if ($review) {
        echo $OUTPUT->heading(get_string('summary_header',
                'mod_simplelesson'), 2);
        $name = $user->firstname . ' ' . $user->lastname;

        echo get_string('summary_user', 'mod_simplelesson', $name);
        echo $renderer->lesson_summary($answerdata, $options->markdp);
        // Display summary.
        echo $renderer->show_summary_data($sessiondata);
    }

    // Log the completion event and update the gradebook.
    $event = attempt_completed::create(array(
        'objectid' => $attemptid,
        'context' => $modulecontext));

    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot($cm->modname, $simplelesson);
    $event->trigger();

    // Clean up our attempt data.
    attempts::set_attempt_completed($attemptid,
            $sessiondata);

    // Update the grade for this attempt.
    simplelesson_update_grades($simplelesson, $user->id);

    // Clean up question usage and attempt data.
    $qubaid = attempts::get_usageid($attemptid);
    attempts::remove_usage_data($qubaid);
    $DB->set_field('simplelesson_attempts', 'qubaid', 0,
            array('id' => $attemptid));

    $linkdata = link_data::get_home_button($cm,
            get_string('finishreview', 'mod_simplelesson'));
    echo $renderer->home_button($linkdata);
} else {
    // It's a preview, go back to the home page.
    $returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
    redirect($returnview,
            get_string('preview_completed', 'mod_simplelesson'), 1,
            notification::NOTIFY_INFO);
}

echo $renderer->footer();