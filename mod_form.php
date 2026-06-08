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
 * Activity settings form for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Activity settings form for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_easycertificate_mod_form extends moodleform_mod {

    /**
     * Defines the activity settings form.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $templates = [0 => get_string('notemplate', 'easycertificate')];
        $dbman = $DB->get_manager();

        if ($dbman->table_exists('easycertificate_templates')) {
            $records = $DB->get_records_menu(
                'easycertificate_templates',
                ['enabled' => 1],
                'name ASC',
                'id,name'
            );

            if ($records) {
                $templates = [0 => get_string('selecttemplate', 'easycertificate')] + $records;
            }
        }

        $mform->addElement('select', 'templateid', get_string('template', 'easycertificate'), $templates);
        $mform->setType('templateid', PARAM_INT);
        $mform->addHelpButton('templateid', 'template', 'easycertificate');

        $templateurl = new moodle_url('/mod/easycertificate/templates.php');
        $templatelink = html_writer::link(
            $templateurl,
            get_string('managetemplates', 'easycertificate'),
            [
                'class' => 'btn btn-secondary ec-template-manage-link',
                'target' => '_blank',
                'rel' => 'noopener',
            ]
        );

        $mform->addElement(
            'html',
            html_writer::start_div('form-group row fitem ec-template-manage-row') .
                html_writer::div('', 'col-md-3 col-form-label') .
                html_writer::div(
                    $templatelink,
                    'col-md-9 d-flex flex-wrap align-items-start felement'
                ) .
                html_writer::end_div()
        );

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
