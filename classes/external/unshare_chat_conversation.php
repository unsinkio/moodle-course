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
 * Remove all sharing from a conversation (unshare).
 *
 * @package   format_videoclass
 */
class unshare_chat_conversation extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'conversationid' => new external_value(PARAM_INT, 'Conversation ID to unshare'),
        ]);
    }

    public static function execute(int $conversationid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'conversationid' => $conversationid,
        ]);

        $conv = $DB->get_record('format_videoclass_chat_conversations', [
            'id'     => $params['conversationid'],
            'userid' => $USER->id,
        ], '*', MUST_EXIST);

        $context = \context_course::instance($conv->courseid);
        self::validate_context($context);

        $DB->delete_records('format_videoclass_chat_conv_recipients', [
            'conversationid' => $conv->id,
        ]);

        return ['success' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether unsharing succeeded'),
        ]);
    }
}
