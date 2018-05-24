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
    public static function fetch_user_data() {
        global $DB;
        $sql = "SELECT a.id, a.attemptid, a.simplelessonid,
                       a.timestarted, a.timecompleted,
                       t.userid, t.status, t.sessionscore,
                       t.maxscore, u.firstname, u.lastname
                  FROM {simplelesson_answers} a
                  JOIN {simplelesson_attempts} t
                    ON t.id = a.attemptid
                  JOIN {user} u
                    ON u.id = t.userid";


        $records = $DB->get_records_sql($sql);

        foreach ($records as $record) {
          $record->datetaken = date("Y-m-d H:i:s",$record->timestarted);
          $record->timetaken = (int) ($record->timecompleted
                    - $record->timestarted);
          $lessonname = $DB->get_record('simplelesson',
                    array('id' => $record->simplelessonid), 'name',
                    MUST_EXIST);
          $record->lessonname = $lessonname->name;
          $record->sessionscore = (int) $record->sessionscore;
          $record->maxscore = (int) $record->maxscore;
        }
        return $records;
    }
    /**
     * Returns HTML to a user report of lesson attempts
     *
     * @param $records - an array of attempt records
     * @return string, html table
     */
    public static function show_user_report($records) {

        $table = new \html_table();
        $table->head = array(
                get_string('firstname', 'mod_simplelesson'),
                get_string('lastname', 'mod_simplelesson'),
                get_string('date', 'mod_simplelesson'),
                get_string('lessonname', 'mod_simplelesson'),
                get_string('sessionscore', 'mod_simplelesson'),
                get_string('maxscore', 'mod_simplelesson'),
                get_string('timetaken', 'mod_simplelesson'));
        $table->align = array('left', 'left', 'left',
                'left', 'left', 'left', 'left');
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
            $data[] = $record->lessonname;
            $data[] = $record->sessionscore;
            $data[] = $record->maxscore;
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

        $type = 'basic';
        $label = get_string('basic_report', 'mod_simplelesson');
        $buttons[] =  self::create_button($courseid,
            $simplelessonid, $type, $label);

        $type = 'user';
        $label = get_string('user_report', 'mod_simplelesson');
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