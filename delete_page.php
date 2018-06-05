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
 * Delete current page, adjusting sequence numbers as necessary
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_simplelesson\local\pages;
use \mod_simplelesson\event\page_deleted;
require_once('../../config.php');
require_once('edit_page_form.php');
defined('MOODLE_INTERNAL') || die();
global $DB;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
// Sequence in which pages are added to this lesson.
$sequence = required_param('sequence', PARAM_INT);
$returnto = optional_param('returnto', 'view', PARAM_TEXT);

// Set course related variables.
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid,
        $courseid, false, MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/delete_page.php',
        array('courseid' => $courseid,
              'simplelessonid' => $simplelessonid,
              'sequence' => $sequence));
require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

$returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));

$returnedit = new moodle_url('/mod/simplelesson/edit.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid));

// Check if any other pages point to this page and fix their links.
$pageid = pages::get_page_id_from_sequence($simplelessonid, $sequence);
pages::fix_page_links($simplelessonid, $pageid);

// Log the page deleted event.
$page = $DB->get_record('simplelesson_pages',
        array('simplelessonid' => $simplelessonid,
        'id' => $pageid), '*', MUST_EXIST);
$event = page_deleted::create(array(
        'objectid' => $pageid,
        'context' => $modulecontext,
    ));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('simplelesson_pages', $page);
$event->trigger();

// Delete the page.
$DB->delete_records('simplelesson_pages',
        array('simplelessonid' => $simplelessonid,
        'id' => $pageid));

// Find the sequence number of the current last.
$lastpage = pages::count_pages($simplelessonid);
$lastpage++; // Last page sequence number.
// Note the id's of pages to change their sequence numbers.
// Then get_page_id_from sequence only works if sequence is unique.
$pagestochange = array();
// We've deleted a page so lastpage is one short in terms
// of it's sequence number.
for ($p = $sequence + 1; $p <= $lastpage; $p++) {
    $thispage = pages::get_page_id_from_sequence($simplelessonid, $p);
    $pagestochange[] = $thispage;
}

// Change sequence numbers (decrement from deleted + 1 to end).
for ($p = 0; $p < count($pagestochange); $p++) {
    pages::decrement_page_sequence($pagestochange[$p]);
}

// Go back to page where request came from.
if ($returnto == 'edit') {
    redirect($returnedit, get_string('page_deleted', 'mod_simplelesson'), 2);
}
// Default.
redirect($returnview, get_string('page_deleted', 'mod_simplelesson'), 2);