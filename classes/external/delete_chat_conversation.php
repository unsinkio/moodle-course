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
 * Delete an AI tutor conversation and all its messages.
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

class delete_chat_conversation extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'conversationid' => new external_value(PARAM_INT, 'Conversation ID'),
        ]);
    }

    public static function execute(int $conversationid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'conversationid' => $conversationid,
        ]);

        $convid = $params['conversationid'];

        // Verify ownership.
        $conversation = $DB->get_record('format_videoclass_chat_conversations', [
            'id'     => $convid,
            'userid' => $USER->id,
        ]);

        if (!$conversation) {
            return ['success' => false];
        }

        $context = \context_course::instance($conversation->courseid);
        self::validate_context($context);

        // Delete all messages in the conversation.
        $DB->delete_records('format_videoclass_chat_history', [
            'conversationid' => $convid,
            'userid'         => $USER->id,
        ]);

        // Delete the conversation record.
        $DB->delete_records('format_videoclass_chat_conversations', [
            'id'     => $convid,
            'userid' => $USER->id,
        ]);

        return ['success' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether conversation was deleted'),
        ]);
    }
}
