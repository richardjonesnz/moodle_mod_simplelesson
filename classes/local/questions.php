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
                'pageid' =>$qdata->pageid);
        // Check if this question was already added to this page
        if (!$DB->get_record($table, $condition, IGNORE_MISSING)) {
            return $DB->insert_record($table, $qdata);
        }
        return false;
    }
}