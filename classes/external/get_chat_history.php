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
 * Get AI tutor chat history for a section.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_videoclass\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class get_chat_history extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'       => new external_value(PARAM_INT, 'Course ID'),
            'sectionid'      => new external_value(PARAM_INT, 'Section ID'),
            'conversationid' => new external_value(PARAM_INT, 'Conversation ID (0 = latest)', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $courseid, int $sectionid, int $conversationid = 0): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'       => $courseid,
            'sectionid'      => $sectionid,
            'conversationid' => $conversationid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        $convid = $params['conversationid'];

        // If no conversation specified, find the most recent one.
        if ($convid <= 0) {
            $convid = (int) $DB->get_field_select(
                'format_videoclass_chat_conversations',
                'id',
                'courseid = :courseid AND sectionid = :sectionid AND userid = :userid',
                [
                    'courseid'  => $params['courseid'],
                    'sectionid' => $params['sectionid'],
                    'userid'    => $USER->id,
                ],
                IGNORE_MULTIPLE
            );
            if (!$convid) {
                return [];
            }
        }

        // Determine the message owner: either the current user owns this conversation,
        // or they are a recipient of a shared conversation.
        $conv = $DB->get_record('format_videoclass_chat_conversations', ['id' => $convid]);
        if (!$conv) {
            return [];
        }

        $messageuser = null;
        if ((int) $conv->userid === (int) $USER->id) {
            // Current user owns this conversation.
            $messageuser = $USER->id;
        } else {
            // Check if the current user is a recipient (shared conversation).
            $isrecipient = $DB->record_exists('format_videoclass_chat_conv_recipients', [
                'conversationid' => $convid,
                'userid'         => $USER->id,
            ]);
            if ($isrecipient) {
                $messageuser = (int) $conv->userid;
            }
        }

        if (!$messageuser) {
            return []; // Not authorized to view this conversation.
        }

        $records = $DB->get_records_select(
            'format_videoclass_chat_history',
            'conversationid = :conversationid AND userid = :userid',
            [
                'conversationid' => $convid,
                'userid'         => $messageuser,
            ],
            'timecreated ASC',
            'id, role, message, noteid, timecreated'
        );

        $messages = [];
        foreach ($records as $r) {
            $messages[] = [
                'id'          => (int) $r->id,
                'role'        => $r->role,
                'message'     => $r->message,
                'noteid'      => (int) ($r->noteid ?? 0),
                'timecreated' => (int) $r->timecreated,
            ];
        }

        return $messages;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'          => new external_value(PARAM_INT, 'Message ID'),
                'role'        => new external_value(PARAM_ALPHA, 'user or assistant'),
                'message'     => new external_value(PARAM_RAW, 'Message content'),
                'noteid'      => new external_value(PARAM_INT, 'Linked note ID or 0'),
                'timecreated' => new external_value(PARAM_INT, 'Timestamp'),
            ])
        );
    }
}
