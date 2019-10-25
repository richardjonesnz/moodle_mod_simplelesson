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
 * Redirect the user to the appropriate submission related page
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */
require_once('../../config.php');
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('simplelesson', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course),
        '*', MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson',
            array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);
$modulecontext = context_module::instance($cm->id);
// Re-direct the user.
if (has_capability('mod/simplelesson:manage', $modulecontext)) {
    $url = new moodle_url('reports.php', array('courseid' => $cm->course,
        'simplelessonid' => $simplelesson->id));
} else {
    $url = new moodle_url('view.php', array('id' => $id));
}
redirect($url);