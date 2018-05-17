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
 * Shows a simplelesson page
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_pairwork
 */
use mod_simplelesson\local\reporting;
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid  = required_param('simplelessonid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid,
        $courseid, false, MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/reports.php',
        array('courseid' => $courseid, 'simplelessonid' => $simplelessonid));

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/simplelesson:viewreportstab', $modulecontext);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_simplelesson');
echo $renderer->show_reports_tab($courseid, $simplelessonid);
$data = reporting::fetch_module_data($courseid);
echo $renderer->show_basic_report($data);

// Home link.
$returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
echo html_writer::link($returnview,
        get_string('homelink',  'mod_simplelesson'));

echo $OUTPUT->footer();