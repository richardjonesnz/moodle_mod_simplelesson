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
     * Returns HTML to display a report tab
     *
     * @param $courseid - current course id
     * @param $simplelessonid - current instance id
     * @return string, a set of tabs
     */
    public static function show_reports_tab($courseid, $simplelessonid) {

        $tabs = $row = $inactive = $activated = array();
        $currenttab = '';
        $viewpage = new \moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));
        $reportspage = new \moodle_url(
                '/mod/simplelesson/reports.php',
        array('courseid' => $courseid,
                'simplelessonid' => $simplelessonid,
                'report' => 'menu'));

        $row[] = new \tabobject('view', $viewpage,
                get_string('viewtab', 'mod_simplelesson'));
        $row[] = new \tabobject('reports', $reportspage,
                get_string('reportstab', 'mod_simplelesson'));

        $tabs[] = $row;

        \print_tabs($tabs, $currenttab, $inactive, $activated);

    }
    /**
     * Returns HTML to a basic report of module usage
     *
     * @param $records - an array of data records
     * @return string, html table
     */
    public static function show_basic_report($records) {

        $table = new \html_table();
        $table->head = array(
                get_string('moduleid', 'mod_simplelesson'),
                get_string('simplelessonname', 'mod_simplelesson'),
                get_string('title', 'mod_simplelesson'),
                get_string('timecreated', 'mod_simplelesson'));
        $table->align = array('left', 'left', 'left', 'left');
        $table->wrap = array('nowrap', '', 'nowrap', '');
        $table->tablealign = 'left';
        $table->cellspacing = 0;
        $table->cellpadding = '2px';
        $table->width = '80%';
        foreach ($records as $record) {
            $data = array();
            $data[] = $record->id;
            $data[] = $record->name;
            $data[] = $record->title;
            $data[] = $record->timecreated;
            $table->data[] = $data;
        }

        return \html_writer::table($table);
    }
    /*
     * User Report - get the user attempt records for a lesson
     *
     * @param $simplelessonid - lesson to get records for
     * @return array of objects
     */
    public static function fetch_attempt_data($simplelessonid) {
        global $DB;
        $sql = "SELECT a.id, a.simplelessonid,
                       a.userid, a.status, a.sessionscore,
                       a.maxscore, a.timetaken, a.timecreated,
                       u.firstname, u.lastname
                  FROM {simplelesson_attempts} a
                  JOIN {user} u
                    ON u.id = a.userid
                 WHERE a.simplelessonid = :slid";


        $records = $DB->get_records_sql($sql,
                array('slid' => $simplelessonid));

        // Select and arrange for report/csv export.
        $table = array();
        foreach ($records as $record) {
            $data = new \stdClass();
            $data->firstname = $record->firstname;
            $data->lastname = $record->lastname;
            $data->datetaken = date("Y-m-d H:i:s",$record->timecreated);
            $data->status = $record->status;
            $data->sessionscore = $record->sessionscore;
            $data->maxscore = $record->maxscore;
            $data->timetaken = $record->timetaken;
            $table[] = $data;
        }
        return $table;
    }
    /**
     * Returns HTML to a user report of lesson attempts
     *
     * @param $records - an array of attempt records
     * @return string, html table
     */
    public static function show_attempt_report($records) {

        $table = new \html_table();
        $table->head = self::fetch_attempt_report_headers();
        $table->align = array('left', 'left', 'left', 'left',
                'left', 'left', 'left');
        $table->wrap = array('nowrap', '', 'nowrap','', '', '', '');
        $table->tablealign = 'left';
        $table->cellspacing = 0;
        $table->cellpadding = '2px';
        $table->width = '80%';
        foreach ($records as $record) {
            $data = array();
            $data[] = $record->firstname;
            $data[] = $record->lastname;
            $data[] = $record->datetaken;
            $data[] = $record->status;
            $data[] = $record->sessionscore;
            $data[] = $record->maxscore;
            $data[] = $record->timetaken;
            $table->data[] = $data;
        }
        return \html_writer::table($table);
    }
    /*
     * Page export - get the columns for attempts report
     *
     * @param none
     * @return array of column names
     */
    public static function fetch_attempt_report_headers() {
        $fields = array('firstname' => 'firstname',
        'lastname' => 'lastname',
        'date' => 'date',
        'status' => "status",
        'sessionscore' => 'sessionscore',
        'maxscore' => 'maxscore',
        'timetaken' => 'timetaken');

        return $fields;
    }
    /*
     * User Report - get the user answer records for a lesson
     *
     * @param $simplelessonid - lesson to get records for
     * @return array of objects
     */
    public static function fetch_answer_data($simplelessonid) {
        global $DB;
        $sql = "SELECT a.id, a.attemptid,
                       a.mark, a.questionsummary, a.rightanswer,
                       a.youranswer,
                       a.timestarted, a.timecompleted,
                       t.userid, t.timecreated,
                       u.firstname, u.lastname
                  FROM {simplelesson_answers} a
                  JOIN {simplelesson_attempts} t
                    ON t.id = a.attemptid
                  JOIN {user} u
                    ON u.id = t.userid
                 WHERE a.simplelessonid = :slid";


        $records = $DB->get_records_sql($sql,
                array('slid' => $simplelessonid));

        // Select and order these for the csv export process.
        $table = array();
        foreach ($records as $record) {
          $data = new \stdClass();
          $data->id = $record->id;
          $data->attemptid = $record->attemptid;
          $data->firstname = $record->firstname;
          $data->lastname = $record->lastname;
          $data->datetaken = date("Y-m-d H:i:s",$record->timecreated);
          $data->questionsummary = $record->questionsummary;
          $data->rightanswer = $record->rightanswer;
          $data->youranswer = $record->youranswer;
          $data->timetaken = (int) ($record->timecompleted
                    - $record->timestarted);
          $table[] = $data;
        }
        return $table;
    }
    /*
     * Page export - get the columns for use answer report
     *
     * @param none
     * @return array of column names
     */
    public static function fetch_answer_report_headers() {
        $fields = array('id'=> 'id',
        'attemptid' => 'attemptid',
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'date' => 'date',
        'questionsummary' => 'questionsummary',
        'rightanswer' => 'rightanswer',
        'youranswer' => 'youranswer',
        'timetaken' => 'timetaken');

        return $fields;
    }
    /**
     * Returns HTML to a user report of lesson answers
     *
     * @param $records - an array of attempt records
     * @return string, html table
     */
    public static function show_answer_report($records) {

        $table = new \html_table();
        $table->head = self::fetch_answer_report_headers();
        $table->align = array(
                'left', 'left', 'left',
                'left', 'left', 'left',
                'left', 'left', 'left');
        $table->wrap = array('nowrap', '', 'nowrap',
                '', '', '', '', '', '');
        $table->tablealign = 'left';
        $table->cellspacing = 0;
        $table->cellpadding = '2px';
        $table->width = '80%';
        foreach ($records as $record) {
            $data = array();
            $data[] = $record->id;
            $data[] = $record->attemptid;
            $data[] = $record->firstname;
            $data[] = $record->lastname;
            $data[] = $record->datetaken;
            $data[] = $record->questionsummary;
            $data[] = $record->rightanswer;
            $data[] = $record->youranswer;
            $data[] = $record->timetaken;
            $table->data[] = $data;
        }
        return \html_writer::table($table);
    }
    /**
     * return the div showing the report menu buttons
     *
     * @param $courseid - current course id
     * @param $simplelessonid - current instance id
     * @return html to display buttons
     */
    public static function show_menu($courseid, $simplelessonid) {
        // Buttons on reports tab
        $buttons = array();

        $type = 'answers';
        $label = get_string('answer_report', 'mod_simplelesson');
        $buttons[] =  self::create_button($courseid,
            $simplelessonid, $type, $label);

        $type = 'attempts';
        $label = get_string('attempt_report', 'mod_simplelesson');
        $buttons[] =  self::create_button($courseid,
            $simplelessonid, $type, $label);

        return $buttons;
    }
    public static function create_button($courseid,
            $simplelessonid, $type, $label) {
        $pageurl = new \moodle_url('reports.php',
                array('courseid' => $courseid,
                'simplelessonid'=> $simplelessonid,
                'report' => $type));

        return new \single_button($pageurl, $label);
    }
}