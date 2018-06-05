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
 * @package mod_simplelesson
 * @copyright Richard Jones https://richardnz.net
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see mod_choice privacy.php 2018 Jun Pataleta
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

       $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm
                    ON cm.id = c.instanceid
                   AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m
                    ON m.id = cm.module
                   AND m.name = :modname
            INNER JOIN {simplelesson} ch ON ch.id = cm.instance
                  JOIN {simplelesson_attempts} co
                    ON ch.id = co.simplelessonid
                 WHERE co.userid = :userid";

        $params = [
            'modname'       => 'simplelesson',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid
        ];
        $contextlist = new contextlist();
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

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

         // Gather data for items specified in metadata.
        $sql = "SELECT cm.id AS cmid,
                       co.mark as mark,
                       co.youranswer as youranswer,
                       ca.status as status,
                       ca.sessionscore as sessionscore,
                       ca.timetaken as timetaken,
                       co.timestarted as timestarted
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {simplelesson} ch ON ch.id = cm.instance
            INNER JOIN {simplelesson_answers} co ON co.simplelessonid = ch.id
            INNER JOIN {simplelesson_attempts} ca ON ca.id = co.attemptid AND ca.simplelessonid = ch.id
                 WHERE c.id {$contextsql}
                       AND ca.userid = :userid
              ORDER BY cm.id";

        $params = ['modname' => 'simplelesson', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;

        // Track the last instance id.
        $lastcmid = null;

        // Get the data to export.
        $answers = $DB->get_recordset_sql($sql, $params);

        // Prepare the data for writing
        foreach ($answers as $answer) {

            // Check for last item.
            if ($lastcmid != $answer->cmid) {
                if (!empty($simplelessondata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_simplelesson_data_for_user(
                            $simplelessondata, $context, $user);
                }
                // Set up for next item.
                $simplelessondata = [
                    'mark' => [],
                    'youranswer' => [],
                    'status' => [],
                    'sessionscore' => [],
                    'timetaken' => [],
                    'timestarted' => \core_privacy\local\request\transform::datetime($answer->timestarted),
                ];
            }
            // Get the data for the last item.
            $simplelessondata['mark'][] = $answer->mark;
            $simplelessondata['youranswer'][] = $answer->youranswer;
            $simplelessondata['status'][] = $answer->status;
            $simplelessondata['sessionscore'][] =
                    $answer->sessionscore;
            $simplelessondata['timetaken'][] = $answer->timetaken;
            $lastcmid = $answer->cmid;
        }
        $answers->close();

        // Write the data for the last item.
        if (!empty($simplelessondata)) {
            $context = \context_module::instance($lastcmid);
            self::export_simplelesson_data_for_user(
                    $simplelessondata, $context, $user);
        }
    }
    /**
     * Prepare the data for export
     * @param array $choicedata the personal data to export.
     * @param \context_module $context the simplelesson context.
     * @param \stdClass $user the user record
     */
    protected static function export_simplelesson_data_for_user(
            array $simplelessondata, \context_module $context,
            \stdClass $user) {

        // Fetch the generic module data for the simplelesson.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with simplelesson data and write it.
        $contextdata = (object)array_merge((array)$contextdata,
                $simplelessondata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }
    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(
            \context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('simplelesson',
                $context->instanceid)) {

            $DB->delete_records('simplelesson_attempts',
                    ['simplelessonid' => $cm->instance]);
        }
    }
    /**
     * Delete all user data for the specified user,
     * in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     * a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(
            approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules',
                    'instance',
                    ['id' => $context->instanceid], MUST_EXIST);

            // Answer table doesn't have a userid field.
            // Get the attempt id to delete answers for.
            $attemptid = $DB->get_field('simplelesson_attempts',
                    'attemptid',
                    ['simplelessonid' => $instanceid,
                    'userid' => $userid]);

            if ($attemptid) {
                $DB->delete_records('simplelesson_answers',
                        ['simplelessonid' => $instanceid,
                        'attemptid' => $attemptid]);
            }

            $DB->delete_records('simplelesson_attempts',
                    ['simplelessonid' => $instanceid,
                    'userid' => $userid]);
        }
    }
}