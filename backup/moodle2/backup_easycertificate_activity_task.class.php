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
 * Defines the backup task for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @category   backup
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/easycertificate/backup/moodle2/backup_easycertificate_stepslib.php');

/**
 * Defines the backup task for the easycertificate activity module.
 *
 * @package    mod_easycertificate
 * @category   backup
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_easycertificate_activity_task extends backup_activity_task {

    /**
     * Defines module-specific backup settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Defines backup steps for the activity module.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_easycertificate_activity_structure_step(
            'easycertificate_structure',
            'easycertificate.xml'
        ));
    }

    /**
     * Encodes Easy Certificate activity links in backed up content.
     *
     * @param string $content Original content.
     * @return string Content with encoded links.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot . '/mod/easycertificate', '#');
        $pattern = '#(' . $base . '/view\.php\?id=)([0-9]+)#';
        $replacement = '$@EASYCERTIFICATEVIEWBYID*$2@$';

        return preg_replace($pattern, $replacement, $content);
    }
}
