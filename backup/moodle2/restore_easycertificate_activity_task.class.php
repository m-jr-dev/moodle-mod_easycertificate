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
 * Defines the restore task for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @category   backup
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/easycertificate/backup/moodle2/restore_easycertificate_stepslib.php');

/**
 * Defines the restore task for the easycertificate activity module.
 *
 * @package    mod_easycertificate
 * @category   backup
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_easycertificate_activity_task extends restore_activity_task {

    /**
     * Defines module-specific restore settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines restore steps for the activity module.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_easycertificate_activity_structure_step(
            'easycertificate_structure',
            'easycertificate.xml'
        ));
    }

    /**
     * Defines contents to decode during restore.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * Defines link decoding rules for the activity module.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule(
            'EASYCERTIFICATEVIEWBYID',
            '/mod/easycertificate/view.php?id=$1',
            'course_module'
        );

        return $rules;
    }

    /**
     * Defines restore log rules for the activity module.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Defines restore log rules for the course.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
