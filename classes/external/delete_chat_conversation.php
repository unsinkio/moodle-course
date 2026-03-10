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
 * Delete an AI tutor conversation.
 *
 * Supports "delete for me" vs "delete for everyone" for shared conversations.
 *   - Owner + deleteforall=true  → full delete (messages, recipients, conversation).
 *   - Owner + deleteforall=false → unshare only (remove sharing, keep conversation).
 *   - Recipient + deleteforall=false → remove only this recipient's record.
 *   - Recipient + deleteforall=true  → full delete for everyone.
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
            'deleteforall'   => new external_value(PARAM_BOOL, 'True = delete for everyone, false = delete for me only', VALUE_DEFAULT, true),
        ]);
    }

    public static function execute(int $conversationid, bool $deleteforall = true): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'conversationid' => $conversationid,
            'deleteforall'   => $deleteforall,
        ]);

        $convid       = $params['conversationid'];
        $deleteforall = $params['deleteforall'];

        // Load conversation (without restricting by userid).
        $conversation = $DB->get_record('format_videoclass_chat_conversations', ['id' => $convid]);
        if (!$conversation) {
            return ['success' => false];
        }

        $context = \context_course::instance($conversation->courseid);
        self::validate_context($context);

        $isowner = ((int) $conversation->userid === (int) $USER->id);
        $isrecipient = $DB->record_exists('format_videoclass_chat_conv_recipients', [
            'conversationid' => $convid,
            'userid'         => $USER->id,
        ]);

        // Must be owner or recipient to perform any action.
        if (!$isowner && !$isrecipient) {
            return ['success' => false];
        }

        if ($deleteforall) {
            // "Delete for everyone": soft-delete conversation, mark recipients.
            // Delete messages (content gone).
            $DB->delete_records('format_videoclass_chat_history', [
                'conversationid' => $convid,
                'userid'         => $conversation->userid,
            ]);
            // Mark conversation as deleted (keep record for recipient JOINs).
            $DB->set_field('format_videoclass_chat_conversations', 'deleted', 1, ['id' => $convid]);
            // Mark all recipients as deleted (they'll see a "deleted" notice).
            $DB->set_field('format_videoclass_chat_conv_recipients', 'deleted', 1, [
                'conversationid' => $convid,
            ]);
        } else {
            // "Delete for me" only.
            if ($isowner) {
                // Owner: just unshare (remove all recipient records).
                $DB->delete_records('format_videoclass_chat_conv_recipients', [
                    'conversationid' => $convid,
                ]);
            } else {
                // Recipient: remove only their own recipient record.
                $DB->delete_records('format_videoclass_chat_conv_recipients', [
                    'conversationid' => $convid,
                    'userid'         => $USER->id,
                ]);
                // If no more recipients and conversation is soft-deleted, hard-delete it.
                $remaining = $DB->count_records('format_videoclass_chat_conv_recipients', [
                    'conversationid' => $convid,
                ]);
                if ($remaining == 0 && (int) $conversation->deleted === 1) {
                    $DB->delete_records('format_videoclass_chat_conversations', ['id' => $convid]);
                }
            }
        }

        return ['success' => true];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation succeeded'),
        ]);
    }
}
