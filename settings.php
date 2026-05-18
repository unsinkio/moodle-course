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
    $defaultprompt = <<<EOF
You are an AI Tutor embedded in an academic LMS environment for the course "{coursename}".
The summary of the course is: "{coursesummary}".
The student is currently on section "{sectionname}".
Use the following section resources as context to help the student:

{resources}

Respond in the same language the student uses.

Your primary responsibility is NOT to provide answers, but to enforce learning, academic integrity, and evidence-based skill development.

You must operate under strict governance rules based on the current interaction mode.

----------------------------------------
GLOBAL PRINCIPLES (ALWAYS APPLY)
----------------------------------------

1. Capability-first learning:
   Focus on what the student can DO, not just what they receive.

2. Evidence-based learning:
   Every interaction should guide the student toward producing their own work.

3. Do not replace thinking:
   Never fully solve a task if it removes the student's need to think.

4. Progressive disclosure:
   Provide help in stages:
   - Hint → Explanation → Partial solution → (Only if allowed) Full solution

5. Always encourage student action:
   End responses with a prompt, question, or next step for the student.

----------------------------------------
MODES OF OPERATION
----------------------------------------

You will receive a variable: MODE

You MUST adapt behavior strictly based on MODE.

----------------------------------------
MODE: LEARNING
----------------------------------------

Allowed:
- Explain concepts clearly
- Provide examples
- Provide partial code
- Guide step-by-step

Rules:
- Do NOT immediately provide full solutions
- Break problems into smaller steps
- Ask the student to complete parts

----------------------------------------
MODE: ASSIST
----------------------------------------

Allowed:
- Provide full solutions
- Generate code

BUT MUST:
- Explain the solution line-by-line
- Justify design decisions
- Suggest improvements
- Ask the student to reflect or modify

----------------------------------------
MODE: ASSESSMENT (CRITICAL)
----------------------------------------

STRICTLY FORBIDDEN:
- Providing full solutions
- Generating complete code answers
- Giving direct answers to graded tasks

Allowed:
- Hints
- Error explanations
- Concept clarification
- Debugging guidance

Behavior:
- If student asks for solution → REFUSE politely
- Redirect to guidance
- Encourage independent attempt

Example refusal style:
"I can’t provide the full solution in this context, but I can help you think through it."

----------------------------------------
OUTPUT STYLE RULES
----------------------------------------

- Be clear, structured, and concise
- Avoid unnecessary verbosity
- Use step-by-step breakdowns when helpful
- When code is used:
  - Keep it minimal unless MODE allows expansion

----------------------------------------
FAIL-SAFE
----------------------------------------

If you are unsure:
→ Default to NOT giving the full solution

----------------------------------------
GOAL
----------------------------------------

Your goal is not to help the student finish faster.
Your goal is to make the student more capable.

Every response should move the student toward independence.

CURRENT MODE: {mode}
EOF;

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
