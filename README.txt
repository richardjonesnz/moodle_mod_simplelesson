Modified
========
This is modified from Justin Hunt's Pairwork activity module for Moodle.  This is hopefully
a good basis for a simple lesson module that has basic features as follows:

1.  Supports adding questions from the question bank
2.  Consists of multimedia pages with simple hyperlinked navigation
3.  Reports for students and teacher's will hopefully be a part of it

For more complex needs (timing, grading, access restrictions, use the Lesson activity module)

Note: I've removed some of Justin's niftier features such as the module.js and tab navigation.

Richard Jones
richardnz@outlook.com

Template Activity Module for Moodle
===================================
This is a more modern, and at least for me, more useful template than the others available.
It contains admin and instance settings stubs, a renderer.php and a module.js . It also contains activity completion on grade, grade book logic, backup and restore and adgoc/scheduled tasks

Justin Hunt
poodllsupport@gmail.com

NB
By default the newtemplate supports grading, but since there is nothing to grade ... yet ... when you do update a gradable item, you will need to call: [modulename]_update_grades($moduleinstance, $userid_of_student);
