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
 * Edit a lesson and its questions
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\questions;
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
        // Just the one field - select a page title.
        $mform->addElement('select', 'pagetitle', 
                get_string('pagetitle', 'mod_simplelesson'),
                $this->_customdata['page_titles']);
        
        $mform->addElement('hidden', 'courseid', 
                $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid', 
                $this->_customdata['simplelessonid']);
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);
        
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

//fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT); 
$action = optional_param('action','list',PARAM_TEXT);
$actionitem = optional_param('actionitem', 0, PARAM_INT);

// Set course related variables.
$moduleinstance  = $simplelessonid;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', 
        $simplelessonid, $courseid, false, MUST_EXIST);

//set up the page
$pageurl = new moodle_url(
        '/mod/simplelesson/edit_questions.php', 
        array('courseid' => $courseid, 
        'simplelessonid' => $simplelessonid)); 
$PAGE->set_url($pageurl);
require_login($course, true, $cm);

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

$renderer = $PAGE->get_renderer('mod_simplelesson');

echo $OUTPUT->heading(
        get_string('question_editing', 'mod_simplelesson'), 2);
$questions = questions::fetch_questions($simplelessonid);
$pagetitles = questions::fetch_all_page_titles($simplelessonid);
               
// Process the form.
$mform = new simplelesson_pagechanger_form(null,
        array('courseid' =>$courseid,
        'simplelessonid' => $simplelessonid,
        'page_titles' => $pagetitles));

if ($mform->is_cancelled()) {
    redirect($pageurl, get_string('cancelled'), 2);
    exit;
}

// If we have data, save it.
if ($data = $mform->get_data()) {
    questions::update_question_table($data); 
    redirect($PAGE->url,
                get_string('updated','core', $data->name), 2);
}

if($action =="edit") {
    
    // Create data for the form
    // Which is the corresponding question
    $data = new stdClass();
    foreach($questions as $question) {
        if ($question->qid == $actionitem) {
            $data = $question;
        }
    }
    
    if(!$data){ redirect($pageurl,'nodata',2); }

    $mform->set_data($data);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(
        get_string('selecting_page', 'mod_simplelesson'), 4);
        echo get_string('editing_question_page', 
                'mod_simplelesson', $data->name);
        $mform->display();
        echo $OUTPUT->footer();
        return;
}
echo $OUTPUT->header();

// Output list of questions.
$questions = questions::fetch_questions($simplelessonid);
echo $renderer->question_management(
        $courseid, $simplelessonid, $questions);

// Add page links.
if(has_capability('mod/simplelesson:manage', $modulecontext)) {
    echo $renderer->fetch_question_page_links($courseid, 
            $simplelessonid);
}
echo $OUTPUT->footer();