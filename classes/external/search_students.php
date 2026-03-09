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
 * Search enrolled students in a course for the recipient picker.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_students extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'query'    => new external_value(PARAM_TEXT, 'Search query'),
        ]);
    }

    public static function execute(int $courseid, string $query): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'query'    => $query,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);

        if (!is_enrolled($context, $USER, '', true)) {
            throw new \moodle_exception('notenrolled', 'error');
        }

        $query = trim($params['query']);
        if (strlen($query) < 2) {
            return [];
        }

        // Get enrolled users (excluding self).
        $enrolled = get_enrolled_users($context, '', 0, 'u.*', 'u.lastname, u.firstname', 0, 0, true);

        $results = [];
        $search = \core_text::strtolower($query);

        foreach ($enrolled as $user) {
            if ((int) $user->id === (int) $USER->id) {
                continue;
            }

            $name = fullname($user);
            if (strpos(\core_text::strtolower($name), $search) !== false ||
                strpos(\core_text::strtolower($user->email), $search) !== false) {
                $results[] = [
                    'id'       => (int) $user->id,
                    'fullname' => $name,
                ];
                if (count($results) >= 10) {
                    break;
                }
            }
        }

        return $results;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'       => new external_value(PARAM_INT, 'User ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Full name'),
            ])
        );
    }
}
