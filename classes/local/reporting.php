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
use \mod_simplelesson\local\display_options;
use \mod_simplelesson\local\constants;
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

    /**
     * Get the user attempt records for essay questions in a lesson
     *
     * @param $simplelessonid - lesson to get records for
     * @return array of objects
     */
    public static function fetch_essay_answer_data($simplelessonid) {
        global $DB;
        $sql = "SELECT a.id, a.simplelessonid, a.attemptid,
                       a.questionsummary, a.youranswer, a.qtype,
                       a.maxmark, a.mark, a.timecompleted, t.userid,
                       u.firstname, u.lastname, u.deleted
                  FROM {simplelesson_answers} a
                  JOIN {simplelesson_attempts} t
                    ON t.id = a.attemptid
                  JOIN {user} u
                    ON u.id = t.userid
                 WHERE a.simplelessonid = :slid
                   AND a.qtype LIKE :qtype
                   AND u.deleted <> 1
                   AND a.mark < 0"; // Negative mark = ungraded.

        $records = $DB->get_records_sql($sql,
                array('slid' => $simplelessonid,
                'qtype' => 'essay'));

        return $records;
    }
    /**
     * Return a table of essay report data
     *
     * @param int $simplelessonid - lesson to get records for
     * @param array of objects $records - answer records
     * @return array $table records formatted for report
     */
    public static function get_essay_report_data($simplelessonid,
            $records) {
        global $DB;

        $courseid = $DB->get_field('simplelesson', 'course',
            array('id' => $simplelessonid), MUST_EXIST);

        // Select and arrange records for grading report.
        $table = array();
        foreach ($records as $record) {
            $data = new \stdClass();
            $data->id = $record->id;
            $data->firstname = $record->firstname;
            $data->lastname = $record->lastname;
            $data->datetaken = date("Y-m-d H:i:s",
                    $record->timecompleted);
            $data->status = get_string('requires_grading',
                    'mod_simplelesson');
            $gradeurl = new \moodle_url(
                    'manual_grading.php',
                    array('courseid' => $courseid,
                    'simplelessonid' => $simplelessonid,
                    'answerid' => $data->id));
            $data->gradelink = \html_writer::link($gradeurl,
                    get_string('gradelink', 'mod_simplelesson'));
            $table[] = $data;
        }
        return $table;
    }
    /**
     * Returns HTML to a user report of lesson essay attempts
     *
     * @param $records - an array of attempt records
     * @return string, html table
     */
    public static function show_essay_answer_report($records) {

        $table = new \html_table();
        $table->head = self::fetch_essay_answer_report_headers();
        $table->align = array('left', 'left', 'left', 'left',
                'left');
        $table->wrap = array('nowrap', '', 'nowrap', '', '');
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
            $data[] = $record->gradelink;
            $table->data[] = $data;
        }
        return \html_writer::table($table);
    }
    /**
     * Page export - get the columns for essay grading report
     *
     * @param none
     * @return array of column names
     */
    public static function fetch_essay_answer_report_headers() {
        $fields = array(
                'firstname' =>
                get_string('firstname', 'mod_simplelesson'),
                'lastname' =>
                get_string('lastname', 'mod_simplelesson'),
                'date' =>
                get_string('date', 'mod_simplelesson'),
                'status' =>
                get_string('status', 'mod_simplelesson'),
                'gradelink' =>
                get_string('gradelinkheader', 'mod_simplelesson'));

        return $fields;
    }
    /**
     * Fetch the data for a particular essay
     *
     * @param int $answerid - the id of the wanted record
     * @return object the wanted record
     */
    public static function fetch_essay_answer_record($answerid) {
        global $DB;

        $sql = "SELECT a.id, a.youranswer, a.maxmark, a.mark,
                       a.youranswer, a.timecompleted, t.userid,
                       u.firstname, u.lastname, u.deleted
                  FROM {simplelesson_answers} a
                  JOIN {simplelesson_attempts} t
                    ON t.id = a.attemptid
                  JOIN {user} u
                    ON u.id = t.userid
                 WHERE a.id = :aid
                   AND u.deleted <> 1";

        return $DB->get_record_sql($sql,
                array('aid' => $answerid), MUST_EXIST);
    }
    /**
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
                       u.firstname, u.lastname, u.deleted
                  FROM {simplelesson_attempts} a
            INNER JOIN {user} u
                    ON u.id = a.userid
                 WHERE a.simplelessonid = :slid
                   AND u.deleted <> 1";

        $records = $DB->get_records_sql($sql,
                array('slid' => $simplelessonid));

        // Select and arrange for report/csv export.
        $table = array();
        foreach ($records as $record) {
            $data = new \stdClass();
            $data->firstname = $record->firstname;
            $data->lastname = $record->lastname;
            $data->datetaken = date("Y-m-d H:i:s", $record->timecreated);
            $status = ($record->status ==
                    constants::MOD_SIMPLELESSON_ATTEMPT_STARTED) ?
                    "Incomplete" : "Complete";
            $data->status = $status;
            $data->sessionscore = (int) $record->sessionscore;
            $data->maxscore = (int) $record->maxscore;
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
        $table->wrap = array('nowrap', '', 'nowrap', '', '',
                '', '');
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
    /**
     * Page export - get the columns for attempts report
     *
     * @param none
     * @return array of column names
     */
    public static function fetch_attempt_report_headers() {
        $fields = array(
                'firstname' =>
                get_string('firstname', 'mod_simplelesson'),
                'lastname' =>
                get_string('lastname', 'mod_simplelesson'),
                'date' =>
                get_string('date', 'mod_simplelesson'),
                'status' =>
                get_string('status', 'mod_simplelesson'),
                'sessionscore' =>
                get_string('sessionscore', 'mod_simplelesson'),
                'maxscore' =>
                get_string('maxscore', 'mod_simplelesson'),
                'timetaken' =>
                get_string('timetaken', 'mod_simplelesson'));

        return $fields;
    }

    /**
     * User Report - get the user answer records for a lesson
     *
     * @param $simplelessonid - lesson to get records for
     * @return array of objects
     */
    public static function fetch_answer_data($simplelessonid) {
        global $DB;
        $sql = "SELECT a.id, a.attemptid, a.maxmark,
                       a.mark, a.questionsummary, a.rightanswer,
                       a.youranswer, a.mark, a.maxmark,
                       a.timestarted, a.timecompleted,
                       t.userid, t.timecreated,
                       u.firstname, u.lastname, u.deleted
                  FROM {simplelesson_answers} a
            INNER JOIN {simplelesson_attempts} t
                    ON t.id = a.attemptid
            INNER JOIN {user} u
                    ON u.id = t.userid
                 WHERE a.simplelessonid = :slid
                   AND u.deleted <> 1";

        $records = $DB->get_records_sql($sql,
                array('slid' => $simplelessonid));
        $options = new display_options();
        $markdp = $options->markdp;
        // Select and order these for the csv export process.
        $table = array();
        foreach ($records as $record) {
            $data = new \stdClass();
            $data->id = $record->id;
            $data->attemptid = $record->attemptid;
            $data->firstname = $record->firstname;
            $data->lastname = $record->lastname;
            $data->datetaken = date("Y-m-d H:i:s",
                    $record->timecreated);
            $data->questionsummary = $record->questionsummary;
            $data->rightanswer = $record->rightanswer;
            $data->youranswer = $record->youranswer;
            $data->mark = round($record->mark, $markdp);
            $data->maxmark = round($record->maxmark, $markdp);
            $data->timetaken = (int) ($record->timecompleted
                    - $record->timestarted);
            $table[] = $data;
        }
        return $table;
    }
    /**
     * Page export - get the columns for use answer report
     *
     * @param none
     * @return array of column names
     */
    public static function fetch_answer_report_headers() {
        $fields = array('id' => 'id',
        'attemptid' => get_string('attemptid', 'mod_simplelesson'),
        'firstname' => get_string('firstname', 'mod_simplelesson'),
        'lastname' => get_string('lastname', 'mod_simplelesson'),
        'date' => get_string('date', 'mod_simplelesson'),
        'questionsummary' => get_string('questionsummary',
                'mod_simplelesson'),
        'rightanswer' => get_string('rightanswer',
                'mod_simplelesson'),
        'youranswer' => get_string('youranswer', 'mod_simplelesson'),
        'mark' => get_string('mark', 'mod_simplelesson'),
        'maxmark' => get_string('outof', 'mod_simplelesson'),
        'timetaken' => get_string('timetaken', 'mod_simplelesson'));

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
                'left', 'left', 'left',
                'left', 'left');
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
            if (strlen($record->youranswer) > 100) {
                $shortanswer = substr($record->youranswer,
                        0, 95) . '...';
            } else {
                $shortanswer = $record->youranswer;
            }
            $data[] = strip_tags(format_string($shortanswer));
            $mark = $record->mark;
            if ($mark < 0) {
                $data[] = get_string('ungraded', 'mod_simplelesson');
            } else {
                $data[] = $record->mark;
            }
            $data[] = $record->maxmark;
            $data[] = $record->timetaken;
            $table->data[] = $data;
        }
        return \html_writer::table($table);
    }
    /**
     * return the data for the report buttons
     *
     * @param $courseid - current course id
     * @param $simplelessonid - current instance id
     * @return html to display buttons
     */
    public static function show_menu($courseid, $simplelessonid) {
        // Buttons on reports tab.

        $data = new \stdClass;

        $data->class = 'mod_simplelesson_page_links';
        $data->buttonclass = 'btn btn-default';

        $data->linkdata = array();
        $baseparams = ['courseid' => $courseid,
                'simplelessonid' => $simplelessonid];

        $link = new \moodle_url('reports.php', $baseparams);
        $data->linkdata[] = ['link' => $link->out(false,
                ['report' => 'answers']),
                'text' => get_string('answer_report',
                'mod_simplelesson')];

        $link = new \moodle_url('reports.php', $baseparams);
        $data->linkdata[] = ['link' => $link->out(false,
                ['report' => 'attempts']),
                'text' => get_string('attempt_report',
                'mod_simplelesson')];

        $link = new \moodle_url('reports.php', $baseparams);
        $data->linkdata[] = ['link' => $link->out(false,
                ['report' => 'manualgrade']),
                'text' => get_string('manual_grade',
                'mod_simplelesson')];

        return $data;
    }
    /*
     * Attempts management- get all user attempt records
     *
     * @param $courseid - Course to get records for
     * @return array of objects
     */
    public static function fetch_course_attempt_data($courseid) {
        global $DB;
        $sql = "SELECT a.id, a.simplelessonid,
                       a.userid, a.status, a.sessionscore,
                       a.maxscore, a.timetaken, a.timecreated,
                       u.firstname, u.lastname, u.deleted,
                       s.name
                  FROM {simplelesson_attempts} a
            INNER JOIN {simplelesson} s
                    ON s.id = a.simplelessonid
            INNER JOIN {user} u
                    ON u.id = a.userid
                 WHERE s.course = :cid
                   AND u.deleted <> 1";

        $records = $DB->get_records_sql($sql,
                array('cid' => $courseid));

        // Select and arrange for report/csv export.
        $table = array();
        foreach ($records as $record) {
            $data = new \stdClass();
            $data->id = $record->id;
            $data->lessonname = $record->name;
            $data->firstname = $record->firstname;
            $data->lastname = $record->lastname;
            $data->datetaken = date("Y-m-d H:i:s",
                    $record->timecreated);
            $status = ($record->status ==
                    constants::MOD_SIMPLELESSON_ATTEMPT_STARTED) ?
                    "Incomplete" : "Complete";
            $data->status = $status;
            $data->sessionscore = (int) $record->sessionscore;
            $data->maxscore = (int) $record->maxscore;
            $data->timetaken = $record->timetaken;
            $table[] = $data;
        }
        return $table;
    }
    /**
     * Returns HTML to course report of lesson attempts
     *
     * @param array $records attempt records
     * @param int $courseid relevant course id
     * @return string, html table
     */
    public static function show_course_attempt_report($records,
        $courseid) {

        $table = new \html_table();
        $table->head = self::fetch_course_attempt_report_headers();
        $table->align = array('left', 'left', 'left', 'left', 'left', 'left', 'left');
        $table->wrap = array('nowrap', '', 'nowrap',
                '', '', '', '');
        $table->tablealign = 'left';
        $table->cellspacing = 0;
        $table->cellpadding = '2px';
        $table->width = '80%';

        foreach ($records as $record) {
            $data = array();
            $data[] = $record->id;
            $data[] = $record->firstname;
            $data[] = $record->lastname;
            $data[] = $record->lessonname;
            $data[] = $record->datetaken;
            $data[] = $record->status;
            $data[] = $record->sessionscore;
            $data[] = $record->maxscore;
            $data[] = $record->timetaken;$url = new \moodle_url(
                '/mod/simplelesson/manage_attempts.php',
                array('courseid' => $courseid,
                'action' => 'delete',
                'attemptid' => $record->id));
            $link = \html_writer::link($url, get_string('delete',
                'mod_simplelesson'));
            $data[] = $link;
            $table->data[] = $data;
        }
        return \html_writer::table($table);
    }
    /**
     * Attempts export - get the column names
     *
     * @param none
     * @return array of column names
     */
    public static function fetch_course_attempt_report_headers() {

        $fields = array('id' => 'id',
        'firstname' => get_string('firstname', 'mod_simplelesson'),
        'lastname' => get_string('lastname', 'mod_simplelesson'),
        'lessonname' => get_string('lessonname', 'mod_simplelesson'),
        'date' => get_string('date', 'mod_simplelesson'),
        'status' => get_string('status', 'mod_simplelesson'),
        'sessionscore' => get_string('sessionscore',
                'mod_simplelesson'),
        'maxscore' => get_string('maxscore', 'mod_simplelesson'),
        'timetaken' => get_string('timetaken', 'mod_simplelesson'),
        'action' => get_string('action', 'mod_simplelesson'));

        return $fields;
    }
}