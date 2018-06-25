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
 * A scheduled task to clean unwanted question usages.  These
 * remain when a lesson is aborted unexpectedly by the user.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/justinhunt/moodle-mod_pairwork
 */
namespace mod_simplelesson\task;
defined('MOODLE_INTERNAL') || die();

/**
 * The scheduled task.
 *
 */
class scheduled_clean extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('clean_up_usages', 'mod_simplelesson');
    }

    /**
     *  Run the cleanup task
     */
    public function execute() {
        return \mod_simplelesson\local\attempts::remove_all_usage_data();
    }
}
