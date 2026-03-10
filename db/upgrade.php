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
 * Upgrade steps for format_videoclass.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_format_videoclass_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026030902) {
        // Table: format_videoclass_notes (personal notes, shareable).
        $table = new xmldb_table('format_videoclass_notes');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
            $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('courseid_sectionid_userid_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'sectionid', 'userid']);
            $dbman->create_table($table);
        }

        // Table: format_videoclass_note_recipients (sharing links).
        $table2 = new xmldb_table('format_videoclass_note_recipients');
        if (!$dbman->table_exists($table2)) {
            $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table2->add_field('noteid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table2->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table2->add_field('timeshared', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table2->add_key('noteid_fk', XMLDB_KEY_FOREIGN, ['noteid'], 'format_videoclass_notes', ['id']);
            $table2->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table2->add_index('noteid_userid_idx', XMLDB_INDEX_UNIQUE, ['noteid', 'userid']);
            $dbman->create_table($table2);
        }

        // Drop old shared_notes table if it exists from previous version.
        $oldtable = new xmldb_table('format_videoclass_shared_notes');
        if ($dbman->table_exists($oldtable)) {
            $dbman->drop_table($oldtable);
        }

        upgrade_plugin_savepoint(true, 2026030902, 'format', 'videoclass');
    }

    if ($oldversion < 2026030903) {
        // Add timeshared column if missing (table may have been created by v1 without it).
        $table = new xmldb_table('format_videoclass_note_recipients');
        $field = new xmldb_field('timeshared', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026030903, 'format', 'videoclass');
    }

    if ($oldversion < 2026030905) {
        // Table: format_videoclass_chat_history (AI tutor chat).
        $table = new xmldb_table('format_videoclass_chat_history');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('role', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
            $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
            $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('courseid_sectionid_userid_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'sectionid', 'userid']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026030905, 'format', 'videoclass');
    }

    if ($oldversion < 2026030907) {
        // Add noteid column to chat_history to track saved notes.
        $table = new xmldb_table('format_videoclass_chat_history');
        $field = new xmldb_field('noteid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'message');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026030907, 'format', 'videoclass');
    }

    if ($oldversion < 2026031001) {
        // Table: format_videoclass_chat_conversations.
        $convtable = new xmldb_table('format_videoclass_chat_conversations');
        if (!$dbman->table_exists($convtable)) {
            $convtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $convtable->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $convtable->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $convtable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $convtable->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $convtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $convtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $convtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $convtable->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
            $convtable->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $convtable->add_index('courseid_sectionid_userid_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'sectionid', 'userid']);
            $dbman->create_table($convtable);
        }

        // Add conversationid column to chat_history.
        $historytable = new xmldb_table('format_videoclass_chat_history');
        $field = new xmldb_field('conversationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'userid');
        if (!$dbman->field_exists($historytable, $field)) {
            $dbman->add_field($historytable, $field);
        }

        // Migrate existing messages: create one conversation per (courseid, sectionid, userid) group.
        $sql = "SELECT DISTINCT courseid, sectionid, userid
                  FROM {format_videoclass_chat_history}
                 WHERE conversationid = 0";
        $groups = $DB->get_records_sql($sql);
        foreach ($groups as $group) {
            // Get first user message for title.
            $firstmsg = $DB->get_field_select(
                'format_videoclass_chat_history',
                'message',
                'courseid = :courseid AND sectionid = :sectionid AND userid = :userid AND role = :role',
                [
                    'courseid' => $group->courseid,
                    'sectionid' => $group->sectionid,
                    'userid' => $group->userid,
                    'role' => 'user',
                ],
                IGNORE_MULTIPLE
            );
            $title = $firstmsg ? substr($firstmsg, 0, 80) : 'Imported conversation';
            $now = time();
            $convid = $DB->insert_record('format_videoclass_chat_conversations', (object) [
                'courseid' => $group->courseid,
                'sectionid' => $group->sectionid,
                'userid' => $group->userid,
                'title' => $title,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
            $DB->set_field_select(
                'format_videoclass_chat_history',
                'conversationid',
                $convid,
                'courseid = :courseid AND sectionid = :sectionid AND userid = :userid AND conversationid = 0',
                [
                    'courseid' => $group->courseid,
                    'sectionid' => $group->sectionid,
                    'userid' => $group->userid,
                ]
            );
        }

        upgrade_plugin_savepoint(true, 2026031001, 'format', 'videoclass');
    }

    return true;
}
