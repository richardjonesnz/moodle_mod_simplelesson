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
 * Shows a simplelesson page
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */
use \mod_simplelesson\local\pages;
use \mod_simplelesson\local\questions;
use \mod_simplelesson\local\attempts;
use \mod_simplelesson\local\display_options;
use \mod_simplelesson\event\page_viewed;
require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir . '/questionlib.php');
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid  = required_param('simplelessonid', PARAM_INT);
$pageid = required_param('pageid', PARAM_INT);
$mode = optional_param('mode', 'preview', PARAM_TEXT);
$starttime = optional_param('starttime', 0, PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
global $USER;

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid,
        $courseid, false, MUST_EXIST);
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);

$PAGE->set_url('/mod/simplelesson/showpage.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'pageid' => $pageid));

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

// Get the question feedback type.
$feedback = $moduleinstance->behaviour;
$maxattempts = $moduleinstance->maxattempts;

// Question usage id.
$qubaid = attempts::get_usageid($attemptid);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

$renderer = $PAGE->get_renderer('mod_simplelesson');

// Sort the question usage and slots.
$questionentry = questions::page_has_question($simplelessonid, $pageid);
if ( ($questionentry) && ($mode == 'attempt') ) {

    $quba = \question_engine::load_questions_usage_by_activity($qubaid);

    // Display and feedback options.
    $options = display_options::get_options($feedback);
}
$actionurl = new moodle_url ('/mod/simplelesson/showpage.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'pageid' => $pageid,
        'mode' => $mode,
        'attemptid' => $attemptid));

// Check if data submitted.
if (data_submitted() && confirm_sesskey()) {
    $timenow = time();
    $transaction = $DB->start_delegated_transaction();
    $quba = \question_engine::load_questions_usage_by_activity($qubaid);
    $quba->process_all_actions($timenow);
    question_engine::save_questions_usage_by_activity($quba);
    $transaction->allow_commit();

    /* Record results here for each answer.
       qatid id is entry in question_attempts table
       attemptid is from start_attempt (includes user id),
       that's our own question_attempts table.
       pageid gives us also the question info, such as slot
       and question number.

       We will keep this data because we will remove the attempt data from the question_attempts table during cleanup.
    */
    $slot = questions::get_slot($simplelessonid, $pageid);
    $qdata = attempts::get_question_attempt_data($qubaid, $slot);
    $answerdata = new stdClass();
    $answerdata->simplelessonid = $simplelessonid;
    $answerdata->qatid = $qdata->id;
    $answerdata->attemptid = $attemptid;
    $answerdata->pageid = $pageid;
    $answerdata->maxmark = 1;
    $answerdata->mark = 0;
    $answerdata->questionsummary = $qdata->questionsummary;
    $answerdata->rightanswer = $qdata->rightanswer;
    $answerdata->youranswer = $qdata->responsesummary;
    $answerdata->timetaken = 0;
    $answerdata->timestarted = $starttime;
    $answerdata->timecompleted = $timenow;
    $answerdata->id = $DB->insert_record('simplelesson_answers',
            $answerdata);
    redirect($actionurl);
} else {
    // Log the page viewed event (but not for every
    // question attempt).
    $page = $DB->get_record('simplelesson_pages',
            array('simplelessonid' => $simplelessonid,
            'id' => $pageid), '*', MUST_EXIST);
    $event = page_viewed::create(array(
            'objectid' => $pageid,
            'context' => $modulecontext,
        ));
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('simplelesson_pages', $page);
    $event->trigger();
}

echo $OUTPUT->header();

// Now get this page record.
$data = pages::get_page_record($pageid);

// Prepare page text, re-write urls.
$contextid = $modulecontext->id;
$data->pagecontents = file_rewrite_pluginfile_urls($data->pagecontents,
        'pluginfile.php', $contextid, 'mod_simplelesson', 'pagecontents',
        $pageid);

// Run the content through format_text to enable streaming video.
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $modulecontext;
$data->pagecontents = format_text($data->pagecontents, $data->pagecontentsformat, $formatoptions);

// Show the page index if required (but not during an attempt).
if ( ($moduleinstance->showindex) && ($mode != 'attempt') ) {
    $pagelinks = pages::fetch_page_links($courseid, $simplelessonid, $pageid);
    echo $renderer->fetch_index($pagelinks);
}

echo $renderer->show_page($data);

// If there is a question and this is an attempt, show
// the question.

if ( ($questionentry) && ($mode == 'attempt') ) {
    $slot = questions::get_slot($simplelessonid, $pageid);
    echo $renderer->render_question_form($actionurl, $options,
            $slot, $quba, time());
}

// If this is the last page, add link to the summary page.
if (pages::is_last_page($data)) {
    echo $renderer->show_summary_page_link($courseid, $simplelessonid,
            $mode, $attemptid, $pageid);
} else {
    // Show the navigation links.
    echo $renderer->show_page_nav_links($data, $courseid, $mode,
            $attemptid);
}

if (has_capability('mod/simplelesson:manage', $modulecontext)) {
    echo $renderer->show_page_edit_links($courseid, $data, 'showpage');
}

echo $OUTPUT->footer();