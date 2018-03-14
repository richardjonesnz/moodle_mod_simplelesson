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
 * Page utilities for simplelesson
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
class pages  {

    /** 
     * Get the page titles for the prev/next drop downs
     * keys are the page values, text is the page title
     *
     * @param int $simplelessonid the id of a simplelesson
     * @param int $pageid id of current page
     * @return array of pageid=>titles of pages in the simplelesson
     */
    public static function fetch_page_titles($simplelessonid, 
            $pageid) { 
        $page_titles = array();
        $pagecount = self::count_pages($simplelessonid);
        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++ ) {
                $pid = self::get_page_id_from_sequence($simplelessonid, $p);
                $data = self::get_page_record($pid);
                // head 'em off at the pass, don't add link 
                // to self
                if ($pid != $pageid)  { 
                    $page_titles[$pid] = $data->pagetitle;  
                }
           }
        }
        // Add a "none" link
        $page_titles[0] = 
                    get_string('nolink', MOD_SIMPLELESSON_LANG);

        return $page_titles;
    }
    /** 
     * Get the page links for the simplelesson index
     *
     * @param int $simplelessonid the id of a simplelesson
     * @param int $course id
     * @param boolean $homepage true if this is the home page
     * @return array of links to pages in the simplelesson
     */
    public static function fetch_page_links($simplelessonid, $courseid, $homepage) { 
        global $CFG; 
        require_once($CFG->libdir . '/weblib.php');
        require_once($CFG->libdir . '/outputcomponents.php');
        $page_links = array();
        // Make the home link, if required
        if (!$homepage) {
            $return_view = new \moodle_url('/mod/simplelesson/view.php', 
                    array('n' => $simplelessonid));
            $page_links[] =  \html_writer::link($return_view, 
                    get_string('homelink', MOD_SIMPLELESSON_LANG));
        }
        // Count the content pages and make the links
        $pagecount = self::count_pages($simplelessonid);
        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++ ) {
                $pageid = self::get_page_id_from_sequence(
                        $simplelessonid, $p);
                $data = self::get_page_record($pageid);
                $page_url = new 
                        \moodle_url('/mod/simplelesson/showpage.php', 
                        array('courseid' => $courseid, 
                        'simplelessonid' => $data->simplelessonid, 
                        'pageid' => $pageid));
                $link = \html_writer::link($page_url, 
                        $data->pagetitle);
                $page_links[] = $link;   
           }
        }
       return $page_links;
    }
    /** 
     * Count the number of pages in a lesson
     *
     * @param int $simplelessonid the id of a simplelesson
     * @return int the number of pages in the database that lesson has
     */
    public static function count_pages($simplelessonid) {
        global $DB;
        
        return $DB->count_records('simplelesson_pages', 
                array('simplelessonid'=>$simplelessonid));
    }
    /** 
     * Check if this is the last page of the lesson
     *
     * @param object $data the simplelesson object
     * @return boolean true if this is the last page
     */
    public static function is_last_page($data) { 
        return ($data->sequence == self::count_pages($data->simplelessonid));
    }
    /** 
     * Verify page record exists in database
     *
     * @param int $pageid the id of a simplelesson page
     * @param int $simplelessonid the id of a simplelesson
     * @return boolean
     */
    public static function has_page_record($pageid, $simplelessonid) {
        global $DB;
        return $DB->record_exists('simplelesson_pages', 
                array('id' => $pageid, 'simplelessonid'=>$simplelessonid));
    }
    
    /** 
     * Add new page record
     *
     * @param int $data from edit_page form
     * @param object $context, the module context
     * @return int pageid
     */

    public static function add_page_record($data, $context) {
        global $DB;

        $pagecontentsoptions = simplelesson_get_editor_options($context);
        
        // insert a dummy record and get the id
        $data->timecreated = time();
        $data->timemodified = time();
        $data->pagecontents = ' ';
        $data->pagecontentsformat = FORMAT_HTML;
        $dataid = $DB->insert_record('simplelesson_pages', $data); 

        $data->id = $dataid;

        $data = file_postupdate_standard_editor(
                $data,
                'pagecontents',
                $pagecontentsoptions, 
                $context, 
                'mod_simplelesson',
                'pagecontents', 
                $data->id);

        $DB->update_record('simplelesson_pages', $data);

        return $data->id;
    }
    /** 
     * Update a page record
     *
     * @param int $data from edit_page form
     * @param object $context, the module context
     */
    public static function update_page_record($data, $context) {
        global $DB;

        $pagecontentsoptions = simplelesson_get_editor_options($context);      
        $data->timemodified = time();

        $data = file_postupdate_standard_editor(
                $data,
                'pagecontents',
                $pagecontentsoptions, 
                $context, 
                'mod_simplelesson',
                'pagecontents', 
                $data->id);

        $DB->update_record('simplelesson_pages', $data);
    }
    /** 
     * Given a lesson id and sequence number, find that page record
     *
     * @param int $simplelessonid the lesson id
     * @param int $sequence, where the page is in the lesson sequence
     * @return int pageid 
     */

    public static function get_page_id_from_sequence($simplelessonid, 
            $sequence) {
        global $DB;  
        $data = $DB->get_record('simplelesson_pages', 
                array('simplelessonid' => $simplelessonid, 
                'sequence' => $sequence));
        return $data->id;
    }
    /** 
     * Given a lesson id and page record, find that sequence number
     *
     * @param int $simplelessonid the lesson id
     * @param int $pageid
     * @return int where the page is in the lesson sequence 
     */

    public static function get_page_sequence_from_id(
            $simplelessonid, $pageid) {
        global $DB;  
        $sequence = $DB->get_field('simplelesson_pages', 
            'sequence',  array('id' => $pageid));
        return $sequence;    
    }

    /** 
     * Given a page id return the data for that page record
     *
     * @param int $pageid the page id
     * @return object representing the record
     */
    public static function get_page_record($pageid) {
        global $DB;
        return $DB->get_record('simplelesson_pages', 
                array('id' => $pageid), '*', MUST_EXIST);
    }
    /** 
     * Given a simplelesson, return the page data
     *
     * @param int $simplelessonid the simplelesson instance
     * @return array of page records for the simplelesson
     */
    public static function get_page_records($simplelessonid) {
        global $DB;
        // Count the content pages and get the sequence id's
        $pagecount = self::count_pages($simplelessonid);
        $page_records = array();

        if ($pagecount != 0) {
            for ($p = 1; $p <= $pagecount; $p++ ) {
                $pageid = self::get_page_id_from_sequence($simplelessonid, $p);
                $data = self::get_page_record($pageid);
                $page_records['pagetitle'] = $data->pagetitle;
                $page_records['sequence'] = $data->sequence;
                $page_records['pageid'] = $data->id;   
           }
        }
       return $page_records;
    }
    /** 
     * Given a simplelesson and sequence number
     * Move the page by exchanging sequence numbers
     *
     * @param int $simplelessonid the simplelesson instance
     * @param int $sequence the page sequence number
     * @return none
     */
    public static function move_page_up($simplelessonid, $sequence) {
        global $DB;
        
        $pageid_up = self::get_page_id_from_sequence(
                $simplelessonid, $sequence);
        $pageid_down = self::get_page_id_from_sequence(
                $simplelessonid, ($sequence - 1));

        self::decrement_page_sequence($pageid_up);
        self::increment_page_sequence($pageid_down);
    }

    /** 
     * Given a simplelesson and sequence number
     * Move the page by exchanging sequence numbers
     *
     * @param int $simplelessonid the simplelesson instance
     * @param int $sequence the page sequence number
     * @return none
     */
    public static function move_page_down($simplelessonid, $sequence) {
        global $DB;
        
        $pageid_down = self::get_page_id_from_sequence(
                $simplelessonid, $sequence);
        $pageid_up = self::get_page_id_from_sequence(
                $simplelessonid, ($sequence + 1));

        self::increment_page_sequence($pageid_down);
        self::decrement_page_sequence($pageid_up);
    }
     
   /** 
     * Given a page record id
     * decrease the sequence number by 1
     *
     * @param int $pageid
     * @return none
     */  
    public static function decrement_page_sequence($pageid) {
        global $DB;
        $sequence = $DB->get_field('simplelesson_pages', 
            'sequence',  
            array('id' => $pageid));
        $DB->set_field('simplelesson_pages', 
            'sequence', ($sequence - 1),  
            array('id' => $pageid));
    }

   /** 
     * Given a page record id
     * increase the sequence number by 1
     *
     * @param int $pageid
     * @return none
     */  
    public static function increment_page_sequence($pageid) {
        global $DB;
        $sequence = $DB->get_field('simplelesson_pages', 
                'sequence',  
                array('id' => $pageid));
        $DB->set_field('simplelesson_pages', 
                'sequence', ($sequence + 1),  
                array('id' => $pageid));
    }
    /** 
     * Given a page record id
     * return its title
     *
     * @param int $pageid
     * @return String the title of the page
     */  
    public static function get_page_title($pageid) {
        global $DB;
        return $DB->get_field('simplelesson_pages', 
                'pagetitle',  
                array('id' => $pageid));
    }
    
}