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
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid  = required_param('simplelessonid', PARAM_INT);
$pageid = required_param('pageid', PARAM_INT);
$mode = optional_param('mode', 'preview', PARAM_TEXT);
$starttime = optional_param('starttime', 0, PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
global $USER;

// Get the question feedback type.
$config = get_config('mod_simplelesson');
$feedback = $config->feedback;
$maxattempts = $config->maxattempts;

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid,
        $courseid, false, MUST_EXIST);
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$PAGE->set_url('/mod/simplelesson/showpage.php',
        array('courseid' => $courseid, 'simplelessonid' => $simplelessonid,
        'pageid' => $pageid));

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

$config = get_config('mod_simplelesson');

$renderer = $PAGE->get_renderer('mod_simplelesson');

// Sort the question usage and slots
$question_entry = questions::page_has_question($simplelessonid, $pageid);
if ($question_entry) {
    
    $qubaid = attempts::get_usageid($simplelessonid);
    $quba = \question_engine::load_questions_usage_by_activity($qubaid);
    
    // get the slot for the lesson and page
    $slot = questions::get_slot($simplelessonid, $pageid);
    
    // Display and feedback options
    $options = displayoptions::get_options($feedback);
    
    // Actually not allowing deferred feedback (yet)
    $deferred = $options->feedback == 'deferredfeedback';
} else {
    $slot = 0;
}

$actionurl = $PAGE->url;

// Check if data submitted.
if (data_submitted() && confirm_sesskey()) {
    $timenow = time();
    $transaction = $DB->start_delegated_transaction();
    $quba = \question_engine::load_questions_usage_by_activity($qubaid);
    // $quba->finish_question($slot);
    $quba->process_all_actions($timenow);
    question_engine::save_questions_usage_by_activity($quba);
    $transaction->allow_commit(); 
    
    // Todo: Record results here for each answer.
    $qdata = attempts::get_question_attempt_id($qubaid, $slot); 
    $answer_data = new stdClass();
    $answer_data->qatid = $qdata->id;           
    $answer_data->courseid = $courseid;
    $answer_data->simplelessonid = $simplelessonid;
    $answer_data->attemptid = $attemptid;
    $answer_data->userid = $USER->id;
    $answer_data->slqid = $question_entry->id;
    $answer_data->starttime = $starttime;
    $answer_data->endtime = $timenow;
    $DB->insert_record('simplelesson_answers', $answer_data);    
    redirect($actionurl);
             
} else if ($slot !=0) {
    // Probably just re-visiting or refreshing page
    // Disable next until question answered...
    $question = $quba->get_question($slot);
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
// the question, or just show a placeholder.

if ($slot != 0) {
    if ($mode == 'attempt') {
        echo $renderer->render_question_form(
                $actionurl, $options, $slot, $quba, 
                $deferred, time());
    } else {
        echo $renderer->dummy_question(
                $questionid, $mode);
    }
}

// If this is the last page, add link to the summary page.
if (pages::is_last_page($data)) {
    // Todo: Check here all questions answered or not.
    echo $renderer->show_last_page_link(
            $courseid, $simplelessonid, $USER->id, 
            $mode, $attemptid);
}

//  Show the navigation links.
echo $renderer->show_page_nav_links($courseid, $data);

if (has_capability('mod/simplelesson:manage', $modulecontext)) {
    echo $renderer->show_page_edit_links($courseid, $data);
}

echo $OUTPUT->footer();