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
 * Timing for the scheduled_clean cron task
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones (https://richardnz.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
defined('MOODLE_INTERNAL') || die();

$tasks = array(
    // The cron will delete all simplequestion usage records.
    // Run every week - normally won't have much to do.
    array('classname' => 'mod_simplelesson\task\scheduled_clean',
            'blocking' => 0,
            'minute' => '*',
            'hour' => '*',
            'day' => '*',
            'dayofweek' => '0',
            'month' => '*'
    )
);