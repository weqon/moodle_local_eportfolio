<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade steps are defined here.
 *
 * @package     local_eportfolio
 * @category    upgrade
 * @copyright   2023 weQon UG <info@weqon.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute local_eportfolio upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_eportfolio_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    if ($oldversion < 2023072100) {

        // Define table local_eportfolio_share to be created.
        $table = new xmldb_table('local_eportfolio_share');

        // Adding fields to table local_eportfolio_share.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fileitemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shareoption', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_eportfolio_share.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_eportfolio_share.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eportfolio savepoint reached.
        upgrade_plugin_savepoint(true, 2023072100, 'local', 'eportfolio');
    }

    if ($oldversion < 2023072400) {

        // Define field id to be dropped from local_eportfolio_share.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('cmid');

        // Conditionally launch drop field id.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field fullcourse to be added to local_eportfolio_share.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('fullcourse', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'shareoption');

        // Conditionally launch add field fullcourse.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field roles to be added to local_eportfolio_share.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('roles', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'fullcourse');

        // Conditionally launch add field roles.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field enrolled to be added to local_eportfolio_share.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('enrolled', XMLDB_TYPE_TEXT, null, null, null, null, null, 'roles');

        // Conditionally launch add field enrolled.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field groups to be added to local_eportfolio_share.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('groups', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'enrolled');

        // Conditionally launch add field groups.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eportfolio savepoint reached.
        upgrade_plugin_savepoint(true, 2023072400, 'local', 'eportfolio');
    }

    if ($oldversion < 2023091100) {

        // Define field cmid to be added to local_eportfolio_share.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'groups');

        // Conditionally launch add field cmid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eportfolio savepoint reached.
        upgrade_plugin_savepoint(true, 2023091100, 'local', 'eportfolio');
    }

    if ($oldversion < 2023091201) {

        // Define field fileidcontext to be added to local_eportfolio_share.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('fileidcontext', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'fileitemid');

        // Conditionally launch add field fileidcontext.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eportfolio savepoint reached.
        upgrade_plugin_savepoint(true, 2023091201, 'local', 'eportfolio');
    }

    if ($oldversion < 2023091202) {

        // Define field h5pid to be added to local_eportfolio_share.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('h5pid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'fileidcontext');

        // Conditionally launch add field h5pid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Get the h5p id for shared ePortfolios to fill the new field h5pid for existing entries.
        $eportfolios = $DB->get_records('local_eportfolio_share');

        foreach ($eportfolios as $eport) {

            $sql = "SELECT h.id FROM {files} AS f
                            JOIN {h5p} AS h
                            ON f.pathnamehash = h.pathnamehash
                            WHERE f.id = :fileid";

            $params = array(
                    'fileid' => $eport->fileidcontext,
            );

            $h5pfile = $DB->get_record_sql($sql, $params);

            if ($h5pfile) {

                $data = new stdClass();

                $data->id = $eport->id;
                $data->h5pid = $h5pfile->id;

                $DB->update_record('local_eportfolio_share', $data);

            }

        }

        // Add required capabilities to student role.
        $cap = 'moodle/h5p:deploy'; // Required for students to create and share H5P files.
        $roleid = '5'; // Default role id for student.
        $contextid = context_system::instance()->id;

        // Finally add additional capabilities to the student role.
        assign_capability($cap, CAP_ALLOW, $roleid, $contextid);

        // Eportfolio savepoint reached.
        upgrade_plugin_savepoint(true, 2023091202, 'local', 'eportfolio');

    }

    if ($oldversion < 2023092800) {

        // Rename field groups on table local_eportfolio_share to coursegroups.
        // "Groups" might be a reserved word depending on server configuration.
        $table = new xmldb_table('local_eportfolio_share');
        $field = new xmldb_field('groups', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'enrolled');

        // Launch rename field coursegroups.
        $dbman->rename_field($table, $field, 'coursegroups');

        // Eportfolio savepoint reached.
        upgrade_plugin_savepoint(true, 2023092800, 'local', 'eportfolio');
    }

    return true;
}
