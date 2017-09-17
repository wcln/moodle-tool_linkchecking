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
 * This file provides all functions required to check if links are working
 *
 * @package    tool_linkchecking
 * @copyright  2017 Colin Bernard {@link http://bclearningnetwork.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

set_time_limit(0); // prevent fatal max exec time errors

$time; // global used to keep track of script running time


/**
 *
 * Finds optimal batch size by testing a bunch and recording time
 *
 * @param    $start The starting batch size
 * @param    $end The ending batch size
 * @param    $increment The increment to increase the batch sizes by each iteration
 * @return   The optimal batch size
 *
 */
function test_batch_sizes($start = 10, $end = 1000, $increment = 10) {

    $optimal = $start;
    $best_time = 999999;

    for ($i = $start; $i <= $end; $i += $increment) {
        // start timer
        $time = microtime(true);

        $results = [];
        create_batches($all_data);
        analyze_results($results);

        // end timer
        $elapsed = microtime(true) - $time;

        if ($elapsed < $best_time) {
            $optimal = $i;
        }
    }

    return $optimal;
}

/**
 *
 * Queries the database and finds pages with HTTP or HTTPS links (as specified)
 *
 * @param    $urltype Either HTTP or HTTPS. The URL type to search in DB for.
 * @param    $course_fullname If specified, will search for links only in this course.
 * @return   An associative array result set
 *
 */
function query_database($urltype = "http", $course_fullname = null) {
    global $DB; 


    $query =   "SELECT {book_chapters}.id, fullname, shortname, {course_modules}.section, {course_sections}.name, {book}.name AS book, content, title, {course_sections}.summary, {course_sections}.id AS summary_id
                FROM {book_chapters}, {book}, {course}, {course_modules}, {course_sections}, {course_categories}
                WHERE {book}.id = {book_chapters}.bookid
                AND {course}.id = {book}.course
                AND {course_modules}.course = {course}.id
                AND {course_modules}.instance = {book}.id
                AND {course_modules}.module = 18
                AND {course_sections}.id = {course_modules}.section
                AND {course}.category = {course_categories}.id
                AND ({course_categories}.parent = 28 OR {course_categories}.parent = 28)";

    if ($urltype == "http") {
        $query .= " AND content LIKE '%http:%' OR {course_sections}.summary LIKE '%http:%'";
    } else { // https
        $query .= " AND content LIKE '%https:%' OR {course_sections}.summary LIKE '%https:%'";
    }

    if (!is_null($course_fullname)) {
        $query .= "AND fullname = '" . $course_fullname . "'";
    }

    $result = $DB->get_records_sql($query);
    return $result;
}

/**
 *
 * Counts size of array returned by extract_links function.
 *
 * @param    $urltype Either HTTP or HTTPS. The URL type to search in DB for.
 * @param    $course_fullname If specified, will search for links only in this course.
 * @return   The number of links in the database.
 *
 */
function count_links_in_database($urltype = "http", $course_fullname = null, $lower_bound, $upper_bound) {
    return count(extract_links(query_database($urltype, $course_fullname), $lower_bound, $upper_bound, $urltype));
}


/**
 *
 * Extracts links from the database query
 *
 * @param    $result The result set from the database query
 * @param    $lower_bound Lower bound search limit (inclusive)
 * @param    $upper_bound Upper bound search limit (exclusive)
 * @param    $result The result set from the database query
 * @return   An associative array of URLS and course data
 *
 */
function extract_links($result, $lower_bound = 0, $upper_bound = 1000, $urltype = "http") {
    $all_data = [];
    $total_http_link_counter = 0;

    foreach ($result as $row) {
        $content = $row->content; // extract content from result set
        $summary = $row->summary; // extract summary from result set

        if ($urltype === "http") {
            preg_match_all('#\bhttp://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $content, $links); // extract HTTP URLs from content
            preg_match_all('#\bhttp://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $summary, $summary_links); // extract HTTP URLs from summary
        } else {
            preg_match_all('#\bhttps://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $content, $links); // extract HTTPS URLs from content
            preg_match_all('#\bhttps://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $summary, $summary_links); // extract HTTPS URLs from summary
        }
        
        $content_count = count($links);
        $links = array_merge($links, $summary_links);
    
        foreach ($links[0] as $link) {

            if ($total_http_link_counter < $lower_bound) {
                $total_http_link_counter++;
                continue;
            }

            // is this link from the summary or the content?
            $is_summary = false;
            if ($total_http_link_counter > $content_count) {
                $is_summary = true;
            }

            array_push($all_data, [
                'url' => $link,
                'httpcode' => null,
                'err' => null,
                'fullname' => $row->fullname,
                'shortname' => $row->shortname,
                'name' => $row->name,
                'title' => $row->title,
                'id' => $row->id,
                'book' => $row->book,
                'is_summary' => $is_summary,
                'summary_id' => $row->summary_id
            ]);

            $total_http_link_counter++; // count total number of http links

            // limit
            if ($total_http_link_counter >= $upper_bound) {
                break 2; // break out of both loops
            }
        }
    }

    return $all_data;
}

/**
 * 
 * Stops the script running timer
 *
 * @return   $elapsed The amount ofmtime in seconds that the script ran for
 *
 */
function end_timer() {
    global $time;

    $elapsed = microtime(true) - $time;
    return $elapsed;
}

/**
 * 
 * Starts the script running timer
 *
 */
function start_timer() {
    global $time;

    $time = microtime(true);
}


/**
 *
 * Receives array of links, splits into sections and sends to perform_multi_request function
 *
 * @param    $data An array of data including URLs to be tested
 * @param    $batch_size The batch size of URLs to send and add to a multi request at the same time
 *
 */
function create_batches($data, $batch_size = 380) {
    
    $results = [];

    for ($i = 0; $i < count($data); $i += $batch_size) {
        $to_test = array_slice($data, $i, $batch_size);
        $results = array_merge($results, perform_multi_request($to_test));
    }

    return $results;
}

/**
 *
 * Retrieves HTTP Codes and CURL errors from URLs
 *
 * @param    $data A batch of URLs
 * @return   The given array with filled out HTTP codes and errors
 *
 */
function perform_multi_request($data) {

    $agent = "Mozilla/4.0 (B*U*S)";

    // array of curl handles
    $curl_handles = array();

    // multi handle
    $mh = curl_multi_init();

    // loop through each $data and create curl handles
    // then add them to the multi handle
    foreach ($data as $index => $d) {

        // init
        $ch = curl_init();

        // extract URL from array
        $url = $d['url'];

        // curl settings
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);

        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100000); // if getting HTTP CODE 0 and timeout then increase this
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 500);

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // up to 5 redirects to prevent infinite loops


        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); //fix for certificate issue
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); //fix for certificate issue

        $curl_handles[$index] = $ch;

        // add new handle to multi
        curl_multi_add_handle($mh, $curl_handles[$index]);

    }

    // execute the handles
    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running > 0);

    // get contents and remove handles
    foreach ($curl_handles as $id => $ch) {

        // fill out missing values in array
        $data[$id]['httpcode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data[$id]['err'] = curl_error($ch);

        curl_multi_remove_handle($mh, $ch);
    }

    // all done
    curl_multi_close($mh);

    // return the filled out array
    return $data;
}

/**
 *
 * Analyzes results of checking web pages. Looks at HTTP codes. Writes final results to files
 *
 * @param    $results The results of perform_multi_request
 * @param    $is_https Boolean that determines if the results to analyze are from HTTPS links or HTTP links
 * @param    $check_conversion Should check conversion from HTTP to HTTPS as well?
 * @return   $https_to_check HTTP links that are good and can be checked for conversion to HTTPS
 * @return   $good_links Returned if function is checking HTTPS links. Returns the good HTTPS links so they can be updated in DB
 *
 */
function analyze_results($results, $is_https = false, $check_conversion = false) {

    $good_links = [];
    $broken_links = [];
    $https_to_check = [];

    foreach ($results as $r) {

        if (($r['httpcode'] >= 200 && $r['httpcode'] < 307) || checkIfEmbededYoutubeVideo($r) || checkIfWirisLink($r) || checkIfEmptyReply($r)){ // good

            array_push($good_links, $r);

            if ($check_conversion && !$is_https) {
                array_push($https_to_check, $r);
            }


        } else { // BAD

            array_push($broken_links, $r);

        }
    }

    $broken_links_string = "";

    foreach ($broken_links as $bl) {
        $broken_links_string .= $bl['fullname'] . "," . $bl['shortname'] . "," . $bl['name'] . "," . $bl['book'] . "," . $bl['title'] . "," . $bl['url'] . "," . $bl['httpcode'] . "," . $bl['err'] . "\n";
    }

    $good_links_string = "";

    foreach ($good_links as $gl) {
        $good_links_string .= $gl['fullname'] . "," . $gl['shortname'] . "," . $gl['name'] . "," . $gl['book'] . "," . $gl['title'] . "," . $gl['url'] . "," . $gl['httpcode'] . "," . $gl['err'] . "\n";
    }

    if (!$is_https) {
        write_to_file($broken_links_string, "results/broken_http_links.txt");
        write_to_file($good_links_string, "results/good_http_links.txt");

        if ($check_conversion) {
            return $https_to_check;
        }
    } else {
        write_to_file($broken_links_string, "results/broken_https_links.txt");
        write_to_file($good_links_string, "results/good_https_links.txt");

        return $good_links; // return the good HTTPS links so they can be updated in DB
    }



}


/**
 *
 * Converts HTTP links to HTTPS by adding an 's'
 *
 * @param    $dataset The data set (array) containing 'url' key
 * @return   The modified dataset with HTTPS links instead of HTTP links
 *
 */
function convert_http_to_https($dataset) {
    foreach ($dataset as &$row) {
        $row['url'] = str_replace("http", "https", $row['url']);
    } 
    return $dataset;
}

/**
 *
 * Replaces HTTP links with HTTPS links in the database
 *
 * @param    $data The array of links and associated course data
 *
 */
function update_http_to_https_in_database($data) {
    global $DB;

    foreach ($data as $d) {

        if ($d['is_summary']) { // link is in summary?

            // get old summary from database
            $params = [];
            $params[] = $d['summary_id'];
            $summary = $DB->get_record_sql('SELECT summary FROM {course_sections} WHERE id=?', $params);


            // replace http link with https link in old summary
            $link = $d['url'];
            $new_summary = str_replace(str_replace("https:", "http:", $link), $link, $summary->summary);

            // update summary in database
            $params = [];
            $params[] = $new_summary;
            $params[] = $d['summary_id'];
            $DB->execute('UPDATE {course_sections} SET summary=? WHERE id=?', $params);


        } else { // link is in content

            // get old content from database
            $params = [];
            $params[] = $d['id'];
            $content = $DB->get_record_sql('SELECT content FROM {book_chapters} WHERE id=?', $params);


            // replace http link with https link in old content
            $link = $d['url'];
            $new_content = str_replace(str_replace("https:", "http:", $link), $link, $content->content);

            // update content in database
            $params = [];
            $params[] = $new_content;
            $params[] = $d['id'];
            $DB->execute('UPDATE {book_chapters} SET content=? WHERE id=?', $params);
        }


    }
}


/**
 *
 * Filter for embedded youtube links which work fine but script detects as not working for some reason
 *
 * @param    $row A row of the results array being analyzed in analyze_results function
 * @return   True if the URL is a link to an embedded youtube video, otherwise false
 *
 */
function checkIfEmbededYoutubeVideo($row) {
    if ($row['httpcode'] == 0 
        && strpos($row['url'], 'youtub') !== false 
        && strpos($row['url'], 'embed') !== false 
        && $row['err'] == "Unknown SSL protocol error in connection to www.youtube.com:443 ") {

        return true;
    }

    return false;
}

/**
 *
 * Filter for wiris math equation links because symbols in URL mess up script
 *
 * @param    $row A row of the results array being analyzed in analyze_results function
 * @return   True if the URL is a link to a wiris/MathML equation
 *
 */
function checkIfWirisLink($row) {
    if (strpos($row['url'], 'MathML') !== false && strpos($row['url'], 'w3.org') !== false) {
        return true;
    }

    return false;
}

/**
 *
 * Filter to check if CURL error of "Empty Reply From somewebsite.com" (all of these links actually seem to be OK and working)
 *
 * @param    $row A row of the results array being analyzed in analyze_results function
 * @return   True if the CURL error contains the string 'Empty reply from'
 *
 */
function checkIfEmptyReply($row) {
    if (strpos($row['err'], 'Empty reply from') !== false) {
        return true;
    }

    return false;
}

/**
 *
 * Writes a string to a file
 *
 * @param    $string The string to be written
 * @param    $filename The file to be written to
 *
 */
function write_to_file($string, $filename) {
    $file = fopen($filename, 'w');
    fwrite($file, $string);
    fclose($file);
}


