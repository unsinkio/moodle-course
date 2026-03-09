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

namespace format_videoclass\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * Delete a personal note (cascades recipients).
 *
 * @package   format_videoclass
 */
class delete_personal_note extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'noteid' => new external_value(PARAM_INT, 'Note ID to delete'),
        ]);
    }

    public static function execute(int $noteid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'noteid' => $noteid,
        ]);

        $note = $DB->get_record('format_videoclass_notes', ['id' => $params['noteid']], '*', MUST_EXIST);

        $context = \context_course::instance($note->courseid);
        self::validate_context($context);

        $isadmin = has_capability('moodle/course:update', $context);
        if (!$isadmin && (int) $note->userid !== (int) $USER->id) {
            throw new \moodle_exception('nopermissions', 'error', '', 'delete this note');
        }

        // Cascade: delete recipients first, then the note.
        $DB->delete_records('format_videoclass_note_recipients', ['noteid' => $note->id]);
        $DB->delete_records('format_videoclass_notes', ['id' => $note->id]);

        return ['success' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether deletion succeeded'),
        ]);
    }
}
