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
 * Library of interface functions and constants for module simplelesson
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the simplelesson specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_simplelesson
 * @copyright 2015 Justin Hunt, modified 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('MOD_SIMPLELESSON_FRANKY','mod_simplelesson');
define('MOD_SIMPLELESSON_LANG','mod_simplelesson');
define('MOD_SIMPLELESSON_TABLE','simplelesson');
define('MOD_SIMPLELESSON_USERTABLE','simplelesson_attempts');
define('MOD_SIMPLELESSON_MODNAME','simplelesson');
define('MOD_SIMPLELESSON_URL','/mod/simplelesson');
define('MOD_SIMPLELESSON_CLASS','mod_simplelesson');

define('MOD_SIMPLELESSON_GRADEHIGHEST', 0);
define('MOD_SIMPLELESSON_GRADELOWEST', 1);
define('MOD_SIMPLELESSON_GRADELATEST', 2);
define('MOD_SIMPLELESSON_GRADEAVERAGE', 3);
define('MOD_SIMPLELESSON_GRADENONE', 4);


////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function simplelesson_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;
		case FEATURE_COMPLETION_HAS_RULES: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default:                        return null;
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the simplelesson.
 *
 * @param $mform form passed by reference
 */
function simplelesson_reset_course_form_definition(&$mform) {
    $mform->addElement('header', MOD_SIMPLELESSON_MODNAME . 'header', get_string('modulenameplural', MOD_SIMPLELESSON_LANG));
    $mform->addElement('advcheckbox', 'reset_' . MOD_SIMPLELESSON_MODNAME , get_string('deletealluserdata',MOD_SIMPLELESSON_LANG));
}

/**
 * Course reset form defaults.
 * @param object $course
 * @return array
 */
function simplelesson_reset_course_form_defaults($course) {
    return array('reset_' . MOD_SIMPLELESSON_MODNAME =>1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function simplelesson_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course as courseid
              FROM {" . MOD_SIMPLELESSON_TABLE . "} l, {course_modules} cm, {modules} m
             WHERE m.name='" . MOD_SIMPLELESSON_MODNAME . "' AND m.id=cm.module AND cm.instance=l.id AND l.course=:course";
    $params = array ("course" => $courseid);
    if ($moduleinstances = $DB->get_records_sql($sql,$params)) {
        foreach ($moduleinstances as $moduleinstance) {
            simplelesson_grade_item_update($moduleinstance, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * simplelesson attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function simplelesson_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', MOD_SIMPLELESSON_LANG);
    $status = array();

    if (!empty($data->{'reset_' . MOD_SIMPLELESSON_MODNAME})) {
        $sql = "SELECT l.id
                         FROM {".MOD_SIMPLELESSON_TABLE."} l
                        WHERE l.course=:course";

        $params = array ("course" => $data->courseid);
        $DB->delete_records_select(MOD_SIMPLELESSON_USERTABLE, MOD_SIMPLELESSON_MODNAME . "id IN ($sql)", $params);

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            simplelesson_reset_gradebook($data->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deletealluserdata', MOD_SIMPLELESSON_LANG), 'error'=>false);
    }

    /// updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates(MOD_SIMPLELESSON_MODNAME, array('available', 'deadline'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}




/**
 * Create grade item for activity instance
 *
 * @category grade
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_NONE
 * @param object $moduleinstance object with extra cmidnumber
 * @param array|object $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function simplelesson_grade_item_update($moduleinstance, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (array_key_exists('cmidnumber', $moduleinstance)) { //it may not be always present
        $params = array('itemname'=>$moduleinstance->name, 'idnumber'=>$moduleinstance->cmidnumber);
    } else {
        $params = array('itemname'=>$moduleinstance->name);
    }

    if ($moduleinstance->grade > 0) {
        $params['gradetype']  = GRADE_TYPE_VALUE;
        $params['grademax']   = $moduleinstance->grade;
        $params['grademin']   = 0;
    } else if ($moduleinstance->grade < 0) {
        $params['gradetype']  = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$moduleinstance->grade;

        // Make sure current grade fetched correctly from $grades
        $currentgrade = null;
        if (!empty($grades)) {
            if (is_array($grades)) {
                $currentgrade = reset($grades);
            } else {
                $currentgrade = $grades;
            }
        }

        // When converting a score to a scale, use scale's grade maximum to calculate it.
        if (!empty($currentgrade) && $currentgrade->rawgrade !== null) {
            $grade = grade_get_grades($moduleinstance->course, 'mod', MOD_SIMPLELESSON_MODNAME, $moduleinstance->id, $currentgrade->userid);
            $params['grademax']   = reset($grade->items)->grademax;
        }
    } else {
        $params['gradetype']  = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    } else if (!empty($grades)) {
        // Need to calculate raw grade (Note: $grades has many forms)
        if (is_object($grades)) {
            $grades = array($grades->userid => $grades);
        } else if (array_key_exists('userid', $grades)) {
            $grades = array($grades['userid'] => $grades);
        }
        foreach ($grades as $key => $grade) {
            if (!is_array($grade)) {
                $grades[$key] = $grade = (array) $grade;
            }
            //check raw grade isnt null otherwise we insert a grade of 0
            if ($grade['rawgrade'] !== null) {
                $grades[$key]['rawgrade'] = ($grade['rawgrade'] * $params['grademax'] / 100);
            } else {
                //setting rawgrade to null just in case user is deleting a grade
                $grades[$key]['rawgrade'] = null;
            }
        }
    }


    return grade_update('mod/' . MOD_SIMPLELESSON_MODNAME, $moduleinstance->course, 'mod', MOD_SIMPLELESSON_MODNAME, $moduleinstance->id, 0, $grades, $params);
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param object $moduleinstance
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function simplelesson_update_grades($moduleinstance, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($moduleinstance->grade == 0) {
        simplelesson_grade_item_update($moduleinstance);

    } else if ($grades = simplelesson_get_user_grades($moduleinstance, $userid)) {
        simplelesson_grade_item_update($moduleinstance, $grades);

    } else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        simplelesson_grade_item_update($moduleinstance, $grade);

    } else {
        simplelesson_grade_item_update($moduleinstance);
    }
	
	//echo "updategrades" . $userid;
}

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $moduleinstance
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function simplelesson_get_user_grades($moduleinstance, $userid=0) {
    global $CFG, $DB;

    $params = array("moduleid" => $moduleinstance->id);

    if (!empty($userid)) {
        $params["userid"] = $userid;
        $user = "AND u.id = :userid";
    }
    else {
        $user="";

    }

	$idfield = 'a.' . MOD_SIMPLELESSON_MODNAME . 'id';
    if ($moduleinstance->maxattempts==1 || $moduleinstance->gradeoptions == MOD_SIMPLELESSON_GRADELATEST) {

        $sql = "SELECT u.id, u.id AS userid, a.sessionscore AS rawgrade
                  FROM {user} u,  {". MOD_SIMPLELESSON_USERTABLE ."} a
                 WHERE u.id = a.userid AND $idfield = :moduleid
                       AND a.status = 1
                       $user";
	
	}else{
		switch($moduleinstance->gradeoptions){
			case MOD_SIMPLELESSON_GRADEHIGHEST:
				$sql = "SELECT u.id, u.id AS userid, MAX( a.sessionscore  ) AS rawgrade
                      FROM {user} u, {". MOD_SIMPLELESSON_USERTABLE ."} a
                     WHERE u.id = a.userid AND $idfield = :moduleid
                           $user
                  GROUP BY u.id";
				  break;
			case MOD_SIMPLELESSON_GRADELOWEST:
				$sql = "SELECT u.id, u.id AS userid, MIN(  a.sessionscore  ) AS rawgrade
                      FROM {user} u, {". MOD_SIMPLELESSON_USERTABLE ."} a
                     WHERE u.id = a.userid AND $idfield = :moduleid
                           $user
                  GROUP BY u.id";
				  break;
			case MOD_SIMPLELESSON_GRADEAVERAGE:
            $sql = "SELECT u.id, u.id AS userid, AVG( a.sessionscore  ) AS rawgrade
                      FROM {user} u, {". MOD_SIMPLELESSON_USERTABLE ."} a
                     WHERE u.id = a.userid AND $idfield = :moduleid
                           $user
                  GROUP BY u.id";
				  break;

        }

    } 

    return $DB->get_records_sql($sql, $params);
}


function simplelesson_get_completion_state($course,$cm,$userid,$type) {
	return simplelesson_is_complete($course,$cm,$userid,$type);
}


//this is called internally only 
function simplelesson_is_complete($course,$cm,$userid,$type) {
	 global $CFG,$DB;
	 
	  global $CFG,$DB;

	// Get module object
    if(!($moduleinstance=$DB->get_record(MOD_SIMPLELESSON_TABLE,array('id'=>$cm->instance)))) {
        throw new Exception("Can't find module with cmid: {$cm->instance}");
    }
	$idfield = 'a.' . MOD_SIMPLELESSON_MODNAME . 'id';
	$params = array('moduleid'=>$moduleinstance->id, 'userid'=>$userid);
	$sql = "SELECT  MAX( sessionscore  ) AS grade
                      FROM {". MOD_SIMPLELESSON_USERTABLE ."}
                     WHERE userid = :userid AND " . MOD_SIMPLELESSON_MODNAME . "id = :moduleid";
	$result = $DB->get_field_sql($sql, $params);
	if($result===false){return false;}
	 
	//check completion reqs against satisfied conditions
	switch ($type){
		case COMPLETION_AND:
			$success = $result >= $moduleinstance->mingrade;
			break;
		case COMPLETION_OR:
			$success = $result >= $moduleinstance->mingrade;
	}
	//return our success flag
	return $success;
}


/**
 * A task called from scheduled or adhoc
 *
 * @param progress_trace trace object
 *
 */
function simplelesson_dotask(progress_trace $trace) {
    $trace->output('executing dotask');
}

/**
 * Saves a new instance of the simplelesson into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $simplelesson An object from the form in mod_form.php
 * @param mod_simplelesson_mod_form $mform
 * @return int The id of the newly inserted simplelesson record
 */
function simplelesson_add_instance(stdClass $simplelesson, mod_simplelesson_mod_form $mform = null) {
    global $DB;

    $simplelesson->timecreated = time();

    // add new instance with dummy data for editor content
    // This is part of the uploaded files saving process
    $simplelesson->firstpagetext ='';
    $simplelesson->firstpageformat =FORMAT_HTML;
    $simplelessonid = $DB->insert_record(MOD_SIMPLELESSON_TABLE, $simplelesson);  
    $simplelesson->id = $simplelessonid;

    //call file_postupdate_standard editor to save files,
    // and prepare editor content for saving in database
    $cmid = $simplelesson->coursemodule;
    $context = context_module::instance($cmid);
    $editoroptions = simplelesson_get_editor_options($context);
    
    $simplelesson = file_postupdate_standard_editor($simplelesson, 'firstpage',
            $editoroptions, $context, 
            'mod_simplelesson', 'firstpage', 
            $simplelessonid);

    //update database with proper editor content
    $DB->update_record(MOD_SIMPLELESSON_TABLE, $simplelesson);

    return $simplelessonid;  
}

/**
 * Updates an instance of the simplelesson in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $simplelesson An object from the form in mod_form.php
 * @param mod_simplelesson_mod_form $mform
 * @return boolean Success/Fail
 */
function simplelesson_update_instance(stdClass $simplelesson, mod_simplelesson_mod_form $mform = null) {
    global $DB;

    $simplelesson->timemodified = time();
    $simplelesson->id = $simplelesson->instance;

    //save files and process editor content
    $cmid = $simplelesson->coursemodule;
    $context = context_module::instance($cmid);
    $editoroptions = simplelesson_get_editor_options($context);
    
    $simplelesson = file_postupdate_standard_editor($simplelesson, 'firstpage',
            $editoroptions, $context, 
            'mod_simplelesson', 'firstpage', 
            $simplelesson->id);

    $DB->update_record(MOD_SIMPLELESSON_TABLE, $simplelesson);
    return $simplelesson->id;
}

/**
 * Removes an instance of the simplelesson from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function simplelesson_delete_instance($id) {
    global $DB;

    if (! $simplelesson = $DB->get_record(MOD_SIMPLELESSON_TABLE, array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #

    $DB->delete_records(MOD_SIMPLELESSON_TABLE, array('id' => $simplelesson->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function simplelesson_user_outline($course, $user, $mod, $simplelesson) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $simplelesson the module instance record
 * @return void, is supposed to echp directly
 */
function simplelesson_user_complete($course, $user, $mod, $simplelesson) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in simplelesson activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function simplelesson_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link simplelesson_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function simplelesson_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see simplelesson_get_recent_mod_activity()}

 * @return void
 */
function simplelesson_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function simplelesson_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function simplelesson_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of simplelesson?
 *
 * This function returns if a scale is being used by one simplelesson
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $simplelessonid ID of an instance of this module
 * @return bool true if the scale is used by the given simplelesson instance
 */
function simplelesson_scale_used($simplelessonid, $scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists(MOD_SIMPLELESSON_TABLE, array('id' => $simplelessonid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of simplelesson.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any simplelesson instance
 */
function simplelesson_scale_used_anywhere($scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists(MOD_SIMPLELESSON_TABLE, array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}



////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

// Return editor options
function simplelesson_get_editor_options($context) {
    global $CFG;
    return array('subdirs'=>true, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1,
            'changeformat'=>1, 'context'=>$context, 
            'noclean'=>true, 'trusttext'=>false);
}

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function simplelesson_get_file_areas($course, $cm, $context) {
    return array('pagecontents' => 'for page files editor content');
}

/**
 * File browsing support for simplelesson file areas
 *
 * @package mod_simplelesson
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function simplelesson_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the simplelesson file areas
 *
 * @package mod_simplelesson
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the simplelesson's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function simplelesson_pluginfile($course, $cm, $context, $filearea, 
        array $args, $forcedownload, array $options=array()) {

    global $DB, $CFG;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }
    
    require_login($course, true, $cm);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_simplelesson/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    // Finally send the file.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding simplelesson nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the simplelesson module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function simplelesson_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {

	$view_url = new moodle_url('/mod/simplelesson/view.php',array('id'=>$cm->id));
	$view_node = $navref->add(get_string('view'), $view_url);
	$config = get_config(MOD_SIMPLELESSON_FRANKY);
	if($config->enablereports){
		$report_url = new moodle_url('/mod/simplelesson/reports.php',array('id'=>$cm->id));
		$report_node = $navref->add(get_string('reports'),$report_url);
	}

}

/**
 * Extends the settings navigation with the simplelesson settings
 *
 * This function is called when the context for the page is a simplelesson module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $simplelessonnode {@link navigation_node}
 */
function simplelesson_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $simplelessonnode=null) {
	global $PAGE;
	$config = get_config(MOD_SIMPLELESSON_FRANKY);
	if($config->enablereset){
		$reset_url = new moodle_url('/mod/simplelesson/reset.php',array('id'=>$PAGE->cm->id));
		$reset_node = $simplelessonnode->add(get_string('reset'), $reset_url);
	}
	
	$rename_url = new moodle_url('/mod/simplelesson/namechanger.php',array('courseid'=>$PAGE->cm->course));
	$rename_node = $simplelessonnode->add(get_string('rename'), $rename_url);
}
