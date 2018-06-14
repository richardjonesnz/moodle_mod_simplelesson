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
 * Add a question to the lesson and optionally select the page
 * it will be shown on.
 *
 * This page should have the option to select a category as well.
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\questions;
use \core\output\notification;
require_once('../../config.php');
require_once('add_question_form.php');

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);

// Set course related variables.
$moduleinstance  = $DB->get_record('simplelesson',
        array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*',
        MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson',
        $simplelessonid, $courseid, false, MUST_EXIST);

// Set up the page.
$thisurl = new moodle_url('/mod/simplelesson/add_question.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid));
$PAGE->set_url($thisurl);
require_login($course, true, $cm);

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

$returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
$returnmanage = new moodle_url('/mod/simplelesson/edit_questions.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid));

$mform = new simplelesson_add_question_form(null,
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'categoryid' => $moduleinstance->categoryid));

// Cancelled.
if ($mform->is_cancelled()) {
    redirect($returnview, get_string('cancelled'), 2);
}

// Has data, save checked questions in the simplelesson_questions table.
if ($data = $mform->get_data()) {

    $qdata = new stdClass;
    foreach ($data as $key => $value) {
        // Any key starts with q and is non-zero is a selected question.
        if (substr($key, 0, 1) == 'q') {
            if ($value != 0) {
                $qdata->qid = $value;
                $qdata->pageid = 0; // No page data yet.
                $qdata->simplelessonid = $simplelessonid;
                $qdataid = questions::save_question($qdata);
            }
        }
    }
    redirect($returnmanage,
            get_string('questions_added', 'mod_simplelesson'), 2,
            notifications::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('question_adding', 'mod_simplelesson'), 2);
$mform->display();
echo $OUTPUT->footer();