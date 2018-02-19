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
 * Edit a page
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('edit_page_form.php');

//fetch URL parameters
$simplelessonid = required_param('simplelessonid', PARAM_INT); 
$action = optional_param('action','list',PARAM_TEXT);
$pageid = optional_param('pageid',0,PARAM_INT);

//Set course related variables
$PAGE->set_course($COURSE);
$course = $DB->get_record('course', array('id' => $COURSE->id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

//set up the page
$PAGE->set_url('/mod/simplelesson/edit_page.php', array('simplelesson' => $simplelessonid));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');

//=========================================
//Form processing begins here
//=========================================

//get the page editing form
$mform = new simplelesson_edit_page_form();

//if the cancel button was pressed, we are out of here
if ($mform->is_cancelled()) {
    $return_url = new moodle_url('/mod/simplelesson/view.php', array('n' => $simplelessonid));
    redirect($return->url, get_string('cancelled'), 2);
    exit;
}

//if we have data, then our job here is to save it and return
if ($data = $mform->get_data()) {
    $DB->update_record('simplelesson_pages', $data);
    redirect($PAGE->url,get_string('updated','core', $data->{$pagetitle}), 2);
}

//=========================================
//Page output begins here
//=========================================
echo $OUTPUT->header();

//if the action param is "edit" then we show the edit form
if($action =="edit") {
    //create some data for our form and set it to the form
    $data = new stdClass();
    $data = $DB->get_record('simplelesson_pages', array('id'=>$pageid));

    // If there is no page data, create a dummy record
    if(!$data || empty($data)) {
        $data = new stdClass();
        $pageid = \mod_simplelesson\local\utilities::make_dummy_page_record($data, $simplelessonid); 
    } 
    
    $mform->set_data($data);
    
    // Header for the page
    echo $OUTPUT->heading(get_string('page_editing', MOD_SIMPLELESSON_LANG), 2);
    
    $mform->display();
}


$head=false;
$table = new html_table();
    //foreach($data as $onedata){
        // var_dump($data);

        
        $onearray = get_object_vars($data);
        //build the head row
        if(!$head){
            $head=true;
            $table->head= array_keys($onearray);
            $table->head[] = get_string('edit');
            $table->head[] = get_string('delete');
        }
        //build all the other rows
        $rowdata=array_values($onearray);
        $editlink = html_writer::link(
        new moodle_url('/mod/simplelesson/edit_page.php', 
                array('id'=>$onearray['id'],'action' => 'edit')),get_string('edit'));
        $rowdata[] = $editlink;

        /* will need a delete action param eventually
        $deletelink = html_writer::link(
                new moodle_url('/mod/simplelesson/edit_page.php', 
                array('id'=>$onearray['id'],'action' => 'delete')),get_string('delete'));
        $rowdata[] = $deletelink;
        */
        $table->data[]=$rowdata;
        
echo html_writer::table($table);
echo $OUTPUT->footer();

return;