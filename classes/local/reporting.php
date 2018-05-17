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
 * Defines report classes
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_pairwork
 */

namespace mod_simplelesson\local;

defined('MOODLE_INTERNAL') || die;

class reporting  {

    /*
     * Basic Report - get the module records for this course
     *
     * @param $courseid - course to get records for
     * @return array of objects
     */
    public static function fetch_module_data($courseid) {
        global $DB;
        $records = $DB->get_records('simplelesson',
                array('course' => $courseid), null, 'id, name, title, timecreated');

        foreach ($records as $record) {
            $record->timecreated = date("Y-m-d H:i:s", $record->timecreated);
        }
        return $records;
    }
}