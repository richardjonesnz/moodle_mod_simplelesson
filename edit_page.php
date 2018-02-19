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

global $DB, $PAGE, $OUTPUT;

// Fetch parameters
$cmid = required_param('id', PARAM_INT);  // module id
$courseid = required_param('courseid', PARAM_INT);

// Return to view page on error
$return_url = new moodle_url('/mod/simplelesson/view.php',
        array('id' => $cmid));

// Get course record
$cm = get_coursemodule_from_id('simplelesson', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_course_login($course);
$modulecontext = context_module::instance($cm->id);

// Set up page
$PAGE->set_url('/mod/simplelesson/edit_page.php', 
        array('id' => $cmid, 'courseid' => $courseid));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('admin');

// Form processing begins here
$mform = new simplelesson_edit_page_form();
if ($mform->is_cancelled()) {
    redirect($return_url, get_string('cancelled'), 2);
    exit;
} else if ($data = $mform->get_data()) {     
    echo 'process data';  
}
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();