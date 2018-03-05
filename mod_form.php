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
 * The main simplelesson instance configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_simplelesson
 * @copyright 2015 Justin Hunt, modified 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/simplelesson/lib.php');

/**
 * Module instance settings form
 */
class mod_simplelesson_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('simplelessonname', MOD_SIMPLELESSON_LANG), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'simplelessonname', MOD_SIMPLELESSON_LANG);

        // Adding the standard "intro" and "introformat" fields
        if($CFG->version < 2015051100){
        	$this->add_intro_editor();
        }else{
        	$this->standard_intro_elements();
		}

        //-------------------------------------------------------------------------------
        // Adding the rest of simplelesson settings, spreading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic
        $mform->addElement('static', 'label1', 'simplelessonsettings', get_string('simplelessonsettings', MOD_SIMPLELESSON_LANG));
        $mform->addElement('text', 'lessontitle', get_string('lessontitle', MOD_SIMPLELESSON_LANG), array('size'=>'64'));
        $mform->addRule('lessontitle', null, 'required', null, 'client');
        $mform->addHelpButton('lessontitle', 'lessontitle', MOD_SIMPLELESSON_LANG);
        $mform->setType('lessontitle', PARAM_TEXT);	
		        
        // First page text - editor field
        $firstpageoptions = simplelesson_get_editor_options($this->context);
        $mform->addElement('editor', 'firstpage_editor', get_string('firstpage', MOD_SIMPLELESSON_LANG), null, $firstpageoptions);
        $mform->setType('firstpage_editor', PARAM_RAW);
        $mform->addRule('firstpage_editor', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('firstpage_editor', 'firstpage', MOD_SIMPLELESSON_LANG);
        $mform->setDefault('firstpage_editor', 
                array('text'=> get_string('defaultfirstpagetext', MOD_SIMPLELESSON_LANG),
                'format'=>1));
        
        // Show page index?
        $mform->addElement('advcheckbox', 'show_index', 
                get_string('show_index', MOD_SIMPLELESSON_LANG), 
                get_string('show_index_text', MOD_SIMPLELESSON_LANG),
                null, array(0, 1));

		//attempts
        $attemptoptions = array(0 => get_string('unlimited', MOD_SIMPLELESSON_LANG),
                            1 => '1',2 => '2',3 => '3',4 => '4',5 => '5',);
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', MOD_SIMPLELESSON_LANG), $attemptoptions);
		
		// Grade.
        $this->standard_grading_coursemodule_elements();
        
        //grade options
        $gradeoptions = array(MOD_SIMPLELESSON_GRADEHIGHEST => get_string('gradehighest',MOD_SIMPLELESSON_LANG),
                            MOD_SIMPLELESSON_GRADELOWEST => get_string('gradelowest', MOD_SIMPLELESSON_LANG),
                            MOD_SIMPLELESSON_GRADELATEST => get_string('gradelatest', MOD_SIMPLELESSON_LANG),
                            MOD_SIMPLELESSON_GRADEAVERAGE => get_string('gradeaverage', MOD_SIMPLELESSON_LANG),
							MOD_SIMPLELESSON_GRADENONE => get_string('gradenone', MOD_SIMPLELESSON_LANG));
        $mform->addElement('select', 'gradeoptions', get_string('gradeoptions', MOD_SIMPLELESSON_LANG), $gradeoptions);


		
        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
	
	
    /**
     * This adds completion rules
	 * The values here are just dummies. They don't work in this project until you implement some sort of grading
	 * See lib.php simplelesson_get_completion_state()
     */
	 function add_completion_rules() {
		$mform =& $this->_form;  
		$config = get_config(MOD_SIMPLELESSON_FRANKY);
    
		//timer options
        //Add a place to set a mimumum time after which the activity is recorded complete
       $mform->addElement('static', 'mingradedetails', '',get_string('mingradedetails', MOD_SIMPLELESSON_LANG));
       $options= array(0=>get_string('none'),20=>'20%',30=>'30%',40=>'40%',50=>'50%',60=>'60%',70=>'70%',80=>'80%',90=>'90%',100=>'40%');
       $mform->addElement('select', 'mingrade', get_string('mingrade', MOD_SIMPLELESSON_LANG), $options);	   
	   
		return array('mingrade');
	}
	
	function completion_rule_enabled($data) {
		return ($data['mingrade']>0);
	}
	
	function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $context = $this->context;
            error_log('mod_form: ' . $context->id);
            $editoroptions = simplelesson_get_editor_options($context);
            $default_values = (object) $default_values;
            $default_values = 
                    file_prepare_standard_editor($default_values, 'firstpage',
                    $editoroptions, $context, 'mod_simplelesson', 
                    'firstpage',
                    $default_values->id);
            $default_values = (array) $default_values;
        }
    }
  
    
}
