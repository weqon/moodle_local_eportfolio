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
 * Overview page for eportfolio
 *
 * @package local_eportfolio
 * @category overview
 * @copyright 2023 weQon UG {@link https://weqon.net}
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// First check, if user is logged in before accessing this page.
require_login();

$url = new moodle_url('/local/eportfolio/index.php');
$overviewurl = new moodle_url('/local/eportfolio/index.php');
$context = context_user::instance($USER->id);

// Set page layout.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('helpfaq:header', 'local_eportfolio'));
$PAGE->set_heading(get_string('helpfaq:header', 'local_eportfolio'));
$PAGE->set_pagelayout('base');
$PAGE->add_body_class('limitedwith');
$PAGE->set_pagetype('user-files');

// Print the header.
echo $OUTPUT->header();

echo '<a href="' . $overviewurl . '" class="btn btn-primary mt-3"><i class="fa fa-arrow-circle-left"></i> Zurück zur Übersicht</a>';

echo '<div class="alert alert-info mt-3">';
echo '<p>In Kürze ist hier die Hilfe und der FAQ-Bereich verfügbar!</p>';
echo '</div>';

echo $OUTPUT->footer();
