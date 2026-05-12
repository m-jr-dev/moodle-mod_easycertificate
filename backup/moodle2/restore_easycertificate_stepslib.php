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
 * Defines the restore structure for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @category   backup
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Processes the restore structure for the easycertificate activity module.
 *
 * @package    mod_easycertificate
 * @category   backup
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_easycertificate_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines XML paths processed during restore.
     *
     * @return restore_path_element[] Restore paths.
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('easycertificate', '/activity/easycertificate');
        $paths[] = new restore_path_element('easycertificate_template', '/activity/easycertificate/template');

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'easycertificate_issue',
                '/activity/easycertificate/issues/issue'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the main activity record.
     *
     * @param array $data Restored data.
     */
    protected function process_easycertificate($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->templateid = null;

        $newitemid = $DB->insert_record('easycertificate', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('easycertificate', $oldid, $newitemid, true);
    }

    /**
     * Restores the template used by the activity.
     *
     * @param array $data Restored data.
     */
    protected function process_easycertificate_template($data) {
        global $DB, $USER;

        $data = (object) $data;
        $oldid = $data->id;
        unset($data->id);

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = time();
        $data->usermodified = $USER->id ?? 0;

        $newtemplateid = $DB->insert_record('easycertificate_templates', $data);
        $this->set_mapping('easycertificate_template', $oldid, $newtemplateid, true);

        $easycertificateid = $this->get_new_parentid('easycertificate');
        $DB->set_field('easycertificate', 'templateid', $newtemplateid, ['id' => $easycertificateid]);
    }

    /**
     * Restores issued certificates when user data is included.
     *
     * @param array $data Restored data.
     */
    protected function process_easycertificate_issue($data) {
        global $DB;

        $data = (object) $data;
        unset($data->id);

        $userid = $this->get_mappingid('user', $data->userid);
        if (empty($userid)) {
            return;
        }

        $data->easycertificateid = $this->get_new_parentid('easycertificate');
        $data->userid = $userid;

        if (!$DB->record_exists('easycertificate_issues', [
            'easycertificateid' => $data->easycertificateid,
            'userid' => $data->userid,
        ])) {
            $DB->insert_record('easycertificate_issues', $data);
        }
    }

    /**
     * Restores files related to the activity.
     */
    protected function after_execute() {
        $this->add_related_files('mod_easycertificate', 'intro', null);
    }
}
