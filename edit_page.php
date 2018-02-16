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
 * Form for editing lesson pages
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/simplelesson/lib.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Define the edit page form elements
 * Elements I will need - title, content editor, question picker, [file picker?]
 */
class simplelesson_edit_page_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;

        $mform->addElement('text', 'pagetitle', get_string('pagetitle', MOD_SIMPLELESSON_LANG), array('size'=>'64'));
        $mform->addRule('pagetitle', null, 'required', null, 'client');
        // $mform->addHelpButton('pagetitle', 'pagetitle', MOD_SIMPLELESSON_LANG);
        $mform->setType('pagetitle', PARAM_TEXT);                     
                                                        
        // First page text - editor field
        $context = $this->_customdata['context'];
        $editpageoptions = simplelesson_get_editor_options($context);
        $mform->addElement('editor', 'pagecontents_editor', 
                get_string('pagecontents', MOD_SIMPLELESSON_LANG), 
                null, $editpageoptions);
        $mform->setType('pagecontents_editor', PARAM_RAW);
        $mform->addRule('pagecontents_editor', get_string('required'), 
                'required', null, 'client');

        // To add, question picker
        // need a utility function to scan the question bank

        // To add link data
        // If this is first page then just a link to home, otherwise a list
        // of available page titles to link to.

        $this->add_action_buttons();
    }

    function data_preprocessing(&$default_values) {

        if ($this->current->instance) {
            $context = $this->context;
            $editoroptions = simplelesson_get_editor_options($context);
            $default_values = (object) $default_values;
            $default_values = 
                    file_prepare_standard_editor($default_values, 'pagecontents',
                    $editoroptions, $context, 'mod_simplelesson', 
                    'pagecontents',
                    $default_values->id);
            $default_values = (array) $default_values;
        }
    }
}

// Fetch parameters
$courseid = required_param('id', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$pageid = required_param('pageid', PARAM_INT); 
$action = optional_param('action','view', PARAM_TEXT); 

// Get course record
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_course_login($course);
$coursecontext = context_course::instance($course->id);

// Set up page
$PAGE->set_url('/mod/simplelesson/edit_page.php', 
        array('id' => $courseid, 'simplelessonid' => $simplelessonid));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');

// Form processing begins here
$mform = new simplelesson_edit_page_form(null, array('context'=>$coursecontext));

// Check cancel
if ($mform->is_cancelled()) {
    redirect($PAGE->url, get_string('cancelled'), 2);
    exit;
}

// Check for data
if ($data = $mform->get_data()) {

    // Does a record exist for this lesson and this page?
    if (\mod_simplelesson\utilities::has_page_record($pageid, $simplelessonid)) {
        // Update existing with form data
        $pageid = \mod_simplelesson\utilities::update_page_record($pageid, $data);
        redirect($PAGE->url, get_string('updated', 'core', $pageid), 2);
    } else {
        // Create a new page record
        $pageid = \mod_simplelesson\utilities::add_page_record($pageid, $data);
        redirect($PAGE->url, get_string('success'), 2); 
    }
}

// If the action is edit, show the form
if ($action == 'edit') {
    echo $OUTPUT->header();
    $data = new stdClass();
    $data->courseid = $courseid;
    $data->context = $coursecontext;
    //output page + form
    echo $OUTPUT->heading(get_string('editingpage', MOD_SIMPLELESSON_LANG), 2);
    $mform->display();
    echo 'we are here: ' . $PAGE->url;
    echo $OUTPUT->footer();
    return;
}

echo $OUTPUT->footer();
