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
 * Prints a particular instance of simplelesson
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_simplelesson
 * @copyright 2015 Justin Hunt, modified 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // simplelesson instance ID 

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

$PAGE->set_url('/mod/simplelesson/view.php', array('id' => $cm->id));
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

// Supports Moodle 3 onwards
// Trigger module viewed event.
$event = \mod_simplelesson\event\course_module_viewed::create(array(
        'objectid' => $moduleinstance->id,
        'context' => $modulecontext
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('simplelesson', $moduleinstance);
$event->trigger();

//if we got this far, we can consider the activity "viewed"
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

//are we a teacher or a student?
$mode= "view";

/// Set up the page header
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

//Get an instance setting
$lessontitle = $moduleinstance->lessontitle;
$activityname = $moduleinstance->name;

// Declare renderer for page output
$renderer = $PAGE->get_renderer('mod_simplelesson');

//if we are teacher we see tabs. If student we just see the page
if(has_capability('mod/simplelesson:preview',$modulecontext)) {
    echo $renderer->header($lessontitle, $activityname);
} else {
    echo $renderer->notabsheader();
}

//if we have too many attempts, lets report that.
if($moduleinstance->maxattempts > 0){
    $attempts =  $DB->get_records(MOD_SIMPLELESSON_USERTABLE,array('userid'=>$USER->id, 
            MOD_SIMPLELESSON_MODNAME.'id'=>$moduleinstance->id));
    if($attempts && count($attempts)<$moduleinstance->maxattempts) {
        echo get_string("exceededattempts",MOD_SIMPLELESSON_LANG,$moduleinstance->maxattempts);
    }
}

// Prepare firstpage text and re-write urls
$firstpagetext = $moduleinstance->firstpage;
$contextid = $modulecontext->id;
$firstpagetext = file_rewrite_pluginfile_urls($firstpagetext, 
        'pluginfile.php', 
        $contextid, 'mod_simplelesson', 
        'firstpage', $moduleinstance->id);

// Enable media filters
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $modulecontext;
$firstpagetext = format_text($firstpagetext, 
        $moduleinstance->firstpageformat, $formatoptions);

// Get the page links 
$page_links = \mod_simplelesson\local\utilities::fetch_page_links(
            $moduleinstance->id, $course->id, true);
$has_pages = (count($page_links) != 0);

$show_index = (int) $moduleinstance->show_index;

// Fetch the firstpage stuff
echo $renderer->fetch_firstpage($firstpagetext, 
        $show_index, $page_links);
if ($has_pages) {
    // Get the record # for the first page
    $pageid = 
            \mod_simplelesson\local\utilities::get_page_id_from_sequence(
                    $moduleinstance->id, 1);
    echo $renderer->fetch_firstpage_link($course->id, 
            $moduleinstance->id, $pageid);
}
//if we are teacher we see buttons
if(has_capability('mod/simplelesson:manage', $modulecontext)) {
    echo $renderer->fetch_module_edit_button($cm->id);
    //if there are no pages show an add link
    if (!$has_pages) {
        echo $renderer->add_firstpage_button($moduleinstance->id, 
                $course->id);
    } 
}

// Finish the page
echo $renderer->footer();
