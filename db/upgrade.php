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

    return true;
}
