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
 *
 */
use \mod_simplelesson\local\pages;
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid  = required_param('simplelessonid', PARAM_INT);
$pageid = required_param('pageid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid,
        $courseid, false, MUST_EXIST);
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);
$PAGE->set_url('/mod/simplelesson/showpage.php',
        array('courseid' => $courseid, 'simplelessonid' => $simplelessonid,
        'pageid' => $pageid));

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

$config = get_config('mod_simplelesson');

echo $OUTPUT->header();
// Now get this page record.
$data = pages::get_page_record($pageid);

// Prepare page text, re-write urls.
$contextid = $modulecontext->id;
$data->pagecontents = file_rewrite_pluginfile_urls($data->pagecontents,
        'pluginfile.php', $contextid, 'mod_simplelesson', 'pagecontents',
        $pageid);

$renderer = $PAGE->get_renderer('mod_simplelesson');

// Show the page index if required
if ($moduleinstance->showindex) {
    $pagelinks = pages::fetch_page_links($courseid, $simplelessonid, $pageid);
    echo $renderer->fetch_index($pagelinks);
}

echo $renderer->show_page($data);
echo $renderer->show_page_nav_links($courseid, $data);

if (has_capability('mod/simplelesson:manage', $modulecontext)) {
    echo $renderer->show_page_edit_links($courseid, $data);
}

echo $OUTPUT->footer();