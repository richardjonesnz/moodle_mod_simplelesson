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
 * Question utilities for simplelesson
 *
 * @package    mod_simplelesson
 * @copyright  Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\pages;
namespace mod_simplelesson\local;
defined('MOODLE_INTERNAL') || die();
/**
 * Utility class for handling questions from question bank
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questions  {
    /**
     * Given a category id
     * return an array of questions from that category
     *
     * @param int $categoryid
     * @return array of objects
     */
    public static function get_questions_from_category($categoryid) {
        global $DB;
        return $DB->get_records('question',
              array('category' => $categoryid));
    }

    /**
     * Given a question id
     * save if data is unique to simplelesson and pageid
     *
     * @param object $qdata
     * @return id of inserted record or false
     */
    public static function save_question($qdata) {
        global $DB;
        $table = 'simplelesson_questions';
        $condition = array('qid' => $qdata->qid,
                'simplelessonid' => $qdata->simplelessonid);
        // Only add the question to this if it doesn't exist.
        // Ie prevent duplicate questions for same lesson id.
        if (!$DB->get_record($table, $condition, IGNORE_MISSING)) {
            return $DB->insert_record($table, $qdata);
        }
        return false;
    }
    /**
     * Given a simplelessonid, find all its questions
     *
     * @param object $simplelesonid
     * @return array question display data
     */
    public static function fetch_questions($simplelessonid) {
        global $DB;
        $sql = "SELECT s.id, s.qid, s.pageid, q.name, q.questiontext, q.defaultmark
                  FROM {simplelesson_questions} s
                  JOIN {question} q ON s.qid = q.id
                 WHERE s.simplelessonid = :slid";
        $entries = $DB->get_records_sql($sql,
              array('slid' => $simplelessonid));
        return $entries;
    }
    /**
     * Given a simplelessonid, find all its questions
     * that are on a page.
     *
     * @param object $simplelesonid
     * @return array question display data
     */
    public static function fetch_attempt_questions($simplelessonid) {
        global $DB;
        $sql = "SELECT s.id, s.qid, s.pageid, q.name,
                       q.questiontext, q.defaultmark
                  FROM {simplelesson_questions} s
                  JOIN {question} q ON s.qid = q.id
                 WHERE s.simplelessonid = :slid
                   AND s.pageid <> 0";
        $entries = $DB->get_records_sql($sql,
              array('slid' => $simplelessonid));
        return $entries;
    }
    /**
     * Get the page titles for the question manager
     * keys are the page values, text is the page title
     *
     * @param int $simplelessonid the id of a simplelesson
     * @return array of titles of pages in the simplelesson
     */
    public static function fetch_all_page_titles(
            $simplelessonid) {
        $pagetitles = array();
        $pagecount =
                pages::count_pages($simplelessonid);
        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++) {
                $pid = pages::get_page_id_from_sequence(
                        $simplelessonid, $p);
                $pagetitles[$pid] = pages::get_page_title($pid);
            }
        }
        $pagetitles[0] = 'none';
        return $pagetitles;
    }
    /**
     * Given a question table id
     * update the pageid field
     *
     * @param int $data the question data
     * @return none
     */
    public static function update_question_table($data) {
        global $DB;
        $DB->set_field('simplelesson_questions',
                'pageid', $data->pagetitle,
                array('id' => $data->id));
        // Remove the slot number too.
        $DB->set_field('simplelesson_questions',
                'slot', 0,
                array('id' => $data->id));
    }
    /**
     * Given a simplelesson id and a page id
     *
     * @param int $simplelessonid the simplelesson id
     * @param int $pageid the relevant page id
     * @return boolean true if record exists in questions table
     */
    public static function page_has_question($simplelessonid, $pageid) {
        global $DB;
        return $DB->record_exists('simplelesson_questions',
                    array('simplelessonid' => $simplelessonid,
                    'pageid' => $pageid));
    }
    /**
     * Given a simplelessonid and pageid
     * return the slot number
     *
     * @param int $simplelesson the module instance
     * @param int $pageid the page
     * @return int a slot number from the table
     */
    public static function get_slot($simplelessonid,
            $pageid) {
        global $DB;
        return $DB->get_field('simplelesson_questions',
                'slot', array(
                'simplelessonid' => $simplelessonid,
                'pageid' => $pageid));
    }
    /**
     * Given a question id find the name
     *
     * @param int $qid - the question id
     * @return string $name the name of the question
     */
    public static function fetch_question_name($qid) {
        global $DB;
        $data = $DB->get_record('question',
                  array('id' => $qid),
                  'name', MUST_EXIST);
        return $data->name;
    }
}