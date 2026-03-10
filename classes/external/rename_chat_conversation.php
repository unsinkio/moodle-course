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
 * Rename an AI tutor conversation.
 *
 * @package   format_videoclass
 */
class rename_chat_conversation extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'conversationid' => new external_value(PARAM_INT, 'Conversation ID'),
            'title'          => new external_value(PARAM_TEXT, 'New title'),
        ]);
    }

    public static function execute(int $conversationid, string $title): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'conversationid' => $conversationid,
            'title'          => $title,
        ]);

        $conv = $DB->get_record('format_videoclass_chat_conversations', [
            'id'     => $params['conversationid'],
            'userid' => $USER->id,
        ], '*', MUST_EXIST);

        $context = \context_course::instance($conv->courseid);
        self::validate_context($context);

        $newtitle = trim($params['title']);
        if (empty($newtitle)) {
            throw new \invalid_parameter_exception('Title cannot be empty.');
        }

        $DB->set_field('format_videoclass_chat_conversations', 'title', mb_substr($newtitle, 0, 255), [
            'id' => $conv->id,
        ]);

        return ['success' => true, 'title' => mb_substr($newtitle, 0, 255)];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether rename succeeded'),
            'title'   => new external_value(PARAM_TEXT, 'Updated title'),
        ]);
    }
}
