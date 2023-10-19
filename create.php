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

require_once(__DIR__ . '/../../config.php');

require_login();

if (isguestuser()) {
    redirect(new moodle_url($CFG->wwwroot),
            get_string('error:noguestaccess', 'local_eportfolio'),
            null, \core\output\notification::NOTIFY_ERROR);
}

$url = new moodle_url('/local/eportfolio/create.php');
$library = optional_param('library', null, PARAM_TEXT);

$context = context_user::instance($USER->id);

// Set page layout.
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('create:header', 'local_eportfolio'));
$PAGE->set_heading(get_string('create:header', 'local_eportfolio'));
$PAGE->set_pagelayout('base');
$PAGE->add_body_class('limitedwith');
$PAGE->set_pagetype('user-files');

$redirecturl = new moodle_url('/local/eportfolio/index.php');
if (empty($library)) {
    echo $OUTPUT->header();
    echo $OUTPUT->box_start('generalbox');

    $libraries = \local_eportfolio\forms\contentselect::get_contenttype_types();
    $renderparams = [
            'libraries' => $libraries,
            'baseurl' => $url->out(false),
            'backurl' => $redirecturl->out(false),
    ];
    echo $OUTPUT->render_from_template('local_eportfolio/eportfolio_create_select', $renderparams);

    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();

} else {

    $mform = new \local_eportfolio\forms\createform($url, ['library' => $library, 'contextid' => $context->id]);

    if ($mform->is_cancelled()) {
        $url->remove_all_params();
        redirect($url);

    } else if ($fromform = $mform->get_data()) {
        $fileid = $mform->save_content($fromform);

        // Trigger event for creating ePortfolio.
        \local_eportfolio\event\eportfolio_created::create([
                'other' => [
                        'description' => get_string('event:eportfolio:created', 'local_eportfolio',
                                array('userid' => $USER->id, 'filename' => '', 'itemid' => $fileid)),
                ],
        ])->trigger();

        redirect(
                $redirecturl,
                get_string('create:success', 'local_eportfolio'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
        );

    } else {
        echo $OUTPUT->header();

        $mform->display();

        echo $OUTPUT->footer();

    }
}
