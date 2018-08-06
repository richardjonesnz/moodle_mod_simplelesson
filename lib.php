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
 *
 * All the simplelesson specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_pairwork
 */
/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function simplelesson_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_USES_QUESTIONS:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the simplelesson into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $simplelesson Submitted data from the form in mod_form.php
 * @param mod_simplelesson_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted simplelesson record
 */
function simplelesson_add_instance(stdClass $simplelesson, mod_simplelesson_mod_form $mform = null) {
    global $DB;

    $simplelesson->timecreated = time();

    // You may have to add extra stuff in here.
    $simplelesson->id = $DB->insert_record('simplelesson', $simplelesson);

    simplelesson_grade_item_update($simplelesson);

    return $simplelesson->id;
}

/**
 * Updates an instance of the simplelesson in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $simplelesson An object from the form in mod_form.php
 * @param mod_simplelesson_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function simplelesson_update_instance(stdClass $simplelesson, mod_simplelesson_mod_form $mform = null) {
    global $DB;

    $simplelesson->timemodified = time();
    $simplelesson->id = $simplelesson->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('simplelesson', $simplelesson);

    simplelesson_grade_item_update($simplelesson);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every simplelesson event in the site is checked, else
 * only simplelesson events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 */
function simplelesson_refresh_events($courseid = 0) {
    global $DB;

    if ($courseid == 0) {
        if (!$simplelessons = $DB->get_records('simplelesson')) {
            return true;
        }
    } else {
        if (!$simplelessons = $DB->get_records('simplelesson', array('course' => $courseid))) {
            return true;
        }
    }
    /*
    foreach ($simplelessons as $simplelesson) {
        // Create a function such as the one below to deal with updating calendar events.
        // simplelesson_update_events($simplelesson);.
    }
    */
    return true;
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

    if (!$simplelesson = $DB->get_record('simplelesson',
            array('id' => $id))) {
        return false;
    }
    if (!$cm = get_coursemodule_from_instance('simplelesson',
            $simplelesson->id)) {
        return false;
    }

    // Delete any dependent records.
    $DB->delete_records('simplelesson_questions',
            array('simplelessonid' => $simplelesson->id));
    $DB->delete_records('simplelesson_answers',
            array('simplelessonid' => $simplelesson->id));
    $DB->delete_records('simplelesson_attempts',
            array('simplelessonid' => $simplelesson->id));
    $DB->delete_records('simplelesson_pages',
            array('simplelessonid' => $simplelesson->id));

    // Delete the module record.
    $DB->delete_records('simplelesson',
            array('id' => $simplelesson->id));

    // Delete files.
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'pagecontents');

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $simplelesson The simplelesson instance record
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
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $simplelesson the module instance record
 */
function simplelesson_user_complete($course, $user, $mod, $simplelesson) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in simplelesson activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function simplelesson_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link simplelesson_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'id' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $id course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function simplelesson_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $id, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link simplelesson_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'id' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function simplelesson_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function simplelesson_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function simplelesson_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of simplelesson?
 *
 * This function returns if a scale is being used by one simplelesson
 * if it has support for grading and scales.
 *
 * @param int $simplelessonid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given simplelesson instance
 */
function simplelesson_scale_used($simplelessonid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('simplelesson', array('id' => $simplelessonid, 'grade' => -$scaleid))) {
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
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any simplelesson instance
 */
function simplelesson_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('simplelesson', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given simplelesson instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $simplelesson instance object with extra idnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function simplelesson_grade_item_update(stdClass $simplelesson, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($simplelesson->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($simplelesson->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $simplelesson->grade;
        $item['grademin']  = 0;
    } else if ($simplelesson->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$simplelesson->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/simplelesson', $simplelesson->course, 'mod', 'simplelesson',
            $simplelesson->id, 0, null, $item);
}

/**
 * Delete grade item for given simplelesson instance
 *
 * @param stdClass $simplelesson instance object
 * @return grade_item
 */
function simplelesson_grade_item_delete($simplelesson) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/simplelesson', $simplelesson->course, 'mod', 'simplelesson',
            $simplelesson->id, 0, null, array('deleted' => 1));
}

/**
 * Update simplelesson grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $simplelesson instance object with extra idnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function simplelesson_update_grades(stdClass $simplelesson, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/simplelesson', $simplelesson->course, 'mod', 'simplelesson', $simplelesson->id, 0, $grades);
}

/* File API */

// Return editor options.
function simplelesson_get_editor_options($context) {
    global $CFG;
    return array('subdirs' => true, 'maxbytes' => $CFG->maxbytes,
            'maxfiles' => -1,
            'changeformat' => 1, 'context' => $context,
            'noclean' => true, 'trusttext' => false);
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
function simplelesson_pluginfile($course, $cm, $context, $filearea, array $args,
        $forcedownload, array $options=array()) {
    global $DB, $CFG;

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
/**
 *
 * @package    mod_simplelesson
 * @see package mod_qpractice
 * @copyright  2013 Jayesh Anandani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Modified for use in mod_simplelesson by Richard Jones http://richardnz/net
 * This is used for images within pages that are in questions.
 * Apparently it will be magically called by simplelesson_pluginfile above.
 *
 * @package  mod_simplelesson
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string $component the name of the component we are serving files for.
 * @param string $filearea the name of the file area.
 * @param int $qubaid the attempt usage id.
 * @param int $slot the id of a question in this quiz attempt.
 * @param array $args the remaining bits of the file path.
 * @param bool $forcedownload whether the user must be forced to download the file.
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */

function mod_simplelesson_question_pluginfile($course, $context, $component,
         $filearea, $qubaid, $slot, $args,
         $forcedownload, array $options = array()) {

    require_login($course);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding simplelesson nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the simplelesson module instance
 * @param stdClass $course current course record
 * @param stdClass $module current simplelesson instance record
 * @param cm_info $cm course module information
 */
function simplelesson_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the simplelesson settings
 *
 * This function is called when the context for the page is a simplelesson module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $simplelessonnode simplelesson administration node
 */
function simplelesson_extend_settings_navigation(settings_navigation
        $settingsnav, navigation_node $simplelessonnode=null) {
    // Provide a link to the attempts management page.
    global $PAGE;
    $attemptsurl = new moodle_url(
            '/mod/simplelesson/manage_attempts.php',
            array('courseid' => $PAGE->course->id));
    $simplelessonnode->add(get_string('manage_attempts',
            'mod_simplelesson'), $attemptsurl);
}
