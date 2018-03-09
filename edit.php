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
 * Edit a lesson and its pages
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

//fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT); 

// Set course related variables.
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);

//set up the page
$PAGE->set_url('/mod/simplelesson/edit.php', 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid,));

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

$renderer = $PAGE->get_renderer('mod_simplelesson');
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('lesson_editing', MOD_SIMPLELESSON_LANG), 2);

echo $renderer->page_management($course->id, $moduleinstance );

echo $OUTPUT->footer();
return;
