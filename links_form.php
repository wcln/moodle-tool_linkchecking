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
 * Link Checking form
 *
 * @package    tool_linkchecking
 * @copyright  2017 Colin Bernard {@link http://bclearningnetwork.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once(__DIR__.'/locallib.php');


/**
 * Definition of links form.
 *
 * @copyright  
 * @license    
 */
class links_form extends moodleform {

    /**
     * Define standards form.
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;

        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'linktype', '', get_string('HTTP', 'tool_linkchecking'), "http");
        $radioarray[] = $mform->createElement('radio', 'linktype', '', get_string('HTTPS', 'tool_linkchecking'), "https");
        $mform->addGroup($radioarray, 'radioar', get_string('linktype', 'tool_linkchecking'), array(' '), false);
        $mform->setDefault('linktype', "http");

        $radioarray2 = array();
        $radioarray2[] = $mform->createElement('radio', 'searchtype', '', get_string('count', 'tool_linkchecking'), "count");
        $radioarray2[] = $mform->createElement('radio', 'searchtype', '', get_string('check', 'tool_linkchecking'), "check");
        $mform->addGroup($radioarray2, 'radioar2', get_string('searchtype', 'tool_linkchecking'), array(' '), false);
        $mform->setDefault('searchtype', "check");

        $courses = tool_linkchecking_get_courses(); // get from lib
        $courses = array_reverse($courses, true);
        $courses[''] = get_string('allcourses', 'tool_linkchecking');
        $courses = array_reverse($courses, true);

        $mform->addElement('select', 'course', get_string('courseselect', 'tool_quizpasschange'), $courses);
        $mform->setType('course', PARAM_RAW);

        $mform->addElement('header', 'conversionheader', get_string('titleconversion', 'tool_linkchecking'));

        $mform->addElement('checkbox', 'checkconversion', get_string('checkconversion', 'tool_linkchecking'), get_string('enable', 'tool_linkchecking'));
        $mform->setType('checkconversion', PARAM_BOOL);
        $mform->addHelpButton('checkconversion', 'checkconversion', 'tool_linkchecking');
        $mform->disabledIf('checkconversion', 'searchtype', 'eq', 'count');
        $mform->disabledIf('checkconversion', 'linktype', 'eq', 'https');

        $mform->addElement('checkbox', 'update', get_string('update', 'tool_linkchecking'), get_string('enable', 'tool_linkchecking'));
        $mform->setType('update', PARAM_BOOL);
        $mform->addHelpButton('update', 'update', 'tool_linkchecking');
        $mform->disabledIf('update', 'searchtype', 'eq', 'count');
        $mform->disabledIf('update', 'checkconversion');
        $mform->disabledIf('update', 'linktype', 'eq', 'https');

        $mform->addElement('header', 'searchlimitheader', get_string('searchlimit', 'tool_linkchecking'));
        $mform->addElement('static', 'description', get_string('searchlimitstatic', 'tool_linkchecking'));

        $mform->addElement('text', 'lowerbound', get_string('lowerbound', 'tool_linkchecking'));
        $mform->setType('lowerbound', PARAM_INT);
        $mform->setDefault('lowerbound', 0);

        $mform->addElement('text', 'upperbound', get_string('upperbound', 'tool_linkchecking'));
        $mform->setType('upperbound', PARAM_INT);
        $mform->setDefault('upperbound', 1000);

        $this->add_action_buttons(false, get_string('submit', 'tool_linkchecking'));
    }

    /**
     * Custom form validation
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {

        // Validate that lower limit is < upper limit
        $errors = parent::validation($data, $files);

        $lowerbound = $data['lowerbound'];
        $upperbound = $data['upperbound'];

        if ($lowerbound > $upperbound) {
            $errors['lowerbound'] = get_string('lowerbound_error', 'tool_linkchecking');
        }

        return $errors;
    }
}
