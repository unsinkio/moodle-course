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
 * VideoClass format settings.
 *
 * Adds configuration under:
 * Site Administration → AU Nexus → VideoClass
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // ── AU Nexus category (fallback if local_aunexus not installed) ───
    if (!$ADMIN->locate('aunexus')) {
        $ADMIN->add('root', new admin_category('aunexus', 'AU Nexus'), 'users');
    }

    // ── VideoClass sub-category ─────────────────────────────────────
    $ADMIN->add('aunexus', new admin_category(
        'aunexus_videoclass',
        get_string('pluginname', 'format_videoclass')
    ));

    // ── Settings page (inside the sub-category) ─────────────────────
    $settings = new admin_settingpage('format_videoclass_settings', get_string('pluginname', 'format_videoclass'));

    // ── AI Tutor heading ──
    $settings->add(new admin_setting_heading(
        'format_videoclass/aitutorheading',
        get_string('settings_aitutor_heading', 'format_videoclass'),
        get_string('settings_aitutor_desc', 'format_videoclass')
    ));

    // CampusMCP URL.
    $settings->add(new admin_setting_configtext(
        'format_videoclass/campusmcp_url',
        get_string('settings_campusmcp_url', 'format_videoclass'),
        get_string('settings_campusmcp_url_desc', 'format_videoclass'),
        'https://campusmcp.azurewebsites.net',
        PARAM_URL
    ));

    // CampusMCP API Key.
    $settings->add(new admin_setting_configpasswordunmask(
        'format_videoclass/campusmcp_apikey',
        get_string('settings_campusmcp_apikey', 'format_videoclass'),
        get_string('settings_campusmcp_apikey_desc', 'format_videoclass'),
        ''
    ));

    // AI Tutor System Prompt.
    $defaultprompt = 'You are an AI academic tutor for the course "{coursename}". '
        . 'The student is currently on section "{sectionname}". '
        . 'Use the following section resources as context to help the student:'
        . "\n\n{resources}\n\n"
        . 'Be helpful, concise, and reference specific resources when relevant. '
        . 'Respond in the same language the student uses.';

    $settings->add(new admin_setting_configtextarea(
        'format_videoclass/aitutor_prompt',
        get_string('settings_aitutor_prompt', 'format_videoclass'),
        get_string('settings_aitutor_prompt_desc', 'format_videoclass'),
        $defaultprompt
    ));

    $ADMIN->add('aunexus_videoclass', $settings);

    // Prevent Moodle from creating a default settings link under Course formats.
    $settings = null;
}
