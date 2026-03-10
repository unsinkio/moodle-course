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
 * Link or unlink a chat message to a personal note.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_videoclass\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class link_chat_note extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'chatid' => new external_value(PARAM_INT, 'Chat history message ID'),
            'noteid' => new external_value(PARAM_INT, 'Note ID (0 to unlink)'),
        ]);
    }

    public static function execute(int $chatid, int $noteid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'chatid' => $chatid,
            'noteid' => $noteid,
        ]);

        // Verify the chat message belongs to this user.
        $record = $DB->get_record('format_videoclass_chat_history', [
            'id'     => $params['chatid'],
            'userid' => $USER->id,
        ], '*', MUST_EXIST);

        $context = \context_course::instance($record->courseid);
        self::validate_context($context);

        // Update the noteid link.
        $DB->set_field('format_videoclass_chat_history', 'noteid', $params['noteid'], [
            'id' => $params['chatid'],
        ]);

        return ['success' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the link was updated'),
        ]);
    }
}
