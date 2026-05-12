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
 * Visual template editor page for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/editorlib.php');

require_login();

$context = context_system::instance();
require_capability('mod/easycertificate:managetemplates', $context);

$id = optional_param('id', 0, PARAM_INT);
$template = $id ? $DB->get_record('easycertificate_templates', ['id' => $id], '*', MUST_EXIST) : null;

if (!$template) {
    $defaultpage = [
        (object) [
            'id' => 'p1',
            'name' => 'Página 1',
            'background' => '',
            'width' => 1123,
            'height' => 794,
        ],
    ];

    $template = (object) [
        'id' => 0,
        'name' => '',
        'description' => '',
        'pagesjson' => json_encode($defaultpage),
        'elementsjson' => '[]',
        'format' => 'A4',
        'orientation' => 'L',
        'enabled' => 1,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $data = (object) [
        'name' => required_param('name', PARAM_TEXT),
        'description' => optional_param('description', '', PARAM_TEXT),
        'pagesjson' => required_param('pagesjson', PARAM_RAW),
        'elementsjson' => required_param('elementsjson', PARAM_RAW),
        'format' => optional_param('format', 'A4', PARAM_ALPHANUMEXT),
        'orientation' => optional_param('orientation', 'L', PARAM_ALPHA),
        'enabled' => optional_param('enabled', 0, PARAM_INT),
        'timemodified' => time(),
        'usermodified' => $USER->id,
    ];

    json_decode($data->pagesjson);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('invalidjson');
    }

    json_decode($data->elementsjson);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('invalidjson');
    }

    if ($id) {
        $data->id = $id;
        $DB->update_record('easycertificate_templates', $data);
    } else {
        $data->timecreated = time();
        $id = $DB->insert_record('easycertificate_templates', $data);
    }

    redirect(new moodle_url('/mod/easycertificate/edit_template.php', ['id' => $id]), 'Modelo salvo.', 1);
}

$PAGE->set_context($context);
$PAGE->set_url('/mod/easycertificate/edit_template.php', ['id' => $id]);
$PAGE->set_title(get_string('edittemplate', 'easycertificate'));
$PAGE->set_heading(get_string('edittemplate', 'easycertificate'));
$PAGE->requires->css(new moodle_url('/mod/easycertificate/styles.css'));
$PAGE->requires->js_call_amd('mod_easycertificate/editor', 'init', [[
    'pages' => json_decode($template->pagesjson ?: '[]'),
    'elements' => json_decode($template->elementsjson ?: '[]'),
    'userfields' => \mod_easycertificate\local\certificate::get_user_fields(),
    'customfields' => \mod_easycertificate\local\certificate::get_custom_profile_fields(),
    'coursefields' => \mod_easycertificate\local\certificate::get_course_fields(),
    'datefields' => \mod_easycertificate\local\certificate::get_date_fields(),
    'previewdata' => \mod_easycertificate\local\certificate::get_preview_user($USER->id),
]]);

$editoroptions = [
    'context' => $context,
    'noclean' => true,
    'maxfiles' => 0,
    'maxbytes' => 0,
    'trusttext' => true,
];
$preferrededitor = editors_get_preferred_editor(FORMAT_HTML);
$preferrededitor->use_editor('ec-text', array_merge($editoroptions, [
    'autosave' => false,
    'placeholder' => 'Ex.: {firstname} {lastname} | Emitido em {issuedate}',
]));

echo $OUTPUT->header();
echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'easycertificate-template-form',
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'pagesjson',
    'id' => 'ec-pagesjson',
    'value' => s($template->pagesjson),
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'elementsjson',
    'id' => 'ec-elementsjson',
    'value' => s($template->elementsjson),
]);

echo html_writer::start_div('ec-topform card mb-3');
echo html_writer::start_div('card-body row align-items-end');

echo html_writer::div(
    '<label>Nome do modelo</label>' .
    '<input class="form-control" type="text" name="name" required value="' . s($template->name) . '">',
    'col-md-4'
);

echo html_writer::div(
    '<label>Descrição</label>' .
    '<input class="form-control" type="text" name="description" value="' . s($template->description) . '">',
    'col-md-4'
);

echo html_writer::div(
    '<label>Papel</label>' .
    '<select class="form-control" name="format"><option value="A4">A4</option></select>',
    'col-md-1'
);

$checked = $template->orientation === 'P' ? 'selected' : '';
echo html_writer::div(
    '<label>Orientação</label>' .
    '<select class="form-control" name="orientation">' .
    '<option value="L">Paisagem</option>' .
    '<option value="P" ' . $checked . '>Retrato</option>' .
    '</select>',
    'col-md-2'
);

echo html_writer::div(
    '<label class="d-block">Ativo</label>' .
    '<input type="checkbox" name="enabled" value="1" ' . ($template->enabled ? 'checked' : '') . '>',
    'col-md-1'
);

echo html_writer::end_div();
echo html_writer::end_div();

echo '<div class="ec-editor">';
echo '<div class="ec-toolbar card">';
echo '<div class="card-body">';
echo '<div class="ec-toolbar-actions">';
echo '<button type="submit" class="btn btn-primary btn-sm ec-save-btn">';
echo '<i class="fa fa-save" aria-hidden="true"></i> Salvar';
echo '</button>';
echo '<a class="btn btn-secondary btn-sm ec-back-btn" href="templates.php">';
echo '<i class="fa fa-arrow-left" aria-hidden="true"></i> Voltar';
echo '</a>';
echo '<button type="button" class="btn btn-light btn-sm" id="ec-add-page">';
echo '<i class="fa fa-file-o" aria-hidden="true"></i> Página';
echo '</button>';
echo '</div>';
echo '<hr>';
echo '<button type="button" class="btn btn-outline-primary btn-sm" data-ec-add="text">';
echo '<i class="fa fa-font" aria-hidden="true"></i> Texto';
echo '</button>';
echo '<button type="button" class="btn btn-outline-primary btn-sm" data-ec-add="userfield">';
echo '<i class="fa fa-user" aria-hidden="true"></i> Campo de usuário';
echo '</button>';
echo '<button type="button" class="btn btn-outline-primary btn-sm" data-ec-add="concat">';
echo '<i class="fa fa-puzzle-piece" aria-hidden="true"></i> Concatenação';
echo '</button>';
echo '<button type="button" class="btn btn-outline-primary btn-sm" data-ec-add="date">';
echo '<i class="fa fa-calendar" aria-hidden="true"></i> Data';
echo '</button>';
echo '<button type="button" class="btn btn-outline-primary btn-sm" data-ec-add="signature">';
echo '<i class="fas fa-signature" aria-hidden="true"></i> Assinatura digital';
echo '</button>';
echo '<button type="button" class="btn btn-outline-primary btn-sm" data-ec-add="image">';
echo '<i class="fa fa-image" aria-hidden="true"></i> Imagem';
echo '</button>';
echo '<hr>';
echo '<div class="btn-group">';
echo '<button type="button" class="btn btn-secondary btn-sm" id="ec-zoom-out">';
echo '<i class="fa fa-search-minus" aria-hidden="true"></i> Zoom -';
echo '</button>';
echo '<button type="button" class="btn btn-secondary btn-sm" id="ec-zoom-label">100%</button>';
echo '<button type="button" class="btn btn-secondary btn-sm" id="ec-zoom-in">';
echo '<i class="fa fa-search-plus" aria-hidden="true"></i> Zoom +';
echo '</button>';
echo '</div>';
echo '<hr>';
echo '<div id="ec-pages-tabs" class="ec-pages-tabs"></div>';
echo '</div>';
echo '</div>';
echo '<div class="ec-workarea">';
echo '<div id="ec-stage-wrap" class="ec-stage-wrap">';
echo '<div id="ec-guide-x" class="ec-guide ec-guide-x"></div>';
echo '<div id="ec-guide-y" class="ec-guide ec-guide-y"></div>';
echo '<div id="ec-stage" class="ec-stage"></div>';
echo '</div>';
echo '</div>';
echo '<div class="ec-items-panel card">';
echo '<div class="card-body">';
echo '<div class="ec-items-title">Itens adicionados</div>';
echo '<div id="ec-items-list" class="ec-items-list"></div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo html_writer::end_tag('form');

echo '<div class="modal fade" id="ec-field-modal" tabindex="-1" aria-hidden="true">';
echo '<div class="modal-dialog modal-lg"><div class="modal-content">';
echo '<div class="modal-header">';
echo '<h5 class="modal-title">Campo</h5>';
echo '<button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Fechar">';
echo '<span aria-hidden="true">&times;</span>';
echo '</button>';
echo '</div>';
echo '<div class="modal-body">';
echo '<input type="hidden" id="ec-modal-type">';
echo '<div class="form-group ec-userfield-row">';
echo '<label>Campos de Usuário</label>';
echo '<select id="ec-userfield-select" class="form-control"></select>';
echo '</div>';
echo '<div class="form-group ec-customfield-row">';
echo '<label>Campo customizado</label>';
echo '<input id="ec-customfield" class="form-control">';
echo '</div>';
echo '<div class="form-group ec-datefield-row">';
echo '<label>Tipo de data</label>';
echo '<select id="ec-datefield-select" class="form-control"></select>';
echo '</div>';
echo '<div class="form-group ec-concatfield-row">';
echo '<label>Inserir campo na concatenação</label>';
echo '<select id="ec-concatfield-select" class="form-control"></select>';
echo '</div>';
echo '<div class="alert alert-info ec-help-box">';
echo '<button type="button" class="btn btn-link btn-sm p-0 ec-info-toggle">';
echo '<i class="fa fa-info-circle" aria-hidden="true"></i> Como usar';
echo '</button>';
echo '<div class="ec-info-content mt-2">';
echo 'Use campos entre chaves. Exemplos: ';
echo '<code>{firstname} {lastname}</code>, <code>{issuedate}</code>, <code>{course}</code>.<br>';
echo 'Para concatenação, digite texto livre junto com os campos: ';
echo '<code>Certificamos que {firstname} concluiu {course} em {issuedate}</code>.';
echo '</div>';
echo '</div>';
echo '<div class="form-group">';
echo '<label>Texto / concatenação</label>';
echo '<textarea id="ec-text" name="ec_text_buffer" class="form-control ec-html-field" rows="8" ';
echo 'placeholder="Ex.: {firstname} {lastname} | {course} | {issuedate}"></textarea>';
echo '</div>';
echo '<div class="row ec-inline-style-row">';
echo '<div class="col-md-6">';
echo '<label>Tamanho do texto selecionado</label>';
echo '<div class="input-group ec-style-control">';
echo '<div class="input-group-prepend">';
echo '<span class="input-group-text" title="Tamanho">';
echo '<i class="fa fa-text-height" aria-hidden="true"></i>';
echo '</span>';
echo '</div>';
echo '<input id="ec-size" type="number" class="form-control" value="24" min="8">';
echo '</div>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<label>Cor do texto selecionado</label>';
echo '<div class="input-group ec-style-control ec-color-control">';
echo '<div class="input-group-prepend">';
echo '<span class="input-group-text" title="Cor">';
echo '<i class="fa fa-paint-brush" aria-hidden="true"></i>';
echo '</span>';
echo '</div>';
echo '<input id="ec-color" type="color" class="form-control" value="#111111">';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '<div class="modal-footer">';
echo '<button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancelar</button>';
echo '<button type="button" id="ec-field-save" class="btn btn-primary">Adicionar</button>';
echo '</div>';
echo '</div></div>';
echo '</div>';

echo '<div class="modal fade" id="ec-image-modal" tabindex="-1" aria-hidden="true">';
echo '<div class="modal-dialog"><div class="modal-content">';
echo '<div class="modal-header">';
echo '<h5 class="modal-title">Imagem</h5>';
echo '<button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Fechar">';
echo '<span aria-hidden="true">&times;</span>';
echo '</button>';
echo '</div>';
echo '<div class="modal-body">';
echo '<div class="form-group">';
echo '<label>Arquivo</label>';
echo '<input id="ec-image-file" type="file" class="form-control" accept="image/png,image/jpeg">';
echo '</div>';
echo '<label><input id="ec-image-bg" type="checkbox"> Usar como imagem de fundo</label>';
echo '</div>';
echo '<div class="modal-footer">';
echo '<button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancelar</button>';
echo '<button type="button" id="ec-image-save" class="btn btn-primary">Adicionar</button>';
echo '</div>';
echo '</div></div>';
echo '</div>';

echo '<div class="modal fade" id="ec-signature-modal" tabindex="-1" aria-hidden="true">';
echo '<div class="modal-dialog"><div class="modal-content">';
echo '<div class="modal-header">';
echo '<h5 class="modal-title">Assinatura digital</h5>';
echo '<button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Fechar">';
echo '<span aria-hidden="true">&times;</span>';
echo '</button>';
echo '</div>';
echo '<div class="modal-body">';
echo '<div class="alert alert-info ec-help-box">';
echo 'Informe um certificado PFX/P12 e a senha para assinar o PDF. ';
echo 'A máscara é obrigatória e será exibida visualmente no certificado.';
echo '</div>';
echo '<div class="form-group">';
echo '<label>Certificado digital (.pfx ou .p12)</label>';
echo '<input id="ec-signature-cert" type="file" class="form-control" ';
echo 'accept=".pfx,.p12,application/x-pkcs12">';
echo '</div>';
echo '<div class="form-group">';
echo '<label>Senha do certificado</label>';
echo '<input id="ec-signature-password" type="password" class="form-control" autocomplete="new-password">';
echo '</div>';
echo '<div class="form-group">';
echo '<label>Máscara visual da assinatura</label>';
echo '<input id="ec-signature-mask" type="file" class="form-control" ';
echo 'accept="image/png,image/jpeg" data-required="1">';
echo '</div>';
echo '<div id="ec-signature-mask-error" class="alert alert-danger ec-signature-error" role="alert">';
echo 'Informe a máscara visual da assinatura.';
echo '</div>';
echo '</div>';
echo '<div class="modal-footer">';
echo '<button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancelar</button>';
echo '<button type="button" id="ec-signature-save" class="btn btn-primary">Adicionar</button>';
echo '</div>';
echo '</div></div>';
echo '</div>';

echo '<div class="modal fade" id="ec-rename-modal" tabindex="-1" aria-hidden="true">';
echo '<div class="modal-dialog"><div class="modal-content">';
echo '<div class="modal-header">';
echo '<h5 class="modal-title">Renomear item</h5>';
echo '<button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Fechar">';
echo '<span aria-hidden="true">&times;</span>';
echo '</button>';
echo '</div>';
echo '<div class="modal-body">';
echo '<input type="hidden" id="ec-rename-id">';
echo '<div class="form-group">';
echo '<label>Nome do item</label>';
echo '<input id="ec-rename-name" type="text" class="form-control" autocomplete="off">';
echo '</div>';
echo '</div>';
echo '<div class="modal-footer">';
echo '<button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancelar</button>';
echo '<button type="button" id="ec-rename-save" class="btn btn-primary">Salvar</button>';
echo '</div>';
echo '</div></div>';
echo '</div>';

echo '<div class="modal fade" id="ec-preview-modal" tabindex="-1" aria-hidden="true">';
echo '<div class="modal-dialog modal-xl"><div class="modal-content">';
echo '<div class="modal-header">';
echo '<h5 class="modal-title">Preview do modelo</h5>';
echo '<button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Fechar">';
echo '<span aria-hidden="true">&times;</span>';
echo '</button>';
echo '</div>';
echo '<div class="modal-body"><div id="ec-preview-body" class="ec-preview-body"></div></div>';
echo '</div></div>';
echo '</div>';

echo $OUTPUT->footer();
