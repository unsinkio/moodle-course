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
            'courseid'  => new external_value(PARAM_INT, 'Course ID'),
            'sectionid' => new external_value(PARAM_INT, 'Section ID'),
            'message'   => new external_value(PARAM_RAW, 'User message'),
        ]);
    }

    public static function execute(int $courseid, int $sectionid, string $message): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'  => $courseid,
            'sectionid' => $sectionid,
            'message'   => $message,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        $courseid  = $params['courseid'];
        $sectionid = $params['sectionid'];
        $message   = trim($params['message']);
        $now       = time();

        if ($message === '') {
            return ['reply' => '', 'error' => 'Empty message'];
        }

        // 1. Save user message to history.
        $DB->insert_record('format_videoclass_chat_history', (object) [
            'courseid'    => $courseid,
            'sectionid'   => $sectionid,
            'userid'      => $USER->id,
            'role'        => 'user',
            'message'     => $message,
            'timecreated' => $now,
        ]);

        // 2. Build context from section resources.
        $resourcecontext = \format_videoclass\resource_context_builder::build($courseid, $sectionid);

        // 3. Load recent chat history (last 20 messages).
        $history = $DB->get_records_select(
            'format_videoclass_chat_history',
            'courseid = :courseid AND sectionid = :sectionid AND userid = :userid',
            ['courseid' => $courseid, 'sectionid' => $sectionid, 'userid' => $USER->id],
            'timecreated ASC',
            'role, message',
            0,
            20
        );

        // 4. Build messages array for API.
        $messages = [];
        foreach ($history as $row) {
            $messages[] = ['role' => $row->role, 'content' => $row->message];
        }

        // 5. Build system prompt.
        $course = $DB->get_record('course', ['id' => $courseid], 'fullname', MUST_EXIST);
        $section = $DB->get_record('course_sections', ['id' => $sectionid], 'name, section', MUST_EXIST);
        $sectionname = !empty($section->name) ? $section->name : get_string('sectionname', 'format_videoclass') . ' ' . $section->section;

        $systemprompt = "You are an AI academic tutor for the course \"{$course->fullname}\". "
            . "The student is currently on section \"{$sectionname}\". "
            . "Use the following section resources as context to help the student:\n\n"
            . $resourcecontext
            . "\n\nBe helpful, concise, and reference specific resources when relevant. "
            . "Respond in the same language the student uses.";

        // 6. Call CampusMCP.
        $reply = self::call_campusmcp($systemprompt, $messages);

        // 7. Save assistant response to history.
        $DB->insert_record('format_videoclass_chat_history', (object) [
            'courseid'    => $courseid,
            'sectionid'   => $sectionid,
            'userid'      => $USER->id,
            'role'        => 'assistant',
            'message'     => $reply,
            'timecreated' => time(),
        ]);

        return ['reply' => $reply, 'error' => ''];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'reply' => new external_value(PARAM_RAW, 'Assistant reply'),
            'error' => new external_value(PARAM_RAW, 'Error message if any'),
        ]);
    }

    /**
     * Call CampusMCP chat endpoint.
     */
    private static function call_campusmcp(string $systemprompt, array $messages): string {
        // CampusMCP endpoint — configurable via plugin settings.
        $endpoint = get_config('format_videoclass', 'campusmcp_url');
        if (empty($endpoint)) {
            $endpoint = 'https://campusmcp.azurewebsites.net';
        }

        $payload = json_encode([
            'system_prompt' => $systemprompt,
            'messages'      => $messages,
        ]);

        $ch = curl_init($endpoint . '/chat');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
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
            return "I'm sorry, something went wrong. Please try again later.";
        }

        $data = json_decode($response, true);
        if (!$data) {
            error_log("[VC AI Tutor] Invalid JSON response: {$response}");
            return "I'm sorry, I received an unexpected response. Please try again.";
        }

        // CampusMCP may return different response shapes — handle common ones.
        if (isset($data['response'])) {
            return $data['response'];
        }
        if (isset($data['reply'])) {
            return $data['reply'];
        }
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }
        if (isset($data['message'])) {
            return $data['message'];
        }

        error_log("[VC AI Tutor] Unexpected response shape: " . json_encode($data));
        return "I received a response but couldn't parse it. Please try again.";
    }
}
