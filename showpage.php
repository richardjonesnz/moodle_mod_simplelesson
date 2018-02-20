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
 * Display a page for a given simplelesson instance
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

//fetch URL parameters
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT); 
$pageid = required_param('pageid', PARAM_INT);


$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($courseid);

//set up the page
$PAGE->set_url('/mod/simplelesson/show_page.php', 
        array('courseid' => $courseid, 
              'simplelessonid' => $simplelessonid, 
              'pageid' => $pageid));

$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

$return_url = new moodle_url('/mod/simplelesson/view.php', array('n' => $simplelessonid));

echo $OUTPUT->header();
$data = \mod_simplelesson\local\utilities::get_page_record($pageid);
//var_dump($data);
$renderer = $PAGE->get_renderer('mod_simplelesson');
echo $renderer->show_page($data);
echo $OUTPUT->footer();