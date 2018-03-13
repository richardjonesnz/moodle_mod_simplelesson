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
 * @package    mod_simplelesson
 * @copyright 2015 Justin Hunt, modified 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // Add fields for first page
    if ($oldversion < 2018021301) {

        // Define editor fields for firstpage to be added to simplelesson
        $table = new xmldb_table('simplelesson');
        $fields=array();
        $fields[] = new xmldb_field('firstpage', XMLDB_TYPE_TEXT, 'medium', null, null, null, null,null);
        $fields[] = new xmldb_field('firstpageformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            null);
        $fields[] = new xmldb_field('showhide', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            null);

        // Add field timemodified
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_mod_savepoint(true, 2018021301, 'simplelesson');
    }

    // Add field for lesson page sequence number
    if ($oldversion < 2018022002) {

        // Define editor fields
        $table = new xmldb_table('simplelesson_pages');
        $field = new xmldb_field('sequence', XMLDB_TYPE_INTEGER, '8', 
                XMLDB_UNSIGNED, null, null, '0', 'simplelessonid');

            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        upgrade_mod_savepoint(true, 2018022002, 'simplelesson');
    }

    // Add field for show index
    if ($oldversion < 2018030504) {

        // Define editor fields for firstpage to be added to simplelesson
        $table = new xmldb_table('simplelesson');
        $field = new xmldb_field('show_index', 
                XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, 
                XMLDB_NOTNULL, null, '1', null);
 
        if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018030504, 'simplelesson');
    }
    // Add field for category
    if ($oldversion < 2018031306) {

        // Define editor fields for firstpage to be added to simplelesson
        $table = new xmldb_table('simplelesson');
        $field = new xmldb_field('category', 
                XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, 
                XMLDB_NOTNULL, null, '0', null);
 
        if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018031306, 'simplelesson');
    }
    // Add table to link questions and pages
    if ($oldversion < 2018031308) {

        // Define editor fields for new table
        $fields = array();

        $table = new xmldb_table('simplelesson_questions');

        $fields[] = new xmldb_field('id', 
                XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, 
                XMLDB_NOTNULL, XMLDB_SEQUENCE);
 
        $fields[] = new xmldb_field('qid', 
                XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, 
                XMLDB_NOTNULL, null, '0', null);

        $fields[] = new xmldb_field('pageid', 
                XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, 
                XMLDB_NOTNULL, null, '0', null);

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
            }
        }
        upgrade_mod_savepoint(true, 2018031308, 'simplelesson');
    }
    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
