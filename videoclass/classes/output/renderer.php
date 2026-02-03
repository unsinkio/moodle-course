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

namespace format_videoclass\output;

use core_courseformat\output\section_renderer;
use moodle_page;

/**
 * Renderer for the VideoClass course format.
 *
 * @package   format_videoclass
 * @copyright 2026 Atlantis University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {
    /**
     * Constructor.
     *
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Prefer local templates for course format renderables.
     *
     * @param \renderable $widget
     * @return string
     */
    public function render(\renderable $widget) {
        global $CFG;

        $fullpath = str_replace('\\', '/', get_class($widget));

        if ($widget instanceof \templatable) {
            $corepath = 'core_courseformat\/output\/local';
            $pluginpath = 'format_videoclass\/output\/courseformat';
            $specialrenderers = '/^(?<componentpath>' . $corepath . '|' . $pluginpath . ')\/(?<template>.+)$/' ;
            $matches = null;

            if (
                preg_match($specialrenderers, $fullpath, $matches)
                && file_exists($CFG->dirroot . '/course/format/videoclass/templates/local/' . $matches['template'])
            ) {
                $data = $widget->export_for_template($this);
                return $this->render_from_template('format_videoclass/local/' . $matches['template'], $data);
            }
        }

        return parent::render($widget);
    }
}
