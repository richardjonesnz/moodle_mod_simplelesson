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
 * Define the add question form elements
 */
class simplelesson_add_question_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;  

        $mform->addElement('static', 'label1', 'select_questions', 
                get_string('select_questions', MOD_SIMPLELESSON_LANG));
        
        $questions = $this->_customdata['questions'];
        $n = 0;
        foreach($questions as $question) {
            $n++;
            $check_name = 'q' . $question->id;  
            $mform->addElement('advcheckbox', $check_name,  
                $question->name,
                null, array('group' => 1), array(0, (int) $question->id));
        $mform->setType($check_name, PARAM_TEXT);   
        }

        // Hidden fields
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid', $this->_customdata['simplelessonid']);
        
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);

        $this->add_action_buttons($cancel=true);
    }
}
