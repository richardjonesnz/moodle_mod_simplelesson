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
 * Utilities for simplelesson
 *
 * @package    mod_simplelesson
 * @copyright 2015 Justin Hunt, modified 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 namespace mod_simplelesson;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility class for counting pages and so on
 *
 * @package    mod_simplelesson
 * @copyright 2015 Justin Hunt, modified 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utilities  {
    /** 
     * Count the number of pages in a lesson
     *
     * @param int $lessonid the id of a simplelesson
     * @return int the number of pages in the database that lesson has
     */
    public static function count_pages($lessonid) {
        global $DB;
        
        return $DB->count_records('simplelesson_pages', array('simplelessonid'=>$lessonid));
    }
    /** 
     * Verify page record exsists in database
     *
     * @param int $pageid the id of a simplelesson page
     * @param int $simplelessonid the id of a simplelesson
     * @return boolean
     */
    public static function has_page_record($pageid, $simplelessonid) {
        return $DB->get_record('simplelesson_pages', 
                array('id' => $pageid, 'simplelessonid'=>$simplelessonid), '*');
    }
    /** 
     * Add new page record
     *
     * @param int $data from edit_page form
     * @return int pageid
     */

    public static function add_page_record($data) {

        // Create a dummy record to insert
        $data->simplelessonid = $simplelessonid;
        $data->timecreated = time();
        $data->timemodified = time();
        $data->pagecontents ='';
        $data->pagecontentsformat =FORMAT_HTML;
        $dataid = $DB->insert_record('simplelesson_pages', $data);  
        $data->id = $dataid;

        // Update record with actual values to insert
        $context = $data->context;
        $editoroptions = simplelesson_get_editor_options($context);
        $data = file_postupdate_standard_editor($data,'pagecontents',
                        $editoroptions, $context, 'mod_simplelesson','pagecontents', $dataid);
        $DB->update_record('simplelesson_pages', $data);

        return $dataid;
    }
    /** 
     * Update a page record
     *
     * @param int $data from edit_page form
     * @param int $pageid, id of record to update
     * @return int pageid 
     */

    public static function update_page_record($data, $pageid) {

        return $pageid;

    }

}