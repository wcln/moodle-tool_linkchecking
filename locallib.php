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
 * Link Checking
 *
 * @package    tool_linkchecking
 * @copyright  2017 Colin Bernard {@link http://bclearningnetwork.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');



/**
 * Returns list of courses from database
 * @return array
 */
function tool_linkchecking_get_courses() {
    global $DB;

    $courses = array();

    $sql = 'SELECT DISTINCT mdl_course.fullname 
            FROM mdl_course';

    $rows = $DB->get_records_sql($sql);


    foreach ($rows as $row) {
        array_push($courses, $row->fullname);
    }

    return $courses;
}

/**
 * Get course string from select list ID
 * @param $id
 * @return course string
 */
function tool_linkchecking_get_course_string($id) {
    $courses = tool_linkchecking_get_courses();
    $courses = array_reverse($courses, true);
    $courses[''] = get_string('allcourses', 'tool_linkchecking');
    $courses = array_reverse($courses, true);

    return $courses[$id];
}

/**
 * Counts number of lines in a file
 * @param $file
 * @return lines integer
 */
function tool_linkchecking_get_lines($file) {
    $f = fopen($file, 'rb');
    $lines = 0;

    while (!feof($f)) {
        $lines += substr_count(fread($f, 8192), "\n");
    }

    fclose($f);

    return $lines;
}

