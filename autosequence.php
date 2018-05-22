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
require_once('../../config.php');
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
require_course_login($courseid);
$returnedit = new moodle_url('/mod/simplelesson/edit.php',
        array('courseid' => $courseid,
        'simplelessonid' => $simplelessonid));
pages::fix_page_sequence($simplelessonid);
// Go back to page where request came from.
redirect($returnedit,
        get_string('sequence_updated', 'mod_simplelesson'), 2);