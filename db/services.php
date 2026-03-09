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
    'format_videoclass_share_note' => [
        'classname'   => 'format_videoclass\external\share_note',
        'description' => 'Share a note with classmates in a course section.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => '',
    ],
    'format_videoclass_get_shared_notes' => [
        'classname'   => 'format_videoclass\external\get_shared_notes',
        'description' => 'Get shared notes for a course section.',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => '',
    ],
    'format_videoclass_delete_note' => [
        'classname'   => 'format_videoclass\external\delete_note',
        'description' => 'Delete a shared note.',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => '',
    ],
];
