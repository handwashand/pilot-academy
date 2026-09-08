<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time content load: documentation links for the Pilot Monitoring
     * course lessons (doc.pilot-gps.com, English user guide v7.9).
     *
     * A data migration (not a seeder) so production picks it up through the
     * regular deploy (`php artisan migrate --force`) — there is no SSH step
     * to run seeders. Only fills lessons whose doc_links is still empty, so
     * links already edited in the admin are never overwritten.
     */
    public function up(): void
    {
        $linksBySlug = [
            'creating-a-client-contract' => [
                ['title' => 'Account', 'url' => 'https://doc.pilot-gps.com/account.html'],
                ['title' => 'Account menu', 'url' => 'https://doc.pilot-gps.com/account_menu.html'],
                ['title' => 'Modules', 'url' => 'https://doc.pilot-gps.com/modules_2.html'],
                ['title' => 'Admin panel', 'url' => 'https://doc.pilot-gps.com/admin_panel_.html'],
            ],
            'configuring-users' => [
                ['title' => 'Staff and groups', 'url' => 'https://doc.pilot-gps.com/staff_and_groups.html'],
                ['title' => 'Roles and access rights', 'url' => 'https://doc.pilot-gps.com/roles_and_access_rights.html'],
                ['title' => 'Login history', 'url' => 'https://doc.pilot-gps.com/login_history.html'],
                ['title' => 'Reports list', 'url' => 'https://doc.pilot-gps.com/reports_list.html'],
            ],
            'adding-the-first-object' => [
                ['title' => 'How to add an object', 'url' => 'https://doc.pilot-gps.com/how_to_add_an_object.html'],
                ['title' => 'Adding an object', 'url' => 'https://doc.pilot-gps.com/adding_an_object.html'],
                ['title' => 'Object card', 'url' => 'https://doc.pilot-gps.com/object_card.html'],
                ['title' => 'Supported equipment', 'url' => 'https://doc.pilot-gps.com/supported_equipment.html'],
            ],
            'understanding-sensors' => [
                ['title' => 'Sensors', 'url' => 'https://doc.pilot-gps.com/sensors_1.html'],
                ['title' => 'Sensor types', 'url' => 'https://doc.pilot-gps.com/sensor_types.html'],
                ['title' => 'Glossary', 'url' => 'https://doc.pilot-gps.com/glossary.html'],
            ],
            'adding-an-ignition-sensor' => [
                ['title' => 'How to add a sensor', 'url' => 'https://doc.pilot-gps.com/how_to_add_a_sensor_2.html'],
                ['title' => 'How to add a sensor (quick start)', 'url' => 'https://doc.pilot-gps.com/how_to_add_a_sensor_1.html'],
                ['title' => 'How to work with a list of sensors', 'url' => 'https://doc.pilot-gps.com/how_to_work_with_a_list_of_sensors.html'],
                ['title' => 'Sensor templates', 'url' => 'https://doc.pilot-gps.com/sensor_templates.html'],
            ],
            'main-workspace' => [
                ['title' => 'User account interface', 'url' => 'https://doc.pilot-gps.com/user_account_interface.html'],
                ['title' => 'Interface overview', 'url' => 'https://doc.pilot-gps.com/interface_overview_1.html'],
                ['title' => 'Workspace', 'url' => 'https://doc.pilot-gps.com/workspace.html'],
                ['title' => 'Map', 'url' => 'https://doc.pilot-gps.com/map.html'],
                ['title' => 'Object menu', 'url' => 'https://doc.pilot-gps.com/object_menu.html'],
                ['title' => 'Object tags', 'url' => 'https://doc.pilot-gps.com/object_tags.html'],
            ],
            'working-with-history' => [
                ['title' => 'Viewing history', 'url' => 'https://doc.pilot-gps.com/viewing_history.html'],
                ['title' => 'Working with history', 'url' => 'https://doc.pilot-gps.com/working_with_history.html'],
                ['title' => 'How to view history (quick start)', 'url' => 'https://doc.pilot-gps.com/how_to_view_history_1.html'],
            ],
            'building-reports' => [
                ['title' => 'How to build a report', 'url' => 'https://doc.pilot-gps.com/how_to_build_a_report.html'],
                ['title' => 'How to work with a report', 'url' => 'https://doc.pilot-gps.com/how_to_work_with_a_report.html'],
                ['title' => 'Report types', 'url' => 'https://doc.pilot-gps.com/report_types.html'],
                ['title' => 'Report scheduler', 'url' => 'https://doc.pilot-gps.com/report_scheduler.html'],
                ['title' => 'Setting up access to reports', 'url' => 'https://doc.pilot-gps.com/setting_up_access_to_reports.html'],
            ],
        ];

        foreach ($linksBySlug as $slug => $links) {
            DB::table('lessons')
                ->where('slug', $slug)
                ->whereNull('doc_links')
                ->update(['doc_links' => json_encode($links)]);
        }
    }

    /**
     * Content load — nothing sensible to reverse (admin may have edited the
     * links since). Intentionally a no-op.
     */
    public function down(): void
    {
        //
    }
};
