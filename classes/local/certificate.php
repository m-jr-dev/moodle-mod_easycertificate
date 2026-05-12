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
 * Certificate data, placeholders and PDF output helpers for Easy Certificate.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_easycertificate\local;

/**
 * Certificate data, placeholders and PDF output helpers.
 *
 * @package    mod_easycertificate
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate {

    /**
     * Returns the available user placeholder fields.
     *
     * @return array
     */
    public static function get_user_fields(): array {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'firstname' => 'Nome',
            'lastname' => 'Sobrenome',
            'firstnamephonetic' => 'Nome fonético',
            'lastnamephonetic' => 'Sobrenome fonético',
            'middlename' => 'Nome do meio',
            'alternatename' => 'Nome alternativo',
            'email' => 'E-mail',
            'phone1' => 'Telefone 1',
            'phone2' => 'Telefone 2',
            'institution' => 'Instituição',
            'department' => 'Departamento',
            'address' => 'Endereço',
            'city' => 'Cidade',
            'country' => 'País',
            'lang' => 'Idioma',
            'timezone' => 'Fuso horário',
            'description' => 'Descrição',
            'idnumber' => 'Número de identificação',
        ];
    }

    /**
     * Returns the available course placeholder fields.
     *
     * @return array
     */
    public static function get_course_fields(): array {
        return [
            'course' => 'Nome completo do curso',
            'courseshortname' => 'Nome breve do curso',
            'courseidnumber' => 'Número de identificação do curso',
            'coursecategory' => 'Categoria do curso',
        ];
    }

    /**
     * Returns the available date placeholder fields.
     *
     * @return array
     */
    public static function get_date_fields(): array {
        return [
            'currentdate' => 'Data atual do dia',
            'issuedate' => 'Data de emissão do certificado',
            'completiondate' => 'Data de conclusão do curso',
        ];
    }

    /**
     * Returns the available custom profile placeholder fields.
     *
     * @return array
     */
    public static function get_custom_profile_fields(): array {
        global $DB;

        $records = $DB->get_records('user_info_field', null, 'name ASC', 'shortname,name');
        $fields = [];

        foreach ($records as $record) {
            $fields[$record->shortname] = $record->name . ' {' . $record->shortname . '}';
        }

        return $fields;
    }

    /**
     * Returns user data used for preview and placeholder replacement.
     *
     * @param int $userid User id.
     * @return array
     */
    public static function get_preview_user(int $userid): array {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        profile_load_custom_fields($user);

        $data = [];

        foreach (self::get_user_fields() as $field => $label) {
            $data[$field] = isset($user->{$field}) ? (string) $user->{$field} : '';
        }

        $data['fullname'] = fullname($user);

        if (!empty($user->profile) && is_array($user->profile)) {
            foreach ($user->profile as $shortname => $value) {
                $data[$shortname] = (string) $value;
            }
        }

        $data['date'] = userdate(time(), get_string('strftimedatefullshort'));
        $data['currentdate'] = $data['date'];
        $data['issuedate'] = $data['date'];
        $data['completiondate'] = '';

        return $data;
    }

    /**
     * Replaces placeholders in a certificate text.
     *
     * @param string $text Text with placeholders.
     * @param \stdClass $user User record.
     * @param \stdClass|null $course Course record.
     * @return string
     */
    public static function resolve_text(string $text, \stdClass $user, ?\stdClass $course = null): string {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        profile_load_custom_fields($user);
        $values = self::get_preview_user((int) $user->id);

        if ($course) {
            $values['course'] = format_string($course->fullname);
            $values['courseshortname'] = format_string($course->shortname);
            $values['courseidnumber'] = isset($course->idnumber) ? (string) $course->idnumber : '';
            $values['coursecategory'] = '';

            if (!empty($course->category)) {
                $category = $DB->get_record(
                    'course_categories',
                    ['id' => $course->category],
                    'name',
                    IGNORE_MISSING
                );
                $values['coursecategory'] = $category ? format_string($category->name) : '';
            }

            $completion = $DB->get_record(
                'course_completions',
                [
                    'course' => $course->id,
                    'userid' => $user->id,
                ],
                'timecompleted',
                IGNORE_MISSING
            );

            if ($completion && !empty($completion->timecompleted)) {
                $values['completiondate'] = userdate(
                    $completion->timecompleted,
                    get_string('strftimedatefullshort')
                );
            }
        }

        return preg_replace_callback(
            '/\{([a-zA-Z0-9_\-]+)\}/',
            static function ($match) use ($values) {
                $key = $match[1];

                return array_key_exists($key, $values) ? $values[$key] : '';
            },
            $text
        );
    }

    /**
     * Generates a unique certificate issue code.
     *
     * @param int $certificateid Certificate id.
     * @param int $userid User id.
     * @return string
     */
    public static function issue_code(int $certificateid, int $userid): string {
        return sha1($certificateid . ':' . $userid . ':' . microtime(true) . ':' . random_string(16));
    }

    /**
     * Ensures a certificate issue record exists for the user.
     *
     * @param \stdClass $certificate Certificate record.
     * @param int $userid User id.
     * @return \stdClass
     */
    public static function ensure_issue(\stdClass $certificate, int $userid): \stdClass {
        global $DB;

        $issue = $DB->get_record(
            'easycertificate_issues',
            [
                'easycertificateid' => $certificate->id,
                'userid' => $userid,
            ]
        );

        if ($issue) {
            return $issue;
        }

        $issue = (object) [
            'easycertificateid' => $certificate->id,
            'userid' => $userid,
            'code' => self::issue_code((int) $certificate->id, $userid),
            'timecreated' => time(),
        ];
        $issue->id = $DB->insert_record('easycertificate_issues', $issue);

        return $issue;
    }

    /**
     * Outputs the certificate PDF.
     *
     * @param \stdClass $certificate Certificate record.
     * @param \stdClass $template Template record.
     * @param \stdClass $user User record.
     * @param \stdClass $course Course record.
     * @param string $dest Output destination.
     */
    public static function output_pdf(
        \stdClass $certificate,
        \stdClass $template,
        \stdClass $user,
        \stdClass $course,
        string $dest = 'I'
    ): void {
        global $CFG;

        require_once($CFG->libdir . '/pdflib.php');

        $pages = json_decode($template->pagesjson ?? '[]');
        $elements = json_decode($template->elementsjson ?? '[]');

        if (!$pages) {
            $pages = [
                (object) [
                    'id' => 'p1',
                    'background' => '',
                    'width' => 1123,
                    'height' => 794,
                ],
            ];
        }

        $orientation = !empty($template->orientation) ? $template->orientation : 'L';
        $format = !empty($template->format) ? $template->format : 'A4';

        $pdf = new \pdf($orientation, 'mm', $format, true, 'UTF-8', false);
        $pdf->SetCreator('Moodle');
        $pdf->SetAuthor(format_string($course->fullname));
        $pdf->SetTitle(format_string($certificate->name));
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        foreach ($pages as $page) {
            $pdf->AddPage($orientation, $format);

            $pagewidth = $pdf->getPageWidth();
            $pageheight = $pdf->getPageHeight();
            $sourcewidth = !empty($page->width) ? (float) $page->width : 1123.0;
            $sourceheight = !empty($page->height) ? (float) $page->height : 794.0;

            if (!empty($page->background) && preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $page->background)) {
                $background = base64_decode(substr($page->background, strpos($page->background, ',') + 1));
                $pdf->Image('@' . $background, 0, 0, $pagewidth, $pageheight, '', '', '', false, 300);
            }

            foreach ($elements as $element) {
                if (($element->pageid ?? 'p1') !== ($page->id ?? 'p1')) {
                    continue;
                }

                $x = ((float) ($element->x ?? 0) / $sourcewidth) * $pagewidth;
                $y = ((float) ($element->y ?? 0) / $sourceheight) * $pageheight;
                $w = ((float) ($element->w ?? 180) / $sourcewidth) * $pagewidth;
                $h = ((float) ($element->h ?? 40) / $sourceheight) * $pageheight;
                $type = $element->type ?? 'text';

                if (($type === 'image' || $type === 'signature') && (!empty($element->src) || !empty($element->mask))) {
                    $imagedata = self::decode_image_data((string) ($element->src ?? $element->mask ?? ''));

                    if ($imagedata !== false) {
                        [$ix, $iy, $iw, $ih] = self::fit_image_box($imagedata, $x, $y, $w, $h);
                        $pdf->Image('@' . $imagedata, $ix, $iy, $iw, $ih, '', '', '', false, 300);
                    }

                    continue;
                }

                if ($type === 'border') {
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->Rect($x, $y, $w, $h);
                    continue;
                }

                $font = preg_replace('/[^a-zA-Z0-9_\-]/', '', $element->font ?? 'helvetica');
                $sizepx = max(8, (int) ($element->size ?? 24));
                $sizept = $sizepx * 0.75;
                $style = '';

                if (!empty($element->bold)) {
                    $style .= 'B';
                }

                if (!empty($element->italic)) {
                    $style .= 'I';
                }

                $pdf->SetFont($font ?: 'helvetica', $style, $sizept);
                $rgb = self::hex_to_rgb($element->color ?? '#111111');
                $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);

                $text = self::resolve_text((string) ($element->text ?? ''), $user, $course);
                $align = strtoupper(substr($element->align ?? 'L', 0, 1));
                $pdf->writeHTMLCell($w, $h, $x, $y, $text, 0, 1, false, true, $align, true);
            }
        }

        $filename = clean_filename($certificate->name . '-' . fullname($user) . '.pdf');
        $content = $pdf->Output('', 'S');
        $content = self::apply_incremental_signatures($content, $elements);

        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Length: ' . strlen($content));
            header(
                'Content-Disposition: ' . (($dest === 'D') ? 'attachment' : 'inline') . '; filename="' . $filename . '"'
            );
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }

        echo $content;
    }

    /**
     * Applies incremental PDF signatures.
     *
     * @param string $pdfcontent PDF content.
     * @param array $elements Certificate elements.
     * @return string
     */
    private static function apply_incremental_signatures(string $pdfcontent, array $elements): string {
        if (!function_exists('openssl_pkcs12_read')) {
            return $pdfcontent;
        }

        $index = 1;

        foreach ($elements as $element) {
            if (($element->type ?? '') !== 'signature' || empty($element->cert)) {
                continue;
            }

            $signed = self::append_pdf_signature($pdfcontent, $element, $index);

            if ($signed !== false) {
                $pdfcontent = $signed;
                $index++;
            }
        }

        return $pdfcontent;
    }

    /**
     * Appends a single incremental PDF signature.
     *
     * @param string $pdfcontent PDF content.
     * @param \stdClass $element Signature element.
     * @param int $index Signature index.
     * @return string|false
     */
    private static function append_pdf_signature(string $pdfcontent, \stdClass $element, int $index) {
        $pfxdata = self::decode_file_data((string) $element->cert);

        if ($pfxdata === false) {
            return false;
        }

        $certs = [];
        $password = (string) ($element->password ?? '');

        if (!@openssl_pkcs12_read($pfxdata, $certs, $password) || empty($certs['cert']) || empty($certs['pkey'])) {
            return false;
        }

        $extracerts = self::normalize_extra_certs($certs['extracerts'] ?? []);
        $root = self::get_pdf_root_object($pdfcontent);

        if (!$root) {
            return false;
        }

        $catalog = self::get_latest_pdf_object($pdfcontent, $root);

        if ($catalog === false) {
            return false;
        }

        $maxobject = self::get_pdf_max_object_number($pdfcontent);
        $fieldobject = $maxobject + 1;
        $sigobject = $maxobject + 2;
        $acroobject = $maxobject + 3;
        $fields = self::get_pdf_signature_fields($pdfcontent, $catalog);
        $fields[] = $fieldobject . ' 0 R';

        $catalog = self::set_pdf_catalog_acroform($catalog, $acroobject . ' 0 R');

        if ($index === 1) {
            $catalog = self::set_pdf_catalog_docmdp($catalog, $sigobject . ' 0 R');
        }

        $pageobject = self::get_pdf_first_page_object($pdfcontent);
        $pagecontent = $pageobject ? self::get_latest_pdf_object($pdfcontent, $pageobject) : false;

        if ($pagecontent !== false) {
            $pagecontent = self::set_pdf_page_annots($pagecontent, $fieldobject . ' 0 R');
        }

        $fieldname = self::escape_pdf_string('Assinatura ' . $index);
        $date = gmdate('YmdHis');
        $placeholderbytes = 24000;
        $placeholderhex = str_repeat('0', $placeholderbytes * 2);
        $brplaceholder = '0000000000 0000000000 0000000000 0000000000';
        $offsets = [];
        $increment = "\n";

        $offsets[$root] = strlen($pdfcontent) + strlen($increment);
        $increment .= $root . " 0 obj\n" . $catalog . "\nendobj\n";

        if ($pagecontent !== false) {
            $offsets[$pageobject] = strlen($pdfcontent) + strlen($increment);
            $increment .= $pageobject . " 0 obj\n" . $pagecontent . "\nendobj\n";
        }

        $offsets[$acroobject] = strlen($pdfcontent) + strlen($increment);
        $increment .= $acroobject . " 0 obj\n";
        $increment .= '<< /Fields [' . implode(' ', $fields) . "] /SigFlags 3 >>\nendobj\n";

        $offsets[$fieldobject] = strlen($pdfcontent) + strlen($increment);
        $pageref = $pagecontent !== false ? ' /P ' . $pageobject . ' 0 R' : '';
        $increment .= $fieldobject . " 0 obj\n";
        $increment .= '<< /Type /Annot /Subtype /Widget /FT /Sig /Rect [0 0 0 0] /F 132';
        $increment .= $pageref . ' /T (' . $fieldname . ') /V ' . $sigobject . " 0 R >>\nendobj\n";

        $offsets[$sigobject] = strlen($pdfcontent) + strlen($increment);
        $reference = '';

        if ($index === 1) {
            $reference = ' /Reference [<< /Type /SigRef /TransformMethod /DocMDP /DigestMethod /SHA256 ';
            $reference .= '/TransformParams << /Type /TransformParams /P 2 /V /1.2 >> >>]';
        }

        $increment .= $sigobject . " 0 obj\n";
        $increment .= '<< /Type /Sig /Filter /Adobe.PPKLite /SubFilter /adbe.pkcs7.detached ';
        $increment .= '/ByteRange [' . $brplaceholder . '] /Contents <' . $placeholderhex . '>';
        $increment .= $reference . ' /Reason (Certificate issue) /M (D:' . $date . "+00'00') >>\nendobj\n";

        $xrefstart = strlen($pdfcontent) + strlen($increment);
        $objects = array_keys($offsets);
        sort($objects, SORT_NUMERIC);

        $increment .= "xref\n";

        foreach (self::group_pdf_xref_objects($objects) as $group) {
            $increment .= $group[0] . ' ' . count($group[1]) . "\n";

            foreach ($group[1] as $objectnum) {
                $increment .= sprintf("%010d 00000 n \n", $offsets[$objectnum]);
            }
        }

        $previous = self::get_pdf_previous_xref($pdfcontent);
        $size = $sigobject + 2;

        $increment .= "trailer\n";
        $increment .= '<< /Size ' . $size . ' /Root ' . $root . ' 0 R /Prev ' . $previous . " >>\n";
        $increment .= "startxref\n" . $xrefstart . "\n%%EOF\n";

        $unsigned = $pdfcontent . $increment;
        $contentspos = strpos($unsigned, '/Contents <' . $placeholderhex . '>');
        $byterangepos = strpos($unsigned, '/ByteRange [' . $brplaceholder . ']');

        if ($contentspos === false || $byterangepos === false) {
            return false;
        }

        $hexstart = $contentspos + strlen('/Contents <');
        $hexend = $hexstart + strlen($placeholderhex);
        $rangestart = $hexstart - 1;
        $rangeend = $hexend + 1;
        $byterange = sprintf(
            '%010d %010d %010d %010d',
            0,
            $rangestart,
            $rangeend,
            strlen($unsigned) - $rangeend
        );

        $unsigned = substr_replace(
            $unsigned,
            $byterange,
            $byterangepos + strlen('/ByteRange ['),
            strlen($brplaceholder)
        );

        $tosign = substr($unsigned, 0, $rangestart) . substr($unsigned, $rangeend);
        $signature = self::create_cms_signature($tosign, $certs['cert'], $certs['pkey'], $extracerts);

        if ($signature === false) {
            return false;
        }

        $signaturehex = bin2hex($signature);

        if (strlen($signaturehex) > strlen($placeholderhex)) {
            return false;
        }

        return substr_replace(
            $unsigned,
            str_pad($signaturehex, strlen($placeholderhex), '0'),
            $hexstart,
            strlen($placeholderhex)
        );
    }

    /**
     * Creates a CMS detached signature.
     *
     * @param string $data Data to sign.
     * @param string $cert Certificate content.
     * @param string $pkey Private key content.
     * @param array $extracerts Extra certificates.
     * @return string|false
     */
    private static function create_cms_signature(string $data, string $cert, string $pkey, array $extracerts = []) {
        global $CFG;

        make_temp_directory('mod_easycertificate');

        $base = $CFG->tempdir . '/mod_easycertificate/sign_' . uniqid('', true);
        $input = $base . '.bin';
        $output = $base . '.p7s';
        $certfile = $base . '.crt';
        $keyfile = $base . '.key';
        $chainfile = $base . '.chain';

        file_put_contents($input, $data);
        file_put_contents($certfile, $cert);
        file_put_contents($keyfile, $pkey);

        $chainpath = null;

        if (!empty($extracerts)) {
            file_put_contents($chainfile, implode("\n", $extracerts));
            $chainpath = $chainfile;
        }

        $signature = self::create_cms_signature_with_openssl_cli(
            $input,
            $output,
            $certfile,
            $keyfile,
            $chainpath
        );

        if ($signature === false) {
            $flags = OPENSSL_CMS_BINARY | OPENSSL_CMS_DETACHED;
            $ok = @openssl_cms_sign(
                $input,
                $output,
                'file://' . $certfile,
                'file://' . $keyfile,
                [],
                $flags,
                OPENSSL_ENCODING_DER,
                $chainpath
            );
            $signature = $ok && file_exists($output) ? file_get_contents($output) : false;
        }

        self::delete_file_if_exists($input);
        self::delete_file_if_exists($output);
        self::delete_file_if_exists($certfile);
        self::delete_file_if_exists($keyfile);
        self::delete_file_if_exists($chainfile);

        return $signature;
    }

    /**
     * Creates a CMS detached signature using the OpenSSL command line.
     *
     * @param string $input Input file path.
     * @param string $output Output file path.
     * @param string $certfile Certificate file path.
     * @param string $keyfile Private key file path.
     * @param string|null $chainpath Certificate chain file path.
     * @return string|false
     */
    private static function create_cms_signature_with_openssl_cli(
        string $input,
        string $output,
        string $certfile,
        string $keyfile,
        ?string $chainpath
    ) {
        $cmd = [
            'openssl',
            'cms',
            '-sign',
            '-binary',
            '-cades',
            '-nosmimecap',
            '-md',
            'sha256',
            '-in',
            $input,
            '-signer',
            $certfile,
            '-inkey',
            $keyfile,
            '-outform',
            'DER',
            '-out',
            $output,
        ];

        if (!empty($chainpath)) {
            $cmd[] = '-certfile';
            $cmd[] = $chainpath;
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($cmd, $descriptors, $pipes);

        if (!is_resource($process)) {
            return false;
        }

        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $status = proc_close($process);

        if ($status !== 0 || !file_exists($output)) {
            return false;
        }

        $signature = file_get_contents($output);

        return $signature !== false && $signature !== '' ? $signature : false;
    }

    /**
     * Normalizes extra certificates from a PKCS#12 container.
     *
     * @param mixed $extracerts Extra certificates.
     * @return array
     */
    private static function normalize_extra_certs($extracerts): array {
        if (empty($extracerts)) {
            return [];
        }

        if (is_string($extracerts)) {
            return [$extracerts];
        }

        if (!is_array($extracerts)) {
            return [];
        }

        $certs = [];

        foreach ($extracerts as $cert) {
            if (is_string($cert) && trim($cert) !== '') {
                $certs[] = $cert;
            }
        }

        return $certs;
    }

    /**
     * Gets the PDF root object number.
     *
     * @param string $pdfcontent PDF content.
     * @return int
     */
    private static function get_pdf_root_object(string $pdfcontent): int {
        if (preg_match_all('/trailer\s*<<(.*?)>>/s', $pdfcontent, $matches) && !empty($matches[1])) {
            for ($i = count($matches[1]) - 1; $i >= 0; $i--) {
                if (preg_match('/\/Root\s+(\d+)\s+0\s+R/', $matches[1][$i], $root)) {
                    return (int) $root[1];
                }
            }
        }

        return 0;
    }

    /**
     * Gets the previous xref offset from the PDF.
     *
     * @param string $pdfcontent PDF content.
     * @return int
     */
    private static function get_pdf_previous_xref(string $pdfcontent): int {
        if (preg_match_all('/startxref\s*(\d+)\s*%%EOF/s', $pdfcontent, $matches) && !empty($matches[1])) {
            return (int) end($matches[1]);
        }

        return 0;
    }

    /**
     * Gets the highest PDF object number.
     *
     * @param string $pdfcontent PDF content.
     * @return int
     */
    private static function get_pdf_max_object_number(string $pdfcontent): int {
        preg_match_all('/(?:^|\n)(\d+)\s+0\s+obj\b/', $pdfcontent, $matches);

        return empty($matches[1]) ? 0 : max(array_map('intval', $matches[1]));
    }

    /**
     * Gets the latest revision of a PDF object.
     *
     * @param string $pdfcontent PDF content.
     * @param int $objectnum Object number.
     * @return string|false
     */
    private static function get_latest_pdf_object(string $pdfcontent, int $objectnum) {
        $pattern = '/(?:^|\n)' . preg_quote((string) $objectnum, '/') . '\s+0\s+obj\s*(.*?)\s*endobj/s';

        if (!preg_match_all($pattern, $pdfcontent, $matches) || empty($matches[1])) {
            return false;
        }

        return trim(end($matches[1]));
    }

    /**
     * Gets the first PDF page object number.
     *
     * @param string $pdfcontent PDF content.
     * @return int
     */
    private static function get_pdf_first_page_object(string $pdfcontent): int {
        $pattern = '/(?:^|\n)(\d+)\s+0\s+obj\s*(<<.*?\/Type\s*\/Page\b(?!s).*?>>).*?endobj/s';

        if (preg_match_all($pattern, $pdfcontent, $matches)) {
            return (int) $matches[1][0];
        }

        return 0;
    }

    /**
     * Adds a widget annotation reference to a PDF page object.
     *
     * @param string $pagecontent Page object content.
     * @param string $annotref Annotation reference.
     * @return string
     */
    private static function set_pdf_page_annots(string $pagecontent, string $annotref): string {
        if (preg_match('/\/Annots\s*\[(.*?)\]/s', $pagecontent, $annots)) {
            $current = trim($annots[1]);

            if (strpos($current, $annotref) !== false) {
                return $pagecontent;
            }

            $replacement = '/Annots [' . trim($current . ' ' . $annotref) . ']';

            return preg_replace('/\/Annots\s*\[(.*?)\]/s', $replacement, $pagecontent, 1);
        }

        if (preg_match('/<<\s*/', $pagecontent, $match, PREG_OFFSET_CAPTURE)) {
            $pos = $match[0][1] + strlen($match[0][0]);

            return substr($pagecontent, 0, $pos) . '/Annots [' . $annotref . '] ' . substr($pagecontent, $pos);
        }

        return $pagecontent;
    }

    /**
     * Adds or replaces the AcroForm reference in the PDF catalog.
     *
     * @param string $catalog Catalog object content.
     * @param string $acroformref AcroForm object reference.
     * @return string
     */
    private static function set_pdf_catalog_acroform(string $catalog, string $acroformref): string {
        if (preg_match('/\/AcroForm\s+\d+\s+0\s+R/', $catalog)) {
            return preg_replace('/\/AcroForm\s+\d+\s+0\s+R/', '/AcroForm ' . $acroformref, $catalog, 1);
        }

        if (preg_match('/<<\s*/', $catalog, $match, PREG_OFFSET_CAPTURE)) {
            $pos = $match[0][1] + strlen($match[0][0]);

            return substr($catalog, 0, $pos) . '/AcroForm ' . $acroformref . ' ' . substr($catalog, $pos);
        }

        return $catalog;
    }

    /**
     * Adds or replaces the DocMDP permission reference in the PDF catalog.
     *
     * @param string $catalog Catalog object content.
     * @param string $sigref Signature object reference.
     * @return string
     */
    private static function set_pdf_catalog_docmdp(string $catalog, string $sigref): string {
        if (preg_match('/\/Perms\s*<<.*?\/DocMDP\s+\d+\s+0\s+R.*?>>/s', $catalog)) {
            return preg_replace(
                '/\/Perms\s*<<.*?\/DocMDP\s+\d+\s+0\s+R.*?>>/s',
                '/Perms << /DocMDP ' . $sigref . ' >>',
                $catalog,
                1
            );
        }

        if (preg_match('/<<\s*/', $catalog, $match, PREG_OFFSET_CAPTURE)) {
            $pos = $match[0][1] + strlen($match[0][0]);

            return substr($catalog, 0, $pos) . '/Perms << /DocMDP ' . $sigref . ' >> ' . substr($catalog, $pos);
        }

        return $catalog;
    }

    /**
     * Gets existing PDF signature field references.
     *
     * @param string $pdfcontent PDF content.
     * @param string $catalog Catalog object content.
     * @return array
     */
    private static function get_pdf_signature_fields(string $pdfcontent, string $catalog): array {
        if (!preg_match('/\/AcroForm\s+(\d+)\s+0\s+R/', $catalog, $acro)) {
            return [];
        }

        $acroform = self::get_latest_pdf_object($pdfcontent, (int) $acro[1]);

        if ($acroform === false || !preg_match('/\/Fields\s*\[(.*?)\]/s', $acroform, $fields)) {
            return [];
        }

        preg_match_all('/\d+\s+0\s+R/', $fields[1], $refs);

        return $refs[0] ?? [];
    }

    /**
     * Groups consecutive PDF objects for xref sections.
     *
     * @param array $objects Object numbers.
     * @return array
     */
    private static function group_pdf_xref_objects(array $objects): array {
        $groups = [];
        $currentstart = null;
        $current = [];

        foreach ($objects as $object) {
            if ($currentstart === null) {
                $currentstart = $object;
                $current = [$object];
                continue;
            }

            if ($object === end($current) + 1) {
                $current[] = $object;
                continue;
            }

            $groups[] = [$currentstart, $current];
            $currentstart = $object;
            $current = [$object];
        }

        if ($currentstart !== null) {
            $groups[] = [$currentstart, $current];
        }

        return $groups;
    }

    /**
     * Escapes text for PDF string values.
     *
     * @param string $value Text value.
     * @return string
     */
    private static function escape_pdf_string(string $value): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    /**
     * Decodes a base64 data URI.
     *
     * @param string $src Data URI.
     * @return string|false
     */
    private static function decode_file_data(string $src) {
        if (!preg_match('/^data:[^;]+;base64,/', $src)) {
            return false;
        }

        return base64_decode(substr($src, strpos($src, ',') + 1));
    }

    /**
     * Decodes a base64 image data URI.
     *
     * @param string $src Image data URI.
     * @return string|false
     */
    private static function decode_image_data(string $src) {
        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $src)) {
            return false;
        }

        return base64_decode(substr($src, strpos($src, ',') + 1));
    }

    /**
     * Calculates image dimensions fitted inside a box.
     *
     * @param string $imagedata Image binary content.
     * @param float $x Box x position.
     * @param float $y Box y position.
     * @param float $w Box width.
     * @param float $h Box height.
     * @return array
     */
    private static function fit_image_box(string $imagedata, float $x, float $y, float $w, float $h): array {
        $info = @getimagesizefromstring($imagedata);

        if (empty($info[0]) || empty($info[1]) || $w <= 0 || $h <= 0) {
            return [$x, $y, $w, $h];
        }

        $ratio = min($w / (float) $info[0], $h / (float) $info[1]);
        $fitw = (float) $info[0] * $ratio;
        $fith = (float) $info[1] * $ratio;
        $fitx = $x + (($w - $fitw) / 2);
        $fity = $y + (($h - $fith) / 2);

        return [$fitx, $fity, $fitw, $fith];
    }

    /**
     * Converts a hexadecimal color to RGB values.
     *
     * @param string $hex Hexadecimal color.
     * @return array
     */
    private static function hex_to_rgb(string $hex): array {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return [17, 17, 17];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Deletes a file when it exists.
     *
     * @param string $filepath File path.
     */
    private static function delete_file_if_exists(string $filepath): void {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}
