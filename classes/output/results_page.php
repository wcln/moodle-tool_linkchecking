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
 * @copyright  2017 Colin Perepelken {@link https://wcln.ca}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_linkchecking\output;                                                                                                         
 
use renderable;                                                                                                                     
use renderer_base;                                                                                                                  
use templatable;                                                                                                                    
use stdClass;                                                                                                                       
 
class results_page implements renderable, templatable {                                                                               

    var $count = null;
    var $linktype = null;
    var $course = null;
    var $conversion = null;
    var $update = null;
    var $elapsed = null;           
    var $number = null;                                                                     
 
    public function __construct($count, $linktype, $course, $conversion, $update, $elapsed, $number) {                                                                                        
        $this->count = $count;
        $this->linktype = $linktype;
        $this->course = $course;
        $this->conversion = $conversion;
        $this->update = $update;
        $this->elapsed = $elapsed;   
        $this->number = $number;                                                                            

    }
 
    /**                                                                                                                             
     * Export this data so it can be used as the context for a mustache template.                                                   
     *                                                                                                                              
     * @return stdClass                                                                                                             
     */                                                                                                                             
    public function export_for_template(renderer_base $output) {                                                                    
        $data = new stdClass();    

        if (is_null($this->course)){
            $data->course = 'all courses';
        } else {
            $data->course = $this->course; 
        }                                                              

        if ($this->count == true) {
            $data->count = $this->count;
        }

        $data->linktype = strtoupper($this->linktype);
        
        if ($this->linktype == "http") {
            $data->http = true;
        } else {
            $data->https = true;
        }

        if ($this->conversion == true) {
            $data->conversion = $this->conversion;
        }

        if ($this->update == true) {
            $data->update = $this->update;    
        }

        
        $data->elapsed = $this->elapsed;

        $data->goodhttpcount = tool_linkchecking_get_lines('results/good_http_links.txt');
        $data->brokenhttpcount = tool_linkchecking_get_lines('results/broken_http_links.txt');
        $data->goodhttpscount = tool_linkchecking_get_lines('results/good_https_links.txt');
        $data->brokenhttpscount = tool_linkchecking_get_lines('results/broken_https_links.txt');

        $data->number = $this->number;

        return $data;                                                                                                               
    }
}