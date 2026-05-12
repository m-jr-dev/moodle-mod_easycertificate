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
 * Global template management page for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('mod/easycertificate:managetemplates', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/easycertificate/templates.php');
$PAGE->set_title(get_string('managetemplates', 'easycertificate'));
$PAGE->set_heading(get_string('managetemplates', 'easycertificate'));

$dbman = $DB->get_manager();

if (!$dbman->table_exists('easycertificate_templates')) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('databasenotready', 'easycertificate'), 'notifyproblem');

    echo html_writer::link(
        new moodle_url('/admin/index.php'),
        get_string('gotoadminnotifications', 'easycertificate'),
        ['class' => 'btn btn-primary']
    );

    echo $OUTPUT->footer();
    exit;
}

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

if ($action === 'delete' && $id && confirm_sesskey()) {
    $DB->delete_records('easycertificate_templates', ['id' => $id]);
    redirect(new moodle_url('/mod/easycertificate/templates.php'));
}

if ($action === 'duplicate' && $id && confirm_sesskey()) {
    $record = $DB->get_record('easycertificate_templates', ['id' => $id], '*', MUST_EXIST);
    unset($record->id);

    $record->name .= ' - cópia';
    $record->timecreated = time();
    $record->timemodified = time();
    $record->usermodified = $USER->id;

    $newid = $DB->insert_record('easycertificate_templates', $record);

    redirect(new moodle_url('/mod/easycertificate/edit_template.php', ['id' => $newid]));
}

$templates = $DB->get_records('easycertificate_templates', null, 'name ASC');

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url('/mod/easycertificate/edit_template.php'),
    get_string('createtemplate', 'easycertificate'),
    ['class' => 'btn btn-primary mb-3']
);

$table = new html_table();
$table->head = ['Modelo', 'Status', 'Atualizado em', 'Ações'];

foreach ($templates as $template) {
    $editurl = new moodle_url('/mod/easycertificate/edit_template.php', [
        'id' => $template->id,
    ]);

    $duplicateurl = new moodle_url('/mod/easycertificate/templates.php', [
        'action' => 'duplicate',
        'id' => $template->id,
        'sesskey' => sesskey(),
    ]);

    $deleteurl = new moodle_url('/mod/easycertificate/templates.php', [
        'action' => 'delete',
        'id' => $template->id,
        'sesskey' => sesskey(),
    ]);

    $editlink = html_writer::link(
        $editurl,
        'Editar',
        ['class' => 'btn btn-sm btn-secondary mr-1']
    );

    $duplicatelink = html_writer::link(
        $duplicateurl,
        'Duplicar',
        ['class' => 'btn btn-sm btn-outline-secondary mr-1']
    );

    $deletelink = html_writer::link(
        $deleteurl,
        'Excluir',
        [
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => "return confirm('Excluir este modelo?')",
        ]
    );

    $actions = $editlink . ' ' . $duplicatelink . ' ' . $deletelink;

    $table->data[] = [
        format_string($template->name),
        $template->enabled ? 'Ativo' : 'Inativo',
        userdate($template->timemodified),
        $actions,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
