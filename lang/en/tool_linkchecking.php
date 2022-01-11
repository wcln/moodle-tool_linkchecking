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
 * Strings for component 'tool_generator', language 'en'.
 *
 * @package    tool_linkchecking
 * @copyright  2017 Colin Perepelken {@link https://wcln.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Check Links';
$string['heading'] = 'Check Links';
$string['linkcheckingintro'] = "This script will check all HTTP or HTTPS links found in course page content. Backing up the database tables 'book_chapters', 'url', and 'course_sections' first is HIGHLY recommended. Execution time of the script may easily reach hours for large numbers of links.";
$string['submit'] = 'Run';
$string['HTTP'] = 'HTTP';
$string['HTTPS'] = 'HTTPS';
$string['linktype'] = 'Type of URL to search for';
$string['titleconversion'] = 'Replacement Options (HTTP to HTTPS)';
$string['checkconversion'] = 'Check conversion of HTTP links to HTTPS links';
$string['update'] = 'Update HTTP links to HTTPS in database';
$string['enable'] = 'Enable';
$string['checkconversion_help'] = 'Checks if working HTTP links could be converted to HTTPS. Does NOT actually convert them. Will generate two additional lists for HTTPS good/broken links.';
$string['update_help'] = 'Only the table mdl_book_chapters is affected. Links are only updated if the script detects that they will work when converted to HTTPS as well. Check conversion must be enabled.';
$string['count'] = 'Just count links';
$string['check'] = 'Check if links are working or broken';
$string['searchtype'] = 'Type of search';
$string['allcourses'] = 'ALL COURSES';
$string['searchlimit'] = 'Search Limit';
$string['lowerbound'] = 'Lower Bound';
$string['upperbound'] = 'Upper Bound';
$string['searchlimitstatic'] = 'Defines the range of links to search. Lower bound is inclusive while upper bound is exclusive.';
$string['lowerbound_error'] = 'Lower bound can not be greater than upper bound!';

