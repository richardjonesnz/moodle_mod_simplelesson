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
use \mod_simplelesson\output\link_data;
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
require_capability('mod/simplelesson:view', $modulecontext);
// Get the question feedback type. Get display options.
$feedback = $moduleinstance->behaviour;
$maxattempts = $moduleinstance->maxattempts;
$options = new display_options();

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

    // Question type (for essay questions).
    $qid = questions::get_questionid($simplelessonid, $pageid);
    $qtype = questions::fetch_question_type($qid);
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

    // Force finish the deferred question on save. But not if
    // it's an essay where we want multiple saves allowed.
    $slot = questions::get_slot($simplelessonid, $pageid);
    if ( ($quba->get_preferred_behaviour() == 'deferredfeedback')
            && ($qtype != 'essay') ) {
        $quba->finish_question($slot);
    }
    /* Record results here for each answer.
       qatid is entry in question_attempts table
       attemptid is from start_attempt (includes user id),
       that's our own question_attempts table.
       pageid gives us also the question info, such as slot
       and question number.

       We will keep this data because we will remove the attempt data from the question_attempts table during cleanup.
    */
    $qid = attempts::get_question_attempt_id($qubaid, $slot);
    $answerdata = new stdClass();
    $answerdata->id = 0;
    $answerdata->simplelessonid = $simplelessonid;
    $answerdata->qatid = $qid;
    $answerdata->attemptid = $attemptid;
    $answerdata->pageid = $pageid;
    $answerdata->maxmark = $quba->get_question_max_mark($slot);
    // Get the score associated with this question (if any).
    $qscore = questions::fetch_question_score(
                    $simplelessonid, $pageid);
    // Check if the user has allocated a specific mark
    // from the question management page.
    if ($qscore == 0) {
        $qscore = $answerdata->maxmark;
    } else {
        $answerdata->maxmark = round($qscore, $options->markdp);
    }
    // Calculate a score for the question.
    $mark = (float) $quba->get_question_fraction($slot);
    $answerdata->mark = round($mark * $qscore, $options->markdp);
    $answerdata->questionsummary = $quba->get_question_summary($slot);
    $answerdata->qtype = $qtype; // For manual essay marking.
    $answerdata->rightanswer = $quba->get_right_answer_summary($slot);
    $answerdata->timetaken = 0;
    $answerdata->timestarted = $starttime;
    $answerdata->timecompleted = $timenow;
    // Calculate the elapsed time (s).
    $answerdata->timetaken = ($answerdata->timecompleted
                    - $answerdata->timestarted);
    if ($qtype == 'essay') {
        // Special case, has additional save option.
        $submitteddata = $quba->extract_responses($slot);
        $answerdata->youranswer = $submitteddata['answer'];
        // Set mark negative (indicate needs grading).
        $answerdata->mark = -1;

    } else {
        $answerdata->youranswer = $quba->get_response_summary($slot);
        //$answerdata->id = $DB->insert_record(
          //      'simplelesson_answers', $answerdata);
    }
    // Save might be done several times. Check if exists.
    $answerdata->id = attempts::update_answer($answerdata);

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
            $slot, $quba, time(), $qtype);
}

// Show the navigation links.
$lastpage = pages::is_last_page($data);
$linkdata = link_data::get_nav_links($data, $cm, $mode, $attemptid,
        $lastpage);
echo $OUTPUT->render_from_template('mod_simplelesson/buttonlinks',
        $linkdata);

if (has_capability('mod/simplelesson:manage', $modulecontext)) {
    $linkdata = link_data::get_manage_links($data, $cm);
    echo $OUTPUT->render_from_template('mod_simplelesson/buttonlinks',
            $linkdata);
}

echo $OUTPUT->footer();