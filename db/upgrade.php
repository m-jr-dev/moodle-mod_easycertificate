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
 * Database upgrade steps for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrades the Easy Certificate database schema.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_easycertificate_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051200) {
        $table = new xmldb_table('easycertificate_templates');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('pagesjson', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('elementsjson', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('format', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'A4');
            $table->add_field('orientation', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, 'L');
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('easycertificate_issues');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('easycertificateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('code', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('easycertificateid', XMLDB_KEY_FOREIGN, ['easycertificateid'], 'easycertificate', ['id']);
            $table->add_index('certuser', XMLDB_INDEX_UNIQUE, ['easycertificateid', 'userid']);
            $table->add_index('code', XMLDB_INDEX_UNIQUE, ['code']);
            $dbman->create_table($table);
        }

        $table = new xmldb_table('easycertificate');
        $field = new xmldb_field('templateid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'introformat');

        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026051200, 'easycertificate');
    }

    return true;
}
