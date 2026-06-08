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
 * Privacy provider for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @category   privacy
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_easycertificate\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for the Easy Certificate activity module.
 *
 * @package    mod_easycertificate
 * @category   privacy
 * @copyright  2026 Marcelo M. Almeida Júnior
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describes the personal data stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'easycertificate_issues',
            [
                'easycertificateid' => 'privacy:metadata:easycertificate_issues:easycertificateid',
                'userid' => 'privacy:metadata:easycertificate_issues:userid',
                'code' => 'privacy:metadata:easycertificate_issues:code',
                'timecreated' => 'privacy:metadata:easycertificate_issues:timecreated',
            ],
            'privacy:metadata:easycertificate_issues'
        );

        $collection->add_database_table(
            'easycertificate_templates',
            [
                'name' => 'privacy:metadata:easycertificate_templates:name',
                'description' => 'privacy:metadata:easycertificate_templates:description',
                'usermodified' => 'privacy:metadata:easycertificate_templates:usermodified',
                'timemodified' => 'privacy:metadata:easycertificate_templates:timemodified',
            ],
            'privacy:metadata:easycertificate_templates'
        );

        return $collection;
    }

    /**
     * Gets contexts containing data for the supplied user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm
                    ON cm.id = ctx.instanceid
                   AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m
                    ON m.id = cm.module
                   AND m.name = :modname
                  JOIN {easycertificate} ec
                    ON ec.id = cm.instance
                  JOIN {easycertificate_issues} eci
                    ON eci.easycertificateid = ec.id
                 WHERE eci.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'easycertificate',
            'userid' => $userid,
        ]);

        if ($DB->record_exists('easycertificate_templates', ['usermodified' => $userid])) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Exports user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_system) {
                self::export_template_data($context, $user->id);
                continue;
            }

            if (!$context instanceof \context_module) {
                continue;
            }

            $certificate = self::get_certificate_from_context($context);
            if (!$certificate) {
                continue;
            }

            $issue = $DB->get_record('easycertificate_issues', [
                'easycertificateid' => $certificate->id,
                'userid' => $user->id,
            ]);

            if (!$issue) {
                continue;
            }

            $data = (object) [
                'name' => format_string($certificate->name),
                'code' => $issue->code,
                'timecreated' => transform::datetime($issue->timecreated),
            ];

            writer::with_context($context)->export_data(
                [get_string('privacy:path:issue', 'easycertificate')],
                $data
            );
        }
    }

    /**
     * Deletes all user data in a module context.
     *
     * @param \context $context Context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \context_system) {
            $DB->set_field('easycertificate_templates', 'usermodified', 0);
            return;
        }

        if (!$context instanceof \context_module) {
            return;
        }

        $certificate = self::get_certificate_from_context($context);
        if (!$certificate) {
            return;
        }

        $DB->delete_records('easycertificate_issues', ['easycertificateid' => $certificate->id]);
    }

    /**
     * Deletes user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_system) {
                $DB->set_field('easycertificate_templates', 'usermodified', 0, ['usermodified' => $user->id]);
                continue;
            }

            if (!$context instanceof \context_module) {
                continue;
            }

            $certificate = self::get_certificate_from_context($context);
            if (!$certificate) {
                continue;
            }

            $DB->delete_records('easycertificate_issues', [
                'easycertificateid' => $certificate->id,
                'userid' => $user->id,
            ]);
        }
    }

    /**
     * Adds users with data in the supplied context.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context instanceof \context_system) {
            $userlist->add_from_sql(
                'usermodified',
                'SELECT DISTINCT usermodified FROM {easycertificate_templates} WHERE usermodified <> 0',
                []
            );
            return;
        }

        if (!$context instanceof \context_module) {
            return;
        }

        $certificate = self::get_certificate_from_context($context);
        if (!$certificate) {
            return;
        }

        $userlist->add_from_sql(
            'userid',
            'SELECT userid FROM {easycertificate_issues} WHERE easycertificateid = :easycertificateid',
            ['easycertificateid' => $certificate->id]
        );
    }

    /**
     * Deletes data for approved users in a context.
     *
     * @param approved_userlist $userlist Approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        if ($context instanceof \context_system) {
            list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $DB->set_field_select('easycertificate_templates', 'usermodified', 0, "usermodified {$insql}", $params);
            return;
        }

        if (!$context instanceof \context_module) {
            return;
        }

        $certificate = self::get_certificate_from_context($context);
        if (!$certificate) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['easycertificateid'] = $certificate->id;

        $DB->delete_records_select(
            'easycertificate_issues',
            "easycertificateid = :easycertificateid AND userid {$insql}",
            $params
        );
    }

    /**
     * Exports template records modified by the supplied user.
     *
     * @param \context_system $context System context.
     * @param int $userid User id.
     */
    private static function export_template_data(\context_system $context, int $userid): void {
        global $DB;

        $templates = $DB->get_records('easycertificate_templates', ['usermodified' => $userid], 'timemodified ASC');
        foreach ($templates as $template) {
            $data = (object) [
                'name' => format_string($template->name),
                'description' => clean_text((string) $template->description, FORMAT_HTML),
                'timemodified' => transform::datetime($template->timemodified),
            ];

            writer::with_context($context)->export_data(
                [get_string('privacy:path:template', 'easycertificate'), $template->id],
                $data
            );
        }
    }

    /**
     * Returns the certificate instance for a module context.
     *
     * @param \context_module $context Module context.
     * @return \stdClass|null
     */
    private static function get_certificate_from_context(\context_module $context): ?\stdClass {
        global $DB;

        $cm = get_coursemodule_from_id('easycertificate', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return null;
        }

        return $DB->get_record('easycertificate', ['id' => $cm->instance], '*', IGNORE_MISSING) ?: null;
    }
}
