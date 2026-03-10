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
 * Send a chat message to the AI tutor and get a response.
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

class send_chat_message extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'        => new external_value(PARAM_INT, 'Course ID'),
            'sectionid'       => new external_value(PARAM_INT, 'Section ID'),
            'message'         => new external_value(PARAM_RAW, 'User message'),
            'conversationid'  => new external_value(PARAM_INT, 'Conversation ID (0 = new)', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $courseid, int $sectionid, string $message, int $conversationid = 0): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'       => $courseid,
            'sectionid'      => $sectionid,
            'message'        => $message,
            'conversationid' => $conversationid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        $courseid       = $params['courseid'];
        $sectionid      = $params['sectionid'];
        $message        = trim($params['message']);
        $conversationid = $params['conversationid'];
        $now            = time();

        if ($message === '') {
            return ['reply' => '', 'error' => 'Empty message', 'msgid' => 0, 'conversationid' => 0];
        }

        // Create or validate conversation.
        if ($conversationid <= 0) {
            // Auto-create a new conversation with title from first 80 chars.
            $title = mb_substr($message, 0, 80);
            $conversationid = $DB->insert_record('format_videoclass_chat_conversations', (object) [
                'courseid'     => $courseid,
                'sectionid'    => $sectionid,
                'userid'       => $USER->id,
                'title'        => $title,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        } else {
            // Update timemodified on the existing conversation.
            $DB->set_field('format_videoclass_chat_conversations', 'timemodified', $now, [
                'id'     => $conversationid,
                'userid' => $USER->id,
            ]);
        }

        // 1. Save user message to history.
        $DB->insert_record('format_videoclass_chat_history', (object) [
            'courseid'       => $courseid,
            'sectionid'      => $sectionid,
            'userid'         => $USER->id,
            'conversationid' => $conversationid,
            'role'           => 'user',
            'message'        => $message,
            'timecreated'    => $now,
        ]);

        // 2. Build context from section resources.
        $resourcecontext = \format_videoclass\resource_context_builder::build($courseid, $sectionid);

        // 3. Load recent chat history (last 20 messages for this conversation).
        $history = $DB->get_records_select(
            'format_videoclass_chat_history',
            'conversationid = :conversationid AND userid = :userid',
            ['conversationid' => $conversationid, 'userid' => $USER->id],
            'timecreated ASC',
            'role, message',
            0,
            20
        );

        // 4. Build history array for CampusMCP (role + content pairs).
        $chathistory = [];
        foreach ($history as $row) {
            $chathistory[] = ['role' => $row->role, 'content' => $row->message];
        }

        // 5. Build system prompt from configurable template.
        $course = $DB->get_record('course', ['id' => $courseid], 'fullname', MUST_EXIST);
        $section = $DB->get_record('course_sections', ['id' => $sectionid], 'name, section', MUST_EXIST);
        $sectionname = !empty($section->name) ? $section->name : get_string('sectionname', 'format_videoclass') . ' ' . $section->section;

        $prompttemplate = get_config('format_videoclass', 'aitutor_prompt');
        if (empty($prompttemplate)) {
            $prompttemplate = 'You are an AI academic tutor for the course "{coursename}". '
                . 'The student is currently on section "{sectionname}". '
                . 'Use the following section resources as context to help the student:'
                . "\n\n{resources}\n\n"
                . 'Be helpful, concise, and reference specific resources when relevant. '
                . 'Respond in the same language the student uses.';
        }

        $systemprompt = str_replace(
            ['{coursename}', '{sectionname}', '{resources}'],
            [$course->fullname, $sectionname, $resourcecontext],
            $prompttemplate
        );

        // 6. Call CampusMCP.
        $reply = self::call_campusmcp($systemprompt, $message, $chathistory, $USER->email, fullname($USER));

        // 7. Save assistant response to history.
        $assistantid = $DB->insert_record('format_videoclass_chat_history', (object) [
            'courseid'       => $courseid,
            'sectionid'      => $sectionid,
            'userid'         => $USER->id,
            'conversationid' => $conversationid,
            'role'           => 'assistant',
            'message'        => $reply,
            'timecreated'    => time(),
        ]);

        return [
            'reply'          => $reply,
            'error'          => '',
            'msgid'          => (int) $assistantid,
            'conversationid' => (int) $conversationid,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'reply'          => new external_value(PARAM_RAW, 'Assistant reply'),
            'error'          => new external_value(PARAM_RAW, 'Error message if any'),
            'msgid'          => new external_value(PARAM_INT, 'Assistant message DB ID', VALUE_OPTIONAL),
            'conversationid' => new external_value(PARAM_INT, 'Conversation ID', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Call CampusMCP /chat endpoint.
     *
     * Matches CampusMCP's ChatRequest:
     *   email, message, student_name, lang, history[], system_prompt (optional override)
     */
    private static function call_campusmcp(
        string $systemprompt,
        string $usermessage,
        array $history,
        string $email,
        string $studentname
    ): string {
        // CampusMCP endpoint — configurable via plugin settings.
        $endpoint = get_config('format_videoclass', 'campusmcp_url');
        if (empty($endpoint)) {
            $endpoint = 'https://campusmcp.azurewebsites.net';
        }

        $apikey = get_config('format_videoclass', 'campusmcp_apikey');
        if (empty($apikey)) {
            $apikey = '';
        }

        $payload = json_encode([
            'email'         => $email,
            'message'       => $usermessage,
            'student_name'  => $studentname,
            'lang'          => current_language(),
            'history'       => $history,
            'system_prompt' => $systemprompt,
        ]);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if (!empty($apikey)) {
            $headers[] = 'Authorization: Bearer ' . $apikey;
        }

        $ch = curl_init($endpoint . '/chat');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[VC AI Tutor] cURL error: {$error}");
            return "I'm sorry, I'm having trouble connecting right now. Please try again later.";
        }

        if ($httpcode !== 200) {
            error_log("[VC AI Tutor] HTTP {$httpcode}: {$response}");
            return "I'm sorry, something went wrong (HTTP {$httpcode}). Please try again later.";
        }

        $data = json_decode($response, true);
        if (!$data) {
            error_log("[VC AI Tutor] Invalid JSON response: {$response}");
            return "I'm sorry, I received an unexpected response. Please try again.";
        }

        // CampusMCP returns ChatResponse: {success, response, tool_calls}
        if (isset($data['response'])) {
            return $data['response'];
        }

        error_log("[VC AI Tutor] Unexpected response shape: " . json_encode($data));
        return "I received a response but couldn't parse it. Please try again.";
    }
}

