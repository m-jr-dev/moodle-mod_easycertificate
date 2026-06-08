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
 * English language strings for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @category   string
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['createtemplate'] = 'Create template';

$string['databasenotready'] = 'The plugin tables have not been created yet. Go to Site administration > Notifications to complete the upgrade.';

$string['downloadcertificate'] = 'Download certificate';

$string['easycertificate:addinstance'] = 'Add Easy Certificate';
$string['easycertificate:manage'] = 'Manage certificate';
$string['easycertificate:managetemplates'] = 'Manage global templates';
$string['easycertificate:view'] = 'View certificate';

$string['edittemplate'] = 'Edit template';

$string['gotoadminnotifications'] = 'Upgrade database';

$string['issuedcertificate'] = 'Certificate issued';

$string['managetemplates'] = 'Manage templates';

$string['modulename'] = 'Easy Certificate';
$string['modulenameplural'] = 'Easy Certificates';

$string['notemplate'] = 'No templates available';

$string['opentemplatemanager'] = 'Open template manager';

$string['pluginadministration'] = 'Easy Certificate administration';
$string['pluginname'] = 'Easy Certificate';

$string['privacy:metadata'] = 'The Easy Certificate plugin stores certificate issue records and template modification metadata.';

$string['selecttemplate'] = 'Select template';

$string['template'] = 'Template';
$string['template_help'] = 'Global template used to issue the certificate for this activity.';

$string['templates'] = 'Templates';

$string['active'] = 'Active';
$string['add'] = 'Add';
$string['addeditems'] = 'Added items';
$string['back'] = 'Back';
$string['backgroundimage'] = 'Background image';
$string['border'] = 'Border';
$string['cancel'] = 'Cancel';
$string['certificatepassword'] = 'Certificate password';
$string['close'] = 'Close';
$string['color'] = 'Color';
$string['concatenation'] = 'Concatenation';
$string['coursefield_course'] = 'Course full name';
$string['coursefield_coursecategory'] = 'Course category';
$string['coursefield_courseidnumber'] = 'Course ID number';
$string['coursefield_courseshortname'] = 'Course short name';
$string['coursefields'] = 'Course fields';
$string['customfield'] = 'Custom field';
$string['customfields'] = 'Custom fields';
$string['date'] = 'Date';
$string['datefield_completiondate'] = 'Course completion date';
$string['datefield_currentdate'] = 'Current date';
$string['datefield_issuedate'] = 'Certificate issue date';
$string['datefield_currentdate_numeric'] = 'Current date (dd/mm/yyyy)';
$string['datefield_currentdate_long'] = 'Current date in full';
$string['datefield_issuedate_numeric'] = 'Certificate issue date (dd/mm/yyyy)';
$string['datefield_issuedate_long'] = 'Certificate issue date in full';
$string['datefield_completiondate_numeric'] = 'Course completion date (dd/mm/yyyy)';
$string['datefield_completiondate_long'] = 'Course completion date in full';
$string['dateformat_numeric'] = '%d/%m/%Y';
$string['dateformat_long'] = '%d %B %Y';
$string['dates'] = 'Dates';
$string['datetype'] = 'Date type';
$string['defaultpagename'] = 'Page {$a}';
$string['deleteconfirm'] = 'Delete this template?';
$string['digitalcertificatefile'] = 'Digital certificate (.pfx or .p12)';
$string['digitalsignature'] = 'Digital signature';
$string['duplicate'] = 'Duplicate';
$string['edit'] = 'Edit';
$string['enabledstatus'] = 'Enabled';
$string['field'] = 'Field';
$string['fieldhelpfirst'] = 'Use fields between braces. Examples:';
$string['fieldhelpsecond'] = 'For concatenation, enter free text together with fields:';
$string['file'] = 'File';
$string['howtouse'] = 'How to use';
$string['image'] = 'Image';
$string['inactive'] = 'Inactive';
$string['insertfieldconcat'] = 'Insert field in concatenation';
$string['invalidjson'] = 'Invalid template data.';
$string['itemname'] = 'Item name';
$string['landscape'] = 'Landscape';
$string['orientation'] = 'Orientation';
$string['page'] = 'Page';
$string['paper'] = 'Paper';
$string['portrait'] = 'Portrait';
$string['previewcertificate'] = 'Preview certificate';
$string['privacy:metadata:easycertificate_issues'] = 'Stores issued certificate records.';
$string['privacy:metadata:easycertificate_issues:code'] = 'Unique issue code.';
$string['privacy:metadata:easycertificate_issues:easycertificateid'] = 'Certificate activity instance ID.';
$string['privacy:metadata:easycertificate_issues:timecreated'] = 'Issue date.';
$string['privacy:metadata:easycertificate_issues:userid'] = 'User who received the certificate.';
$string['privacy:path:issue'] = 'Issued certificate';

$string['privacy:metadata:easycertificate_templates'] = 'Stores global certificate template records.';
$string['privacy:metadata:easycertificate_templates:description'] = 'Template description.';
$string['privacy:metadata:easycertificate_templates:name'] = 'Template name.';
$string['privacy:metadata:easycertificate_templates:timemodified'] = 'Template last modification date.';
$string['privacy:metadata:easycertificate_templates:usermodified'] = 'User who last modified the template.';
$string['privacy:path:template'] = 'Certificate template';
$string['remove'] = 'Remove';
$string['removepage'] = 'Remove page';
$string['rename'] = 'Rename';
$string['renameitem'] = 'Rename item';
$string['save'] = 'Save';
$string['selectedtextcolor'] = 'Selected text color';
$string['selectedtextsize'] = 'Selected text size';
$string['signature'] = 'Signature';
$string['signaturehelp'] = 'Provide a PFX/P12 certificate and password to sign the PDF. The visual mask is required and will be shown in the certificate.';
$string['signaturemask'] = 'Visual signature mask';
$string['signaturemaskrequired'] = 'Provide the visual signature mask.';
$string['size'] = 'Size';
$string['status'] = 'Status';
$string['templatecopyname'] = '{$a} - copy';
$string['templatedescription'] = 'Description';
$string['templatename'] = 'Template name';
$string['templatepreview'] = 'Template preview';
$string['templatesaved'] = 'Template saved.';
$string['text'] = 'Text';
$string['textconcat'] = 'Text / concatenation';
$string['textplaceholder'] = 'Example: {firstname} {lastname} | {course} | {issuedate}';
$string['timemodified'] = 'Updated at';
$string['userfield'] = 'User field';
$string['userfield_address'] = 'Address';
$string['userfield_alternatename'] = 'Alternate name';
$string['userfield_city'] = 'City';
$string['userfield_country'] = 'Country';
$string['userfield_department'] = 'Department';
$string['userfield_description'] = 'Description';
$string['userfield_email'] = 'Email';
$string['userfield_firstname'] = 'First name';
$string['userfield_firstnamephonetic'] = 'First name - phonetic';
$string['userfield_id'] = 'ID';
$string['userfield_idnumber'] = 'ID number';
$string['userfield_institution'] = 'Institution';
$string['userfield_lang'] = 'Language';
$string['userfield_lastname'] = 'Last name';
$string['userfield_lastnamephonetic'] = 'Last name - phonetic';
$string['userfield_middlename'] = 'Middle name';
$string['userfield_phone1'] = 'Phone 1';
$string['userfield_phone2'] = 'Phone 2';
$string['userfield_timezone'] = 'Timezone';
$string['userfield_username'] = 'Username';
$string['userfields'] = 'User fields';
$string['usebackgroundimage'] = 'Use as background image';
$string['viewcertificate'] = 'View certificate';
$string['zoomin'] = 'Zoom +';
$string['zoomout'] = 'Zoom -';
$string['notemplateassigned'] = 'No active template has been linked to this activity.';
$string['fieldhelpexample'] = 'This certifies that {firstname} completed {course} on {issuedate}';
$string['delete'] = 'Delete';
$string['noitemsadded'] = 'No items added.';
$string['resize'] = 'Resize';
$string['selectfield'] = 'Select field';
