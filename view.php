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
 * Displays and issues certificates for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$download = optional_param('download', 0, PARAM_BOOL);
$preview = optional_param('preview', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('easycertificate', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$certificate = $DB->get_record('easycertificate', ['id' => $cm->instance], '*', MUST_EXIST);
$template = $DB->get_record('easycertificate_templates', [
    'id' => $certificate->templateid,
    'enabled' => 1,
]);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/easycertificate:view', $context);

if (!$template) {
    $PAGE->set_url('/mod/easycertificate/view.php', ['id' => $id]);
    $PAGE->set_title(format_string($certificate->name));
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();
    echo $OUTPUT->notification('Nenhum modelo ativo foi vinculado a esta atividade.', 'warning');
    echo $OUTPUT->footer();
    exit;
}

\mod_easycertificate\local\certificate::ensure_issue($certificate, $USER->id);

if ($download || $preview) {
    \mod_easycertificate\local\certificate::output_pdf(
        $certificate,
        $template,
        $USER,
        $course,
        $download ? 'D' : 'I'
    );
    exit;
}

$PAGE->set_url('/mod/easycertificate/view.php', ['id' => $id]);
$PAGE->set_title(format_string($certificate->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($certificate->name));
echo $OUTPUT->box_start('generalbox easycertificate-issued');
echo html_writer::tag('p', get_string('issuedcertificate', 'easycertificate'));

echo html_writer::link(
    new moodle_url('/mod/easycertificate/view.php', [
        'id' => $id,
        'preview' => 1,
    ]),
    '<i class="fa fa-eye" aria-hidden="true"></i> Visualizar certificado',
    [
        'class' => 'btn btn-secondary mr-2',
        'target' => '_blank',
    ]
);

echo html_writer::link(
    new moodle_url('/mod/easycertificate/view.php', [
        'id' => $id,
        'download' => 1,
    ]),
    '<i class="fa fa-download" aria-hidden="true"></i> ' .
        get_string('downloadcertificate', 'easycertificate'),
    ['class' => 'btn btn-primary']
);

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
