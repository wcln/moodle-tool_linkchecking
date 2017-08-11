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
 * Link Checking Admin Tool
 *
 * @package    tool_linkchecking
 * @copyright  2017 Colin Bernard {@link http://bclearningnetwork.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Standard GPL and phpdocs
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('links_form.php');
require_once(__DIR__.'/locallib.php');
require_once(__DIR__.'/check_multi.php');

// Calls require_login and performs permission checks for admin pages
admin_externalpage_setup('linkchecking'); 

// Set up the page.
$title = get_string('pluginname', 'tool_linkchecking');
$pagetitle = $title;
$url = new moodle_url("/admin/tool/linkchecking/index.php");
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/admin/tool/linkchecking/ajax/onselect.js'));
 
 
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading', 'tool_linkchecking'));





$mform = new links_form();

if ($fromform = $mform->get_data()) {
	// process validated data
	// $mform->get_data() returns data posted in form


	// perform required actions

	start_timer();
	$course = tool_linkchecking_get_course_string($fromform->course); 
	if ($course == "ALL COURSES") {
		$course = null;	
	}

	$number_links = -1;
	$count = false;
	if ($fromform->searchtype == "count") {
		$count = true;
		$number_links = count_links_in_database($fromform->linktype, $course, $fromform->lowerbound, $fromform->upperbound);
	} else if ($fromform->searchtype == "check") {
		$database_result_set = query_database($fromform->linktype, $course);
		$extracted_data = extract_links($database_result_set, $fromform->lowerbound, $fromform->upperbound, $fromform->linktype);
		$test_results = create_batches($extracted_data);

		if (isset($fromform->checkconversion)) {
			$good_links = analyze_results($test_results, false, true);
			$https_links = convert_http_to_https($good_links);
			$test_results = create_batches($https_links);
			$data_to_update_in_db = analyze_results($test_results, true, false);

			if (isset($fromform->update)) {
				update_http_to_https_in_database($data_to_update_in_db); // update the links in the database as well	
			}
			
		} else {
			if ($fromform->linktype == "http") {
				$is_https = false;
			} else {
				$is_https = true;
			}
			analyze_results($test_results, $is_https, false);
		}

	}
	$elapsed = round(end_timer(), 1);

	// display results
	$output2 = $PAGE->get_renderer('tool_linkchecking');

	$renderable = new \tool_linkchecking\output\results_page($count, $fromform->linktype, $course, isset($fromform->checkconversion), isset($fromform->update), $elapsed, $number_links);
	echo $output2->render($renderable);

} else {
	// this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
	// or on the first display of the form

	$info = format_text(get_string('linkcheckingintro', 'tool_linkchecking'), FORMAT_MARKDOWN);
	echo $OUTPUT->box($info);


	// display the form
	$mform->display();
}

 
echo $OUTPUT->footer();

?>