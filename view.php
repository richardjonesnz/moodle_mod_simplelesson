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
 * Prints a particular instance of simplelesson
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_pairwork
 */
use mod_simplelesson\local\pages;
use mod_simplelesson\local\attempts;
use mod_simplelesson\event\course_module_viewed;
use mod_simplelesson\local\reporting;
use mod_simplelesson\local\questions;
require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');
global $DB, $USER;
// Get a course module or instance id.
$id = optional_param('id', 0, PARAM_INT);
$simplelessonid  = optional_param('simplelessonid', 0, PARAM_INT);

if ($id) {
    // Course module id.
    $cm = get_coursemodule_from_id('simplelesson',
            $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course),
            '*', MUST_EXIST);
    $simplelesson = $DB->get_record('simplelesson',
            array('id' => $cm->instance), '*', MUST_EXIST);

} else if ($simplelessonid) {
    // Simplelesson instance id.
    $simplelesson = $DB->get_record('simplelesson',
            array('id' => $simplelessonid), '*', MUST_EXIST);
    $course = $DB->get_record('course',
            array('id' => $simplelesson->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('simplelesson', $simplelesson->id,
            $course->id, false, MUST_EXIST);
} else {

    // Developer debugging called.
    debugging('Internal error: No course_module ID or instance ID',
            DEBUG_DEVELOPER);
}

$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/simplelesson/view.php', array('id' => $cm->id));
require_login($course, true, $cm);

// Log the module viewed event.
$event = course_module_viewed::create(array(
        'objectid' => $cm->id,
        'context' => $modulecontext,
    ));

$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $simplelesson);
$event->trigger();

// Set completion.
// if we got this far, we can consider the activity "viewed".
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$renderer = $PAGE->get_renderer('mod_simplelesson');

echo $renderer->header($simplelesson->title, $course->fullname);

// Show reports tab if permission exists and admin has allowed.
$config = get_config('mod_simplelesson');
if ($config->enablereports) {
    if (has_capability('mod/simplelesson:viewreportstab', $modulecontext)) {
        echo reporting::show_reports_tab($course->id, $simplelesson->id);
    }
}

// Output the introduction as the first page.
if ($simplelesson->intro) {
    echo $renderer->fetch_intro($simplelesson, $cm->id);
}

// Do we have any pages?
$numpages = pages::count_pages($simplelesson->id);

// Add a link to the first page.
if ($numpages > 0) {
    // Get the record # for the first page.
    $pageid = pages::get_page_id_from_sequence($simplelesson->id, 1);
    // Show the attempt link if we have questions.
    $questionentries = questions::fetch_attempt_questions(
            $simplelesson->id);
    $attemptlink = (count($questionentries) > 0);
    echo $renderer->fetch_firstpage_links($course->id,
            $simplelesson->id, $pageid, $attemptlink);
}

$canmanage = has_capability('mod/simplelesson:manage', $modulecontext);

// First page summary.
$userattempts = attempts::get_number_of_attempts($USER->id,
        $simplelesson->id);
echo $renderer->fetch_lesson_info($numpages, $userattempts,
        $simplelesson->maxattempts, $canmanage);

// If we are teacher we see edit links.
if ($canmanage) {
    echo $renderer->fetch_editing_links($course->id,
            $simplelesson->id, $numpages);
}

// Finish the page.
echo $OUTPUT->footer();