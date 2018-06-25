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
        $sql = "SELECT s.id, s.qid, s.pageid, s.score,
                       q.name, q.questiontext
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
     * keys are the page sequence values,
     * text is the page title
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
                $pagetitles[$p] = pages::get_page_title($pid);
            }
        }
        $pagetitles[0] = 'none';
        return $pagetitles;
    }
    /**
     * Given a question data record
     * update the pageid field and slot
     *
     * @param object $data the question data
     * @return none
     */
    public static function update_question_table($simplelessonid, $data) {
        global $DB;
        
        // The pagetitle is the sequence from the drop-down list.
        $pageid = pages::get_page_id_from_sequence($simplelessonid,
                    $data->pagetitle);
    
        // Put the pageid into the questions table.
        $DB->set_field('simplelesson_questions',
                'pageid', $pageid,
                array('id' => $data->id,
                'simplelessonid' => $simplelessonid));
        
        // Save the user score.
        $DB->set_field('simplelesson_questions',
                'score', $data->score,
                array('id' => $data->id,
                'simplelessonid' => $simplelessonid));
        
        // Remove any slot number too.
        $DB->set_field('simplelesson_questions',
                'slot', 0,
                array('id' => $data->id,
                'simplelessonid' => $simplelessonid));
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
     * Given a simplelessonid and pageid
     * return the question number
     *
     * @param int $simplelesson the module instance
     * @param int $pageid the page
     * @return int question id from the table
     */
    public static function get_questionid($simplelessonid,
            $pageid) {
        global $DB;
        return $DB->get_field('simplelesson_questions',
                'qid', array(
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
    /**
     * Given a question id find the type
     *
     * @param int $qid - the question id
     * @return string $type the type of question
     */
    public static function fetch_question_type($qid) {
        global $DB;
        $data = $DB->get_record('question',
                  array('id' => $qid),
                  'qtype', MUST_EXIST);
        return $data->qtype;
    }
    /**
     * Given a question id find the score assigned
     *
     * @param int $qid - the question id
     * @return int $score the score allocated by the teacher
     */
    public static function fetch_question_score($simplelessonid,
            $pageid) {
        global $DB;
        $data = $DB->get_record('simplelesson_questions',
                  array('simplelessonid' => $simplelessonid,
                  'pageid' => $pageid),
                  'score', MUST_EXIST);
        return $data->score;
    }
    /**
     * Add up the questions scores for the lesson
     *
     * @param int $simplelessonid - id of the lesson
     * @return int the maximum possible score for questions in this lesson
     */
    public static function get_maxscore($simplelessonid) {
        global $DB;

        $sql = "SELECT s.id, s.simplelessonid, s.score, s.slot
                  FROM {simplelesson_questions} s
                 WHERE s.simplelessonid = :slid
                   AND s.slot <> 0";
        $entries = $DB->get_records_sql($sql,
              array('slid' => $simplelessonid));

        $maxscore = 0;
        foreach ($entries as $entry) {
            $maxscore += $entry->score;
        }
        return $maxscore;
    }
    /**
     * Given a simplelessonid and question id, find out the
     * page sequence number for the select list.
     *
     * @param int $qid, the question id in the questions table
     * @param simplelessonid - the simplelesson instance id.
     * @return int the page sequence number
     */
    public static function get_page_sequence($qid, $simplelessonid) {
        global $DB;
           $pageid = $DB->get_field('simplelesson_questions',
                   'pageid', array('qid' => $qid,
                   'simplelessonid' => $simplelessonid));
           return pages::get_page_sequence_from_id($pageid);
    }
    /**
     * Given a simplelessonid and attemptid,
     * check all questions answered.
     *
     * @param int $simplelessonid - the simplelesson to be checked
     * @param int $attemptid - the attempt to be checked
     * @return bool true if all questions answered.
     */
    public static function attempt_completed($simplelessonid,
            $attemptid) {
        global $DB;

        $questions = $DB->count_records('simplelesson_questions',
                array('simplelessonid' => $simplelessonid));

        return $questions == $answered;
    }
}