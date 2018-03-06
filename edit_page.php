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
$pageid = required_param('pageid', PARAM_INT);  // the page we're on
// sequence in which pages were added to this lesson
$sequence = required_param('sequence', PARAM_INT); 

// Set course related variables
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);

//set up the page
$PAGE->set_url('/mod/simplelesson/edit_page.php', 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid, 
              'pageid' => $pageid,
              'sequence' => $sequence));

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');



$return_showpage = new moodle_url('/mod/simplelesson/showpage.php', 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid, 
              'pageid' => $pageid));

//get the page editing form
$mform = new simplelesson_edit_page_form(null, 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid,
              'pageid'  => $pageid,
              'sequence' => $sequence,
              'context'=> $modulecontext));

//if the cancel button was pressed
if ($mform->is_cancelled()) {
    redirect($return_showpage, get_string('cancelled'), 2);
}

//if we have data, then our job here is to save it and return
if ($data = $mform->get_data()) {
    $data->sequence = $sequence;
    $data->simplelessonid = $simplelessonid;
    $data->nextpageid = (int) $data->nextpageid;
    $data->prevpageid = (int) $data->prevpageid;  
    $data->id = $pageid;
    \mod_simplelesson\local\utilities::update_page_record($data, $modulecontext);
    redirect($return_showpage, 
            get_string('page_updated', MOD_SIMPLELESSON_LANG), 2);
}

$data = new stdClass();
$data = \mod_simplelesson\local\utilities::get_page_record($pageid);
$data->id = $pageid;
$pagecontentsoptions = simplelesson_get_editor_options($modulecontext);

$data = file_prepare_standard_editor(
    $data, 
    'pagecontents',
    $pagecontentsoptions, 
    $modulecontext, 
    'mod_simplelesson', 
    'pagecontents',
    $data->id);
    
$mform->set_data($data); 
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('page_editing', MOD_SIMPLELESSON_LANG), 2);
$mform->display();
echo $OUTPUT->footer();
return;
