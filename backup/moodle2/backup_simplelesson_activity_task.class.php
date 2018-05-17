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
 * Defines backup_simplelesson_activity_task class.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/simplelesson/backup/moodle2/backup_simplelesson_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the simplelesson instance.
 *
 * @package   mod_simplelesson
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_simplelesson_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the simplelesson.xml file.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_simplelesson_activity_structure_step('simplelesson_structure', 'simplelesson.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts.
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts.
     * @return string the content with the URLs encoded.
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot.'/mod/simplelesson', '#');

        // Link to the list of simplelessons.
        $pattern = '#'.$base.'/index\.php\?id=([0-9]+)#';
        $replacement = '$@MULTIPAGEINDEX*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Link to one simplelesson by id.
        $pattern = '#'.$base.'/view\.php\?id=([0-9]+)#';
        $replacement = '$@MULTIPAGEVIEWBYID*$2@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Action for displaying a page.
        $pattern = '#'.$base.'/showpage\.php\?id=([0-9]+)(&|&amp;)pagedid=([0-9]+)#';
        $replacement = '$@MULTIPAGESHOWPAGE*$2*$4@$';
        $content = preg_replace($pattern, $replacement, $content);

        return $content;
    }
}
