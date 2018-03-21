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
 * Prints the simplelesson summary page
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

// Course module or module instance 
$id = optional_param('id', 0, PARAM_INT); // course_module ID
$n  = optional_param('n', 0, PARAM_INT);  // instance ID 
// could be preview or attempt
$mode  = optional_param('mode', 'none', PARAM_TEXT);  

if ($id) {
    $cm         = get_coursemodule_from_id('simplelesson', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record('simplelesson', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record('simplelesson', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('simplelesson', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

$PAGE->set_url('/mod/simplelesson/summary.php', array('id' => $cm->id));
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

//  are we a teacher or a student?
$view_mode= "view";

// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

//Get an instance setting.
$lessontitle = $moduleinstance->lessontitle;
$activityname = $moduleinstance->name;

// Declare renderer for page output.
$renderer = $PAGE->get_renderer('mod_simplelesson');

//if we are teacher we see tabs. If student we just see the page.
if(has_capability('mod/simplelesson:preview',$modulecontext)) {
    echo $renderer->header($lessontitle, $activityname);
} else {
    echo $renderer->notabsheader();
}

$attempts =  $DB->get_records(MOD_SIMPLELESSON_USERTABLE,
        array('userid'=>$USER->id, 
        MOD_SIMPLELESSON_MODNAME.'id'=>$moduleinstance->id));

// Get the page links. 
$page_links = \mod_simplelesson\local\pages::fetch_page_links(
            $moduleinstance->id, $course->id, true);

//if we are teacher we see buttons.
if(has_capability('mod/simplelesson:manage', $modulecontext)) {

    echo $renderer->lesson_editing_links($course->id, $cm->id,
            $moduleinstance->id);
}

// Finish the page.
echo $renderer->footer();
