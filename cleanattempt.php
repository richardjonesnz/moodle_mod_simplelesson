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
 * Clean up usages and attempt data, explicitly
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\attempts;
use \question_engine;
require_once('../../config.php');
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$attemptid = required_param('attemptid', PARAM_INT);
$PAGE->set_url('/mod/simplelesson/cleanattempt.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'attemptid' => $attemptid));
require_course_login($courseid);

$qubaid = attempts::get_usageid($simplelessonid);
attempts::remove_usage_data($qubaid);

$returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
// Go back to home page.
redirect($returnview,
        get_string('attempt_completed', 'mod_simplelesson', 1));