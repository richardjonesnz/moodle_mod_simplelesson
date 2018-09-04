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
 * This file keeps track of upgrades to the simplelesson module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package mod_simplelesson
 * @copyright 2018 Richard Jones <richardnz@outlook.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute simplelesson upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */

function xmldb_simplelesson_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();


    if ($oldversion < 2018082002) {

        // Define field for grading method.
        $table = new xmldb_table('simplelesson');
        $field = new xmldb_field('grademethod',
                XMLDB_TYPE_INTEGER, '4',
                XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1',
                'maxattempts');
        // Add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2018082002, 'simplelesson');
    }
    if ($oldversion < 2018090400) {

        // Define field to check answer state.
        $table = new xmldb_table('simplelesson_answers');
        $field = new xmldb_field('stateclass', XMLDB_TYPE_TEXT, null,
                null, null, null, null, 'youranswer');

        // Add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018090400, 'simplelesson');
    }
    return true;
}
