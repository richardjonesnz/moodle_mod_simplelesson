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
 * delete current page, 
 * adjusting sequence numbers as necessary
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('edit_page_form.php');

global $DB;

//fetch URL parameters
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT); 
// sequence in which pages are added to this lesson
$sequence = required_param('sequence', PARAM_INT);
$pageid = required_param('pageid', PARAM_INT);
$returnto = optional_param('returnto', 'view', PARAM_TEXT);

// Set course related variables
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);

//set up the page
$PAGE->set_url('/mod/simplelesson/delete_page.php', 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid, 
              'sequence' => $sequence));
require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

$return_view = new moodle_url('/mod/simplelesson/view.php', 
        array('n' => $simplelessonid));

$return_edit = new moodle_url('/mod/simplelesson/edit.php', 
        array('courseid' => $courseid, 
        'simplelessonid' => $simplelessonid));

// Confirm dialog needed
// see: https://docs.moodle.org/dev/AMD_Modal

// Use set field to decrement the sequence numbers 
// from deleted page to last page.
$DB->delete_records('simplelesson_pages',  
        array('simplelessonid'=>$simplelessonid,
        'id' => $pageid));

$lastpage = 
        \mod_simplelesson\local\utilities::count_pages($simplelessonid);
$lastpage++; // last page sequence number
// Note the id's of pages to change
// get_page_id_from sequence only works if sequence is unique.
$pagestochange = array();
// We've deleted a page so lastpage is one short in terms
// of it's sequence number.
for ($p = $sequence + 1; $p <= $lastpage ; $p++) {
    $thispage = \mod_simplelesson\local\utilities::
            get_page_id_from_sequence($simplelessonid, $p);
    $pagestochange[] = $thispage;
}

// Change sequence numbers (decrement from deleted + 1 to end).
for ($p = 0; $p < sizeof($pagestochange); $p++) {

   \mod_simplelesson\local\utilities::
           decrement_page_sequence($pagestochange[$p]); 
}
// Go back to page where request came from
if ($returnto == 'edit') {
    redirect($return_edit, 
            get_string('page_deleted', MOD_SIMPLELESSON_LANG), 2);    
}
// default
redirect($return_view, get_string('page_deleted', MOD_SIMPLELESSON_LANG), 2);
