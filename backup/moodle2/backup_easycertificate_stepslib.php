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
 * Defines the backup structure for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @category   backup
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the backup structure step for the easycertificate activity module.
 *
 * @package    mod_easycertificate
 * @category   backup
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_easycertificate_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the data structure exported by the backup process.
     *
     * @return backup_nested_element Main backup structure.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $easycertificate = new backup_nested_element('easycertificate', ['id'], [
            'name',
            'intro',
            'introformat',
            'templateid',
            'timecreated',
            'timemodified',
        ]);

        $template = new backup_nested_element('template', ['id'], [
            'name',
            'description',
            'pagesjson',
            'elementsjson',
            'format',
            'orientation',
            'enabled',
            'timecreated',
            'timemodified',
            'usermodified',
        ]);

        $issues = new backup_nested_element('issues');
        $issue = new backup_nested_element('issue', ['id'], [
            'userid',
            'code',
            'timecreated',
        ]);

        $easycertificate->add_child($template);
        $easycertificate->add_child($issues);
        $issues->add_child($issue);

        $easycertificate->set_source_table('easycertificate', ['id' => backup::VAR_ACTIVITYID]);

        $template->set_source_sql(
            'SELECT t.*
               FROM {easycertificate_templates} t
               JOIN {easycertificate} e ON e.templateid = t.id
              WHERE e.id = ?',
            [backup::VAR_ACTIVITYID]
        );

        if ($userinfo) {
            $issue->set_source_table('easycertificate_issues', [
                'easycertificateid' => backup::VAR_PARENTID,
            ]);
        }

        $issue->annotate_ids('user', 'userid');
        $easycertificate->annotate_files('mod_easycertificate', 'intro', null);

        return $this->prepare_activity_structure($easycertificate);
    }
}
