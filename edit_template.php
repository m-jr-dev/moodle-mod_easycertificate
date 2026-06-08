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

/**
 * Reads a posted JSON field after the session key has been verified.
 *
 * @param string $name Field name.
 * @return array Decoded JSON array.
 */
function easycertificate_decode_json_param(string $name): array {
    if (!array_key_exists($name, $_POST) || is_array($_POST[$name])) {
        throw new moodle_exception('missingparam', 'error', '', $name);
    }

    $json = trim(str_replace("\0", '', (string) $_POST[$name]));
    $decoded = json_decode($json);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new moodle_exception('invalidjson', 'easycertificate');
    }

    return $decoded;
}

/**
 * Cleans a base64 data URI using an allowed MIME pattern.
 *
 * @param mixed $value Raw value.
 * @param string $pattern MIME validation pattern.
 * @return string
 */
function easycertificate_clean_data_uri($value, string $pattern): string {
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (!preg_match($pattern, $value)) {
        return '';
    }

    $comma = strpos($value, ',');
    if ($comma === false) {
        return '';
    }

    $base64 = substr($value, $comma + 1);
    if (strlen($base64) > 20 * 1024 * 1024 || base64_decode($base64, true) === false) {
        return '';
    }

    return $value;
}

/**
 * Cleans and re-encodes template pages.
 *
 * @param array $pages Raw decoded pages.
 * @return string JSON value ready to store.
 */
function easycertificate_clean_pages_json(array $pages): string {
    $clean = [];

    foreach ($pages as $index => $page) {
        if (!is_object($page)) {
            continue;
        }

        $id = clean_param($page->id ?? '', PARAM_ALPHANUMEXT);
        if ($id === '') {
            $id = 'p' . ($index + 1);
        }

        $name = clean_param($page->name ?? '', PARAM_TEXT);
        if ($name === '') {
            $name = get_string('defaultpagename', 'easycertificate', $index + 1);
        }

        $width = max(1, min(5000, (int) ($page->width ?? 1123)));
        $height = max(1, min(5000, (int) ($page->height ?? 794)));

        $clean[] = (object) [
            'id' => $id,
            'name' => $name,
            'background' => easycertificate_clean_data_uri(
                $page->background ?? '',
                '/^data:image\/(png|jpeg|jpg);base64,[A-Za-z0-9+\/=_-]+$/i'
            ),
            'width' => $width,
            'height' => $height,
        ];
    }

    if (!$clean) {
        $clean[] = (object) [
            'id' => 'p1',
            'name' => get_string('defaultpagename', 'easycertificate', 1),
            'background' => '',
            'width' => 1123,
            'height' => 794,
        ];
    }

    return json_encode($clean, JSON_UNESCAPED_UNICODE);
}

/**
 * Cleans and re-encodes template elements.
 *
 * @param array $elements Raw decoded elements.
 * @return string JSON value ready to store.
 */
function easycertificate_clean_elements_json(array $elements): string {
    $clean = [];
    $types = ['text', 'userfield', 'concat', 'date', 'image', 'signature', 'border'];
    $aligns = ['L', 'C', 'R', 'J'];

    foreach ($elements as $element) {
        if (!is_object($element)) {
            continue;
        }

        $type = clean_param($element->type ?? 'text', PARAM_ALPHA);
        if (!in_array($type, $types, true)) {
            $type = 'text';
        }

        $item = (object) [
            'id' => clean_param($element->id ?? uniqid($type . '-', true), PARAM_ALPHANUMEXT),
            'pageid' => clean_param($element->pageid ?? 'p1', PARAM_ALPHANUMEXT),
            'type' => $type,
            'x' => max(-5000, min(5000, (float) ($element->x ?? 0))),
            'y' => max(-5000, min(5000, (float) ($element->y ?? 0))),
            'w' => max(1, min(5000, (float) ($element->w ?? 180))),
            'h' => max(1, min(5000, (float) ($element->h ?? 40))),
        ];

        if (!empty($element->name)) {
            $item->name = clean_param($element->name, PARAM_TEXT);
        }

        if (isset($element->text)) {
            $item->text = clean_text((string) $element->text, FORMAT_HTML);
        }

        if (!empty($element->font)) {
            $item->font = clean_param($element->font, PARAM_ALPHANUMEXT);
        }

        if (isset($element->size)) {
            $item->size = max(8, min(200, (int) $element->size));
        }

        if (!empty($element->color) && preg_match('/^#[0-9a-f]{6}$/i', (string) $element->color)) {
            $item->color = (string) $element->color;
        }

        if (!empty($element->align)) {
            $align = strtoupper(substr(clean_param($element->align, PARAM_ALPHA), 0, 1));
            $item->align = in_array($align, $aligns, true) ? $align : 'L';
        }

        if (!empty($element->bold)) {
            $item->bold = 1;
        }

        if (!empty($element->italic)) {
            $item->italic = 1;
        }

        if (!empty($element->src)) {
            $item->src = easycertificate_clean_data_uri(
                $element->src,
                '/^data:image\/(png|jpeg|jpg);base64,[A-Za-z0-9+\/=_-]+$/i'
            );
        }

        if (!empty($element->mask)) {
            $item->mask = easycertificate_clean_data_uri(
                $element->mask,
                '/^data:image\/(png|jpeg|jpg);base64,[A-Za-z0-9+\/=_-]+$/i'
            );
        }

        if (!empty($element->cert)) {
            $item->cert = easycertificate_clean_data_uri(
                $element->cert,
                '/^data:[a-z0-9\/\.\-+]*;base64,[A-Za-z0-9+\/=_-]+$/i'
            );
        }

        if (isset($element->password)) {
            $item->password = substr(str_replace("\0", '', (string) $element->password), 0, 255);
        }

        if (isset($element->naturalw)) {
            $item->naturalw = max(0, min(10000, (float) $element->naturalw));
        }

        if (isset($element->naturalh)) {
            $item->naturalh = max(0, min(10000, (float) $element->naturalh));
        }

        $clean[] = $item;
    }

    return json_encode($clean, JSON_UNESCAPED_UNICODE);
}

require_login();

$context = context_system::instance();
require_capability('mod/easycertificate:managetemplates', $context);

$id = optional_param('id', 0, PARAM_INT);
$template = $id ? $DB->get_record('easycertificate_templates', ['id' => $id], '*', MUST_EXIST) : null;

if (!$template) {
    $defaultpage = [
        (object) [
            'id' => 'p1',
            'name' => get_string('defaultpagename', 'easycertificate', 1),
            'background' => '',
            'width' => 1123,
            'height' => 794,
        ],
    ];

    $template = (object) [
        'id' => 0,
        'name' => '',
        'description' => '',
        'pagesjson' => json_encode($defaultpage, JSON_UNESCAPED_UNICODE),
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
        'pagesjson' => easycertificate_clean_pages_json(easycertificate_decode_json_param('pagesjson')),
        'elementsjson' => easycertificate_clean_elements_json(easycertificate_decode_json_param('elementsjson')),
        'format' => optional_param('format', 'A4', PARAM_ALPHANUMEXT),
        'orientation' => optional_param('orientation', 'L', PARAM_ALPHA),
        'enabled' => optional_param('enabled', 0, PARAM_INT),
        'timemodified' => time(),
        'usermodified' => $USER->id,
    ];

    if (!in_array($data->orientation, ['L', 'P'], true)) {
        $data->orientation = 'L';
    }

    if ($id) {
        $data->id = $id;
        $DB->update_record('easycertificate_templates', $data);
    } else {
        $data->timecreated = time();
        $id = $DB->insert_record('easycertificate_templates', $data);
    }

    redirect(
        new moodle_url('/mod/easycertificate/edit_template.php', ['id' => $id]),
        get_string('templatesaved', 'easycertificate'),
        1
    );
}

$PAGE->set_context($context);
$PAGE->set_url('/mod/easycertificate/edit_template.php', ['id' => $id]);
$PAGE->set_title(get_string('edittemplate', 'easycertificate'));
$PAGE->set_heading(get_string('edittemplate', 'easycertificate'));
$PAGE->requires->js_call_amd('mod_easycertificate/editor', 'init', [[
    'pages' => json_decode($template->pagesjson ?: '[]'),
    'elements' => json_decode($template->elementsjson ?: '[]'),
    'userfields' => \mod_easycertificate\local\certificate::get_user_fields(),
    'customfields' => \mod_easycertificate\local\certificate::get_custom_profile_fields(),
    'coursefields' => \mod_easycertificate\local\certificate::get_course_fields(),
    'datefields' => \mod_easycertificate\local\certificate::get_date_fields(),
    'previewdata' => \mod_easycertificate\local\certificate::get_preview_user($USER->id),
    'strings' => [
        'add' => get_string('add', 'easycertificate'),
        'backgroundimage' => get_string('backgroundimage', 'easycertificate'),
        'border' => get_string('border', 'easycertificate'),
        'customfields' => get_string('customfields', 'easycertificate'),
        'dates' => get_string('dates', 'easycertificate'),
        'delete' => get_string('delete', 'easycertificate'),
        'duplicate' => get_string('duplicate', 'easycertificate'),
        'edit' => get_string('edit', 'easycertificate'),
        'noitemsadded' => get_string('noitemsadded', 'easycertificate'),
        'resize' => get_string('resize', 'easycertificate'),
        'selectfield' => get_string('selectfield', 'easycertificate'),
        'coursefields' => get_string('coursefields', 'easycertificate'),
        'image' => get_string('image', 'easycertificate'),
        'page' => get_string('page', 'easycertificate'),
        'remove' => get_string('remove', 'easycertificate'),
        'removepage' => get_string('removepage', 'easycertificate'),
        'rename' => get_string('rename', 'easycertificate'),
        'save' => get_string('save', 'easycertificate'),
        'signature' => get_string('signature', 'easycertificate'),
        'text' => get_string('text', 'easycertificate'),
        'userfields' => get_string('userfields', 'easycertificate'),
    ],
]]);

$editoroptions = [
    'context' => $context,
    'noclean' => false,
    'maxfiles' => 0,
    'maxbytes' => 0,
    'trusttext' => false,
];
$preferrededitor = editors_get_preferred_editor(FORMAT_HTML);
$preferrededitor->use_editor('ec-text', array_merge($editoroptions, [
    'autosave' => false,
    'placeholder' => get_string('textplaceholder', 'easycertificate'),
]));

$templatedata = [
    'sesskey' => sesskey(),
    'pagesjson' => $template->pagesjson,
    'elementsjson' => $template->elementsjson,
    'name' => $template->name,
    'description' => $template->description,
    'orientationportraitselected' => $template->orientation === 'P',
    'enabled' => !empty($template->enabled),
    'templatesurl' => (new moodle_url('/mod/easycertificate/templates.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_easycertificate/editor', $templatedata);
echo $OUTPUT->footer();
