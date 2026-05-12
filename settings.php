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
 * Administrative settings for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $manageurl = new moodle_url('/mod/easycertificate/templates.php');
    $link = html_writer::link(
        $manageurl,
        get_string('opentemplatemanager', 'easycertificate'),
        ['class' => 'btn btn-primary']
    );

    $settings->add(new admin_setting_heading(
        'mod_easycertificate_templatemanager',
        get_string('managetemplates', 'easycertificate'),
        $link
    ));
}

if ($hassiteconfig || has_capability('mod/easycertificate:managetemplates', context_system::instance())) {
    $ADMIN->add('modsettings', new admin_externalpage(
        'mod_easycertificate_templates',
        get_string('managetemplates', 'easycertificate'),
        new moodle_url('/mod/easycertificate/templates.php'),
        'mod/easycertificate:managetemplates'
    ));
}
