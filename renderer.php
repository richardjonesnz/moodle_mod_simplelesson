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


defined('MOODLE_INTERNAL') || die();

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_simplelesson
 * @copyright 2015 Justin Hunt, modified 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_simplelesson_renderer extends plugin_renderer_base {

	/**
     * Returns the header for the module
     *
     * @param mod $instance
     * @param string $currenttab current tab that is shown.
     * @param int    $item id of the anything that needs to be displayed.
     * @param string $extrapagetitle String to append to the page title.
     * @return string
     */
    public function header($lessontitle, $activityname) {

        //$context = context_module::instance($cm->id);

        // Header setup
        $this->page->set_title($this->page->course->shortname.": ".$activityname);
        $this->page->set_heading($this->page->course->fullname);
        $output = $this->output->header();

        $output .= $this->output->heading($lessontitle);

        return $output;
    }
	
	/**
     * Return HTML to display limited header
     */
      public function notabsheader() {
      	return $this->output->header();
      }
      
     /**
     * Returns the text for the first page
     *
     * @param object $instance
     * @return string
     */
    public function fetch_firstpage_text($moduleinstance, $firstpagetext) {
    	
        // Introductory text        
        $html =  $this->output->box_start();
        $html .= html_writer::start_div(MOD_SIMPLELESSON_CLASS . '_firstpagecontent');
        $html .=  $firstpagetext;
        $html .= html_writer::end_div();
        
        // Link to first content page - ToDo: add check that first page exists
        $url = new moodle_url('/mod/simplelesson/showpage.php',
                array('id' => $moduleinstance->id, 'page' => 1));
        $link = html_writer::link($url, get_string('gotofirstpage', MOD_SIMPLELESSON_LANG));
        $html .= html_writer::div($link, MOD_SIMPLELESSON_CLASS . '_firstpagecontent_link');
        
        $html .=  $this->output->box_end();
        return $html;

    }

    /**
     * Returns add first page button, used when no pages exist yet
     *
     * @param object $instance
     * @return string
     */
    public function fetch_firstpage_button($simplelessonid, $courseid) {

        $html =  $this->output->box_start();
        
        $url = new moodle_url('/mod/simplelesson/edit_page.php', 
                array('id' => $simplelessonid, 'courseid' => $courseid));
        $link = html_writer::link($url,get_string('addfirstpage', MOD_SIMPLELESSON_LANG));
        $text = '<p>' . get_string('nopages', MOD_SIMPLELESSON_LANG) . '</p>' . $link;
        $html .=  html_writer::div($text, MOD_SIMPLELESSON_CLASS . '_firstpage_editing');
        $html .=  $this->output->box_end();
        
        return $html;
    }

}