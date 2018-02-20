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
 * Edit a page
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('edit_page_form.php');

//fetch URL parameters
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT); 
$pageid = optional_param('pageid', 0, PARAM_INT);
$pagesequence = optional_param('pagesequence', 1, PARAM_INT);

//Set course related variables
//$PAGE->set_course($COURSE);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($courseid);

//set up the page
$PAGE->set_url('/mod/simplelesson/edit_page.php', 
        array('courseid' => $courseid, 'simplelessonid' => $simplelessonid, 'pageid' => $pageid));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');

$return_url = new moodle_url('/mod/simplelesson/view.php', array('n' => $simplelessonid));

//get the page editing form
$mform = new simplelesson_edit_page_form(null, 
        array('context'=>$coursecontext, 
              'courseid' => $courseid, 
              'simplelessonid' => $simplelessonid));

//if the cancel button was pressed
if ($mform->is_cancelled()) {
    redirect($return_url, get_string('cancelled'), 2);
}

//if we have data, then our job here is to save it and return
if ($data = $mform->get_data()) {
    $data->context = $coursecontext;
    $data->id = $pageid;
    $data->sequence = $pagesequence;
    $data->simplelessonid = $simplelessonid;
    \mod_simplelesson\local\utilities::add_page_record($data);    
    redirect($return_url, get_string('updated'), 2);
}

echo $OUTPUT->header();

// Does the page record already exist
$data = new stdClass();
$data = $DB->get_record('simplelesson_pages', array('id'=>$pageid));


// If there is no page data, create a dummy record
/*
if(!$data || empty($data)) {
    $data = new stdClass();
    $pageid = \mod_simplelesson\local\utilities::make_dummy_page_record($data, $simplelessonid);
} 
*/
$mform->set_data($data);
    
// Header for the page
echo $OUTPUT->heading(get_string('page_editing', MOD_SIMPLELESSON_LANG), 2);
    
$mform->display();

echo $OUTPUT->footer();

return;