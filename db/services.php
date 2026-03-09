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
 * External services for VideoClass.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // Personal notes (My Notes tab).
    'format_videoclass_save_personal_note' => [
        'classname'   => 'format_videoclass\external\save_personal_note',
        'description' => 'Save or update a personal note for a section.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'format_videoclass_get_personal_notes' => [
        'classname'   => 'format_videoclass\external\get_personal_notes',
        'description' => 'Get personal notes for a section (with sharing info).',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'format_videoclass_delete_personal_note' => [
        'classname'   => 'format_videoclass\external\delete_personal_note',
        'description' => 'Delete a personal note (cascades recipients).',
        'type'        => 'write',
        'ajax'        => true,
    ],

    // Sharing (from My Notes tab).
    'format_videoclass_share_note' => [
        'classname'   => 'format_videoclass\external\share_note',
        'description' => 'Share a saved personal note with selected classmates.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'format_videoclass_unshare_note' => [
        'classname'   => 'format_videoclass\external\unshare_note',
        'description' => 'Remove all sharing from a note.',
        'type'        => 'write',
        'ajax'        => true,
    ],

    // Shared with me (Shared Notes tab).
    'format_videoclass_get_shared_with_me' => [
        'classname'   => 'format_videoclass\external\get_shared_with_me',
        'description' => 'Get notes shared with the current user.',
        'type'        => 'read',
        'ajax'        => true,
    ],

    // Student search for recipient picker.
    'format_videoclass_search_students' => [
        'classname'   => 'format_videoclass\external\search_students',
        'description' => 'Search enrolled students in a course.',
        'type'        => 'read',
        'ajax'        => true,
    ],

    // AI Tutor chat.
    'format_videoclass_send_chat_message' => [
        'classname'   => 'format_videoclass\external\send_chat_message',
        'description' => 'Send a message to the AI tutor and get a response.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'format_videoclass_get_chat_history' => [
        'classname'   => 'format_videoclass\external\get_chat_history',
        'description' => 'Get AI tutor chat history for a section.',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'format_videoclass_clear_chat_history' => [
        'classname'   => 'format_videoclass\external\clear_chat_history',
        'description' => 'Clear AI tutor chat history for a section.',
        'type'        => 'write',
        'ajax'        => true,
    ],
];
