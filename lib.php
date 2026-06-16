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
 * Course format class for VideoClass.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/topics/lib.php');

/**
 * VideoClass course format.
 */
class format_videoclass extends format_topics {

    /**
     * Default AI tutor system prompt.
     *
     * Used as fallback when the admin has not configured a custom prompt,
     * and as the default value for the setting in settings.php.
     */
    const DEFAULT_AITUTOR_PROMPT = <<<'EOF'
You are an AI Tutor embedded in an academic LMS environment for the course "{coursename}".
The summary of the course is: "{coursesummary}".
The student is currently on section "{sectionname}".{activitycontext}
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
"I can't provide the full solution in this context, but I can help you think through it."

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

    /**
     * VideoClass supports AJAX section moves in editing mode.
     *
     * @return bool
     */
    public function supports_ajax(): bool {
        return true;
    }

    /**
     * VideoClass uses sections.
     *
     * @return bool
     */
    public function uses_sections(): bool {
        return true;
    }

    /**
     * Return the default section name.
     *
     * @param \stdClass $section The section object.
     * @return string
     */
    public function get_default_section_name($section): string {
        if (!empty($section->name)) {
            return format_string(
                $section->name,
                true,
                ['context' => \context_course::instance($this->courseid)]
            );
        }
        if ($section->section == 0) {
            return get_string('section0name', 'format_videoclass');
        }
        return get_string('sectionname', 'format_videoclass') . ' ' . $section->section;
    }

    /**
     * Course-level format options.
     *
     * Teachers can toggle the AI Tutor on/off per course from
     * Course Settings → Course Format.
     *
     * @param bool $foreditform
     * @return array
     */
    public function course_format_options($foreditform = false): array {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            $courseformatoptions = [
                'enable_aitutor' => [
                    'default' => 1,
                    'type'    => PARAM_INT,
                ],
            ];
        }

        if ($foreditform) {
            $optionsedit = [
                'enable_aitutor' => [
                    'label' => get_string('settings_enable_aitutor', 'format_videoclass'),
                    'help'  => 'settings_enable_aitutor',
                    'help_component' => 'format_videoclass',
                    'element_type'   => 'select',
                    'element_attributes' => [
                        [
                            0 => get_string('no'),
                            1 => get_string('yes'),
                        ],
                    ],
                ],
            ];
            return array_merge_recursive($courseformatoptions, $optionsedit);
        }

        return $courseformatoptions;
    }
}
