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
 * Form for editing lesson pages
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../lib/formslib.php');
require_once('lib.php');
/**
 * Define the edit page form elements
 * Elements I will need - title, content editor, question picker, [file picker?]
 */
class simplelesson_edit_page_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;

        $mform->addElement('text', 'pagetitle', get_string('pagetitle', MOD_SIMPLELESSON_LANG), array('size'=>'64'));
        $mform->addRule('pagetitle', null, 'required', null, 'client');
        // $mform->addHelpButton('pagetitle', 'pagetitle', MOD_SIMPLELESSON_LANG);
        $mform->setType('pagetitle', PARAM_TEXT);                     
                                                        
        // page text - editor field
        $context = $this->_customdata['context'];
        $pagecontentsoptions = simplelesson_get_editor_options($this->_customdata['context']);
        
        $mform->addElement('editor', 'pagecontents_editor', 
                get_string('pagecontents', MOD_SIMPLELESSON_LANG), 
                null, $pagecontentsoptions);
        
        $mform->setType('pagecontents_editor', PARAM_RAW);
        $mform->addRule('pagecontents_editor', get_string('required'), 
                'required', null, 'client');

        $mform->addElement('select', 'prevpageid', get_string('getprevpage', MOD_SIMPLELESSON_LANG), $this->_customdata['page_titles']);
        $mform->addElement('select', 'nextpageid', get_string('getnextpage', MOD_SIMPLELESSON_LANG), $this->_customdata['page_titles']);
        
        // To add, question picker
        // need a utility function to scan the question bank

        $mform->setType('nextpage', PARAM_TEXT);  
        $mform->setType('prevpage', PARAM_TEXT);   

        // Hidden fields
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid', $this->_customdata['simplelessonid']);
        $mform->addElement('hidden', 'pageid', $this->_customdata['pageid']);
        $mform->addElement('hidden', 'sequence', $this->_customdata['sequence']);
        
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);
        $mform->setType('pageid', PARAM_INT);
        $mform->setType('sequence', PARAM_INT);

        $this->add_action_buttons($cancel=true);
    }

    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $context = $this->_customdata['context'];
            $pagecontentsoptions = simplelesson_get_editor_options($context);
            $default_values = (object) $default_values;
            $default_values = 
                    file_prepare_standard_editor(
                        $default_values, 
                        'pagecontents',
                        $pagecontentsoptions, 
                        $context, 
                        'mod_simplelesson', 
                        'pagecontents',
                        $default_values->id);
            $default_values = (array) $default_values;
        }
    }
}
