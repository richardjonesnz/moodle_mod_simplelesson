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
 * Add a page at the end of the sequence
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_simplelesson\local\pages;
use \mod_simplelesson\event\page_created;

require_once('../../config.php');
require_once('edit_page_form.php');

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
// Sequence in which pages are added to this lesson.
$sequence = required_param('sequence', PARAM_INT);

// Set course related variables.
$moduleinstance  = $DB->get_record('simplelesson',
        array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid,
        $courseid, false, MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/add_page.php',
        array('courseid' => $courseid,
              'simplelessonid' => $simplelessonid,
              'sequence' => $sequence));
require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

// For use with the re-direct.
$returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));

// Page link data.
$pagetitles = pages::fetch_page_titles($simplelessonid);

// Get the page editing form.
$mform = new simplelesson_edit_page_form(null,
        array('courseid' => $courseid,
              'simplelessonid' => $simplelessonid,
              'pageid' => 0,
              'sequence' => $sequence,
              'context' => $modulecontext,
              'pagetitles' => $pagetitles));

// If the cancel button was pressed.
if ($mform->is_cancelled()) {
    redirect($returnview, get_string('cancelled'), 2);
}
/*
 * If we have data, then our job here is to save it and return
 * We will always add pages at the end, moving pages is handled
 * elsewhere.
 */
if ($data = $mform->get_data()) {
    $lastpage = pages::count_pages($moduleinstance->id);
    $data->sequence = $lastpage + 1;
    $data->simplelessonid = $simplelessonid;
    $data->nextpageid = (int) $data->nextpageid;
    $data->prevpageid = (int) $data->prevpageid;
    pages::add_page_record($data, $modulecontext);

    // Trigger the page created event.
    $eventparams = array('context' => $modulecontext,
            'objectid' => $data->id);
    $event = page_created::create($eventparams);
    $event->add_record_snapshot('course', $PAGE->course);
    $event->add_record_snapshot($PAGE->cm->modname, $moduleinstance);
    $event->trigger();

    redirect($returnview, get_string('page_saved', 'mod_simplelesson'), 2);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('page_adding', 'mod_simplelesson'), 2);

// Show the form.
$mform->display();

// Finish the page.
echo $OUTPUT->footer();
