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
use external_multiple_structure;
use external_single_structure;
use external_value;

/**
 * Share an AI tutor conversation with selected classmates.
 *
 * @package   format_videoclass
 */
class share_chat_conversation extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'conversationid' => new external_value(PARAM_INT, 'Conversation ID to share'),
            'recipients'     => new external_multiple_structure(
                new external_value(PARAM_INT, 'User ID of a recipient'),
                'List of recipient user IDs'
            ),
        ]);
    }

    public static function execute(int $conversationid, array $recipients): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'conversationid' => $conversationid,
            'recipients'     => $recipients,
        ]);

        $conv = $DB->get_record('format_videoclass_chat_conversations', [
            'id'     => $params['conversationid'],
            'userid' => $USER->id,
        ], '*', MUST_EXIST);

        $context = \context_course::instance($conv->courseid);
        self::validate_context($context);

        if (empty($params['recipients'])) {
            throw new \invalid_parameter_exception('At least one recipient is required.');
        }

        $now = time();
        $recipientnames = [];

        foreach ($params['recipients'] as $recipientid) {
            if ($DB->record_exists('format_videoclass_chat_conv_recipients', [
                'conversationid' => $conv->id,
                'userid'         => $recipientid,
            ])) {
                continue;
            }

            $DB->insert_record('format_videoclass_chat_conv_recipients', (object) [
                'conversationid' => $conv->id,
                'userid'         => $recipientid,
                'timeshared'     => $now,
            ]);

            $ruser = $DB->get_record('user', ['id' => $recipientid],
                'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename');
            if ($ruser) {
                $recipientnames[] = fullname($ruser);
            }
        }

        return [
            'success'        => true,
            'recipientnames' => implode(', ', $recipientnames),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'        => new external_value(PARAM_BOOL, 'Whether sharing succeeded'),
            'recipientnames' => new external_value(PARAM_TEXT, 'Comma-separated recipient names'),
        ]);
    }
}
