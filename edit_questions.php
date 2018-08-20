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

use \mod_simplelesson\local\questions;
use \mod_simplelesson\local\pages;
use \mod_simplelesson\output\link_data;
use \mod_simplelesson\output\table_data;
use \core\output\notification;

/**
 * Edit a lesson and its questions
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
/**
 * Define a form that acts on the page field
 */
class simplelesson_pagechanger_form extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        $pagetitles = $this->_customdata['page_titles'];

        // Select a page title.
        $mform->addElement('select', 'pagetitle',
                get_string('pagetitle', 'mod_simplelesson'),
                $pagetitles);

        $mform->addElement('text', 'score',
                get_string('questionscore', 'mod_simplelesson'));
        $mform->setDefault('score', 1);
        $mform->setType('score', PARAM_INT);

        $mform->addElement('hidden', 'courseid',
                $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid',
                $this->_customdata['simplelessonid']);
        $mform->addElement('hidden', 'actionitem',
                $this->_customdata['actionitem']);

        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);
        $mform->setType('actionitem', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'qid');
        $mform->setType('qid', PARAM_INT);
        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_TEXT);

        $this->add_action_buttons();
    }
}
global $DB;

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$action = optional_param('action', 'list', PARAM_TEXT);
$actionitem = optional_param('actionitem', 0, PARAM_INT);

$moduleinstance  = $simplelessonid;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson',
        $simplelessonid, $courseid, false, MUST_EXIST);

$pageurl = new moodle_url(
        '/mod/simplelesson/edit_questions.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'sesskey' => sesskey()));
$PAGE->set_url($pageurl);
require_login($course, true, $cm);
require_sesskey();
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

$renderer = $PAGE->get_renderer('mod_simplelesson');

$questions = questions::fetch_questions($simplelessonid);
$pagetitles = questions::fetch_all_page_titles($simplelessonid);

// Process the form.
$mform = new simplelesson_pagechanger_form(null,
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'page_titles' => $pagetitles,
        'actionitem' => $actionitem));

if ($mform->is_cancelled()) {
    redirect($pageurl, get_string('cancelled'), 2);
    exit;
}

/* If we have data, save it (if it isn't there already)
 * We only allow one question per page.
 * However, we still want to update the table if the question
 * is de-selected using the "none" option.
*/
if ($data = $mform->get_data()) {

    if (!questions::page_has_question($simplelessonid, $data->pagetitle)
            || ($data->pagetitle == 0) ) {
        questions::update_question_table($simplelessonid, $data);
        redirect($PAGE->url,
                get_string('updated', 'core', $data->name), 2,
                notification::NOTIFY_SUCCESS);
    } else {
        // If this page has this question we can update.
        if ($data->qid == questions::get_questionid($simplelessonid,
                $data->pagetitle)) {
            questions::update_question_table($simplelessonid, $data);
            redirect($PAGE->url,
                    get_string('updated', 'core', $data->pagetitle), 2,
                    notification::NOTIFY_SUCCESS);
        } else {
            // Otherwise we can't add it.
            redirect($PAGE->url,
                    get_string('question_exists', 'mod_simplelesson'),
                    2, notification::NOTIFY_ERROR);
        }
    }
}

if ($action == "edit") {

    // Create data for the form
    // Which is the corresponding question.
    $data = new stdClass();

    foreach ($questions as $question) {
        if ($question->qid == $actionitem) {
            $data = $question;
        }
    }

    if (!$data) {
        redirect($pageurl, 'nodata', 2);
    }
    // Which page is the currently selected question on
    // if any?  Set the form select to the page sequence value.
    $data->pagetitle = questions::get_page_sequence(
            $actionitem, $simplelessonid);
    $mform->set_data($data);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(
        get_string('selecting_page', 'mod_simplelesson') .
        $data->name, 3);
        echo '<br />';
        $mform->display();
        echo $OUTPUT->footer();
        return;
}
echo $OUTPUT->header();
echo $OUTPUT->heading(
        get_string('question_editing', 'mod_simplelesson'), 2);
echo get_string('edit_question_page', 'mod_simplelesson');
// Output list of questions.
//$questions = questions::fetch_questions($simplelessonid);
//echo $renderer->question_management(
//        $courseid, $simplelessonid, $questions);

$tabledata = table_data::get_question_table_data($cm);
echo $OUTPUT->render_from_template('mod_simplelesson/question_edit',
            $tabledata);

// Add page links.
if (has_capability('mod/simplelesson:manage', $modulecontext)) {
    $linkdata = link_data::get_questionpage_links($cm);
    echo $OUTPUT->render_from_template('mod_simplelesson/buttonlinks',
            $linkdata);
}
echo $OUTPUT->footer();