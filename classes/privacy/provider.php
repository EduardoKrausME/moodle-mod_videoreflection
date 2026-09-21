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

namespace mod_videoreflection\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider for Video Reflection.
 *
 * @package   mod_videoreflection
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, plugin_provider, core_userlist_provider {
    /**
     * Describes personal data stored by the plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('videoreflection_entries', [
            'videoreflectionid' => 'privacy:metadata:videoreflection_entries:videoreflectionid',
            'questionid' => 'privacy:metadata:videoreflection_entries:questionid',
            'userid' => 'privacy:metadata:videoreflection_entries:userid',
            'timepoint' => 'privacy:metadata:videoreflection_entries:timepoint',
            'entrytype' => 'privacy:metadata:videoreflection_entries:entrytype',
            'reflectiontext' => 'privacy:metadata:videoreflection_entries:reflectiontext',
            'shared' => 'privacy:metadata:videoreflection_entries:shared',
            'timecreated' => 'privacy:metadata:videoreflection_entries:timecreated',
        ], 'privacy:metadata:videoreflection_entries');
        $collection->add_database_table('videoreflection_progress', [
            'videoreflectionid' => 'privacy:metadata:videoreflection_progress:videoreflectionid',
            'userid' => 'privacy:metadata:videoreflection_progress:userid',
            'lastposition' => 'privacy:metadata:videoreflection_progress:lastposition',
            'percent' => 'privacy:metadata:videoreflection_progress:percent',
            'watchedsegments' => 'privacy:metadata:videoreflection_progress:watchedsegments',
            'timemodified' => 'privacy:metadata:videoreflection_progress:timemodified',
        ], 'privacy:metadata:videoreflection_progress');
        return $collection;
    }

    /**
     * Finds module contexts containing data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {videoreflection} v ON v.id = cm.instance
             LEFT JOIN {videoreflection_entries} e ON e.videoreflectionid = v.id AND e.userid = :entryuserid
             LEFT JOIN {videoreflection_progress} p ON p.videoreflectionid = v.id AND p.userid = :progressuserid
                 WHERE ctx.contextlevel = :contextlevel
                   AND (e.id IS NOT NULL OR p.id IS NOT NULL)";
        $contextlist->add_from_sql($sql, [
            'modname' => 'videoreflection',
            'entryuserid' => $userid,
            'progressuserid' => $userid,
            'contextlevel' => CONTEXT_MODULE,
        ]);
        return $contextlist;
    }

    /**
     * Exports personal data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videoreflection', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $activity = $DB->get_record('videoreflection', ['id' => $cm->instance]);
            if (!$activity) {
                continue;
            }
            $entries = $DB->get_records('videoreflection_entries', [
                'videoreflectionid' => $activity->id,
                'userid' => $userid,
            ], 'timecreated ASC');
            $progress = $DB->get_record('videoreflection_progress', [
                'videoreflectionid' => $activity->id,
                'userid' => $userid,
            ]);
            writer::with_context($context)->export_data([
                get_string('pluginname', 'videoreflection'),
                format_string($activity->name),
            ], (object)[
                'entries' => array_values($entries),
                'progress' => $progress ?: null,
            ]);
        }
    }

    /**
     * Deletes all user data stored in one module context.
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videoreflection', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $DB->delete_records('videoreflection_entries', ['videoreflectionid' => $cm->instance]);
        $DB->delete_records('videoreflection_progress', ['videoreflectionid' => $cm->instance]);
    }

    /**
     * Deletes data for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('videoreflection', $context->instanceid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('videoreflection_entries', [
                'videoreflectionid' => $cm->instance,
                'userid' => $userid,
            ]);
            $DB->delete_records('videoreflection_progress', [
                'videoreflectionid' => $cm->instance,
                'userid' => $userid,
            ]);
        }
    }

    /**
     * Adds users with stored data to a context user list.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videoreflection', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $sql = "SELECT userid FROM {videoreflection_entries} WHERE videoreflectionid = :activityid
                UNION
                SELECT userid FROM {videoreflection_progress} WHERE videoreflectionid = :activityid2";
        $userlist->add_from_sql('userid', $sql, [
            'activityid' => $cm->instance,
            'activityid2' => $cm->instance,
        ]);
    }

    /**
     * Deletes data for approved users in one context.
     *
     * @param approved_userlist $userlist Approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('videoreflection', $context->instanceid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'privacyuser');
        $params['activityid'] = $cm->instance;
        $DB->delete_records_select(
            'videoreflection_entries',
            "videoreflectionid = :activityid AND userid {$insql}",
            $params
        );
        $DB->delete_records_select(
            'videoreflection_progress',
            "videoreflectionid = :activityid AND userid {$insql}",
            $params
        );
    }
}
