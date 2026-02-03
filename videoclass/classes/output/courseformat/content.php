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
 * Content output class for VideoClass.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_videoclass\output\courseformat;

use core_courseformat\output\local\content as content_base;
use core_courseformat\base as course_format;
use course_modinfo;

class content extends content_base {
    /**
     * Returns the output class template path.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_videoclass/local/content';
    }

    /**
     * Export data for template.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $format = $this->format;
        $course = $format->get_course();
        $context = \context_course::instance($course->id);

        $sections = $this->export_sections($output);

        $announcement = '';
        if (!empty($course->summary)) {
            $announcement = format_text($course->summary, $course->summaryformat, ['context' => $context]);
        }

        $data = (object)[
            'title' => $format->page_title(),
            'sections' => $sections,
            'format' => $format->get_format(),
            'sectionclasses' => '',
            'announcement' => $announcement,
        ];

        $data->singlesection = array_shift($data->sections);

        if ($format->show_editor()) {
            $bulkedittools = new $this->bulkedittoolsclass($format);
            $data->bulkedittools = $bulkedittools->export_for_template($output);
        }

        return $data;
    }

    /**
     * Export sections array data.
     *
     * @param \renderer_base $output
     * @return array
     */
    protected function export_sections(\renderer_base $output): array {
        $format = $this->format;
        $modinfo = $this->format->get_modinfo();

        $sections = [];
        foreach ($this->get_sections_to_display($modinfo) as $thissection) {
            if (!$thissection) {
                throw new \moodle_exception('unknowncoursesection', 'error', course_get_url($format->get_course()));
            }

            if (!$format->is_section_visible($thissection)) {
                continue;
            }

            $section = new $this->sectionclass($format, $thissection);
            $sections[] = $section->export_for_template($output);
        }

        return $sections;
    }

    /**
     * Return an array of sections to display.
     *
     * @param course_modinfo $modinfo
     * @return \section_info[]
     */
    protected function get_sections_to_display(course_modinfo $modinfo): array {
        $sections = [];
        $singlesection = $this->format->get_sectionnum();
        $sections[] = $modinfo->get_section_info($singlesection);
        return $sections;
    }
}
