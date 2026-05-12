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
 * Main library functions for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the list of features supported by the module.
 *
 * @param string $feature Feature constant.
 * @return bool|null
 */
function easycertificate_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
            return true;

        default:
            return null;
    }
}

/**
 * Adds a new Easy Certificate activity instance.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_easycertificate_mod_form|null $mform Activity form.
 * @return int New instance id.
 */
function easycertificate_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();

    return $DB->insert_record('easycertificate', $data);
}

/**
 * Updates an Easy Certificate activity instance.
 *
 * @param stdClass $data Submitted activity data.
 * @param mod_easycertificate_mod_form|null $mform Activity form.
 * @return bool
 */
function easycertificate_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    return $DB->update_record('easycertificate', $data);
}

/**
 * Deletes an Easy Certificate activity instance.
 *
 * @param int $id Activity instance id.
 * @return bool
 */
function easycertificate_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('easycertificate', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('easycertificate_issues', ['easycertificateid' => $id]);
    $DB->delete_records('easycertificate', ['id' => $id]);

    return true;
}

/**
 * Updates course module information for display.
 *
 * @param cm_info $cm Course module information.
 */
function easycertificate_cm_info_view(cm_info $cm) {
    $cm->set_after_link('');
}
