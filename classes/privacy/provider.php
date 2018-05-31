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
 * Privacy implementation for GDPR
 *
 * @package    mod_simplelesson
 * @copyright  Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * This function provids the metadata for the user privacy register
     *
     * @param collection $items - the metadata collection to use
     * @return The updated collection
     */

    public static function get_metadata(collection $items)
            : collection {
        /*
        * The data tables and fields which hold data relevant to
        * user privacy requests.
        */

        $items->add_database_table (
            'simplelesson_attempts',
            [
            'userid' => 'privacy:metadata:simplelesson_attempts:userid',
            'status' => 'privacy:metadata:simplelesson_attempts:status',
            'sessionscore' =>
                    'privacy:metadata:simplelesson_attempts:sessionscore',
            'timetaken' =>
                    'privacy:metadata:simplelesson_attempts:timetaken'
            ],
            'privacy:metadata:simplelesson_attempts'
        );

        $items->add_database_table (
            'simplelesson_answers',
            [
            'mark' => 'privacy:metadata:simplelesson_answers:mark',
            'youranswer' => 'privacy:metadata:simplelesson_answers:youranswer',
            ],
            'privacy:metadata:simplelesson_answers'
        );

        $items->add_subsystem_link('core_question', [],
                'privacy:metadata:core_question');

        return $items;
    }

    /**
     * Get the list of contexts that contain user information
     * for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info
     * for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $contextlist = new contextlist();

        $sql = "
            SELECT DISTINCT ctx.id
            FROM {simplelesson} l
            JOIN {modules} m
              ON m.name = :simplelesson
            JOIN {course_modules} cm
              ON cm.instance = l.id
             AND cm.module = m.id
             JOIN {context} ctx
               ON ctx.instanceid = cm.id
              AND ctx.contextlevel = :modulelevel
        LEFT JOIN {simplelesson_attempts} at
               ON at.simplelessonid = l.id
        LEFT JOIN {simplelesson_answers} an
               ON an.attemptid = at.id
            WHERE l.id IS NOT NULL
              AND at.userid = :userid";

        $params = ['simplelesson' => 'simplelesson',
                'modulelevel' => CONTEXT_MODULE,
                'userid' => $userid];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }
    /**
     * Export all user data for the specified user
     * in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts
     * to export information for.
     */
    public static function export_user_data(approved_contextlist
            $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;



    }

    public static function delete_data_for_all_users_in_context(\context
            $context) {
        global $DB;
    }
    public static function delete_data_for_user(approved_contextlist
            $contextlist) {
        global $DB;


    }
}