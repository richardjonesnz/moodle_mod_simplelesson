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
    /**
     * Return an array of lesson answers and associated data
     * for multiple attempts at a single lesson.
     *
     * @param $simplelessonid int id of simplelesson instance
     * @return object array with one or more rows of answer data
     */
    public static function get_lesson_answer_data($simplelessonid) {
        global $DB;
        // Get the records for this user on this attempt
        $sql = "SELECT  a.id, a.simplelessonid, a.qatid,
                        a.attemptid, a.pageid, a.timestarted,
                        a.timecompleted, t.userid
                  FROM  {simplelesson_answers} a
                  JOIN  {simplelesson_attempts} t ON a.attemptid = t.id
                   AND  a.simplelessonid = :slid";

        $answerdata = $DB->get_records_sql($sql,
                array('slid' => $simplelessonid));

        // Add the data for the summary table.
        foreach ($answerdata as $data) {

            // Get the records from our tables.
            $pagedata = $DB->get_record('simplelesson_pages',
                    array('id' => $data->pageid), '*',
                    MUST_EXIST);
            $questiondata = $DB->get_record('simplelesson_questions',
                    array('simplelessonid' => $data->simplelessonid,
                    'pageid' => $data->pageid), '*',
                    MUST_EXIST);

            // Add the page and question name.
            $data->pagename = pages::get_page_title($pagedata->id);
            $data->qname = questions::fetch_question_name($questiondata->qid);

            // We'll need the slot to get the response data.
            $data->slot = $questiondata->slot;

            // Get the record from the question attempt data.
            $qdata = $DB->get_record('question_attempts',
                    array('id' => $data->qatid), '*',
                    MUST_EXIST);
            $data->youranswer = $qdata->responsesummary;
            $data->rightanswer = $qdata->rightanswer;

            // Get the userdata.
            $userdata = $DB->get_record('user',
                    array('id' => $data->userid), '*',
                    MUST_EXIST);
            $data->userid = $userdata->id;
            $data->firstname = $userdata->firstname;
            $data->lastname = $userdata->lastname;
            $data->timetaken = date("s", ($data->timecompleted
                    - $data->timestarted));
        }

        return $answerdata;
    }
}