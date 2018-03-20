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
namespace mod_simplelesson\local;
require_once('../../config.php'); 
defined('MOODLE_INTERNAL') || die();
/**
 * Utility class for counting pages and so on
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
    public static function get_questions($categoryid) {
        global $DB;
        return $DB->get_records('question',
              array('category' => $categoryid));
    }
    
    /** 
     * Given a question id and page id
     * save if data is unique
     *
     * @param object $qdata
     * @return id of inserted record or false
     */    
    public static function save_question($qdata) {
        global $DB;
        $table = 'simplelesson_questions';
        $condition = array('qid' => $qdata->qid, 
                'simplelessonid' =>$qdata->simplelessonid);
        // Check if this question was already added to this lesson
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
     * Get the page titles for the question manager
     * keys are the page values, text is the page title
     *
     * @param int $simplelessonid the id of a simplelesson
     * @return array of titles of pages in the simplelesson
     */
    public static function fetch_all_page_titles(
            $simplelessonid) { 
        $page_titles = array();
        $pagecount = 
                \mod_simplelesson\local\pages::
                count_pages($simplelessonid);
        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++ ) {
                $pid = 
                        \mod_simplelesson\local\pages::
                        get_page_id_from_sequence(
                        $simplelessonid, $p);
                $page_titles[$pid] = 
                        \mod_simplelesson\local\pages::
                        get_page_title($pid);
           }
        }
        $page_titles[0] = 'none';
        return $page_titles;
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
        return $DB->set_field('simplelesson_questions', 
                    'pageid', $data->pagetitle,  
                    array('id' => $data->id));
    }  
    /** 
     * Given a simplelessonid update the slots field
     * for the lesson pages.
     *
     * @param int $simplelessonid the module instance id
     * @return none
     */  
    public static function set_slots($simplelessonid) {
        global $DB;
        $pagecount = \mod_simplelesson\local\pages::
                count_pages($simplelessonid);
        $slot = 1;
        for ($p = 1; $p <= $pagecount; $p++) {
            $pageid = 
                    \mod_simplelesson\local\pages::
                    get_page_id_from_sequence(
                    $simplelessonid,$p);
            if (self::page_has_question($simplelessonid, 
                    $pageid)) {
                $data = $DB->get_record('simplelesson_questions', 
                        array('simplelessonid' => $simplelessonid,
                        'pageid' => $pageid), '*', MUST_EXIST);
                $data->slot = $slot;
                $DB->update_record('simplelesson_questions', $data);
                $slot++;
            }
        }
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
     * Given a simplelesson and page find out if
     * it has a question entry in the questions table
     *
     * @param int $simplelessonid
     * @param int $pageid
     * @return int the id of the question or 0
     */  
    public static function page_has_question($simplelessonid,
            $pageid) {
        global $DB;
        $q = $DB->get_record('simplelesson_questions',
                  array('pageid' => $pageid, 
                  'simplelessonid' => $simplelessonid),
                  'qid', IGNORE_MISSING);
        if (!$q) {
            return 0;
        }   
        return $q->qid;
    }      
}