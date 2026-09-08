<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Database\Seeder;

class PilotMonitoringCourseSeeder extends Seeder
{
    public function run(): void
    {
        // Replace the old demo course if it is present.
        Course::where('slug', 'pilot-quick-start')->delete();

        $course = Course::firstOrCreate(
            ['slug' => 'pilot-monitoring'],
            [
                'title' => 'Pilot Monitoring: Setup & Daily Use',
                'description' => 'Onboard clients on the Pilot monitoring platform: create contracts, users, objects and sensors, then work day-to-day with the map, history and reports.',
                'level' => 'beginner',
                'audience' => 'all',
                'status' => Course::STATUS_PUBLISHED,
                'sort_order' => 1,
            ],
        );

        // Already seeded — do not touch existing lessons (preserves uploaded videos).
        if ($course->lessons()->exists()) {
            return;
        }

        foreach ($this->lessons() as $i => $data) {
            $lesson = $course->lessons()->create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'content' => $data['content'],
                'status' => Lesson::STATUS_PUBLISHED,
                'sort_order' => $i + 1,
            ]);

            foreach ($data['questions'] as $qi => [$prompt, $options, $correct]) {
                $question = $lesson->questions()->create([
                    'prompt' => $prompt,
                    'sort_order' => $qi + 1,
                ]);

                foreach ($options as $oi => $text) {
                    $question->options()->create([
                        'text' => $text,
                        'is_correct' => $oi === $correct,
                        'sort_order' => $oi + 1,
                    ]);
                }
            }
        }

        // Compose a ready-to-use final quiz: enabled, 80% pass mark, and every
        // lesson question in the bank (admins can curate it later).
        $course->update(['final_quiz_enabled' => true, 'pass_percent' => 80]);
        $questionIds = Question::whereIn('lesson_id', $course->lessons()->pluck('id'))->pluck('id');
        $course->finalQuestions()->sync($questionIds->all());
    }

    private function lessons(): array
    {
        return [
            [
                'title' => 'Creating a Client Contract',
                'slug' => 'creating-a-client-contract',
                'summary' => 'Create a new client contract and set its tariff, account type and modules.',
                'content' => '<h3>Getting started</h3>'
                    .'<p>Open the link you were given and log in with your username and password.</p>'
                    .'<h3>Creating the contract</h3>'
                    .'<ul>'
                    .'<li>Go to the <strong>Account</strong> tab and click <strong>Add New</strong>, then fill in all required fields.</li>'
                    .'<li><strong>Partner:</strong> specify the current partner.</li>'
                    .'<li><strong>Tariff:</strong> choose the option matching the object price for your client.</li>'
                    .'<li><strong>Organization Type:</strong> specify the form of employment.</li>'
                    .'<li><strong>Account Type:</strong> choose one of three options.</li>'
                    .'</ul>'
                    .'<h3>Account types</h3>'
                    .'<ul>'
                    .'<li><strong>Postpaid</strong> — payment after services are provided.</li>'
                    .'<li><strong>Prepaid</strong> — payment before services are provided.</li>'
                    .'<li><strong>Postpaid Lite</strong> — adds a field where you specify the amount blocked on the account.</li>'
                    .'</ul>'
                    .'<p>Leave the <strong>Active</strong> checkbox unchanged. Open the <strong>Modules</strong> tab in Settings and enable the modules your client needs, then click <strong>Save</strong>. You have created your first contract.</p>'
                    .'<h3>After creation</h3>'
                    .'<p>Double-click the contract to view its details. A list of users appears — the first user is created automatically when the contract is created. Double-click that user to open the Transport Monitoring Pilot workspace.</p>',
                'questions' => [
                    ['In which tab do you create a new client contract?', ['Modules', 'Account', 'Objects'], 1],
                    ['Which account type adds a field for the amount blocked on the account?', ['Prepaid', 'Postpaid', 'Postpaid Lite'], 2],
                    ['How is the first user created for a new contract?', ['Automatically when the contract is created', 'Manually on the Modules tab', 'By technical support'], 0],
                ],
            ],
            [
                'title' => 'Configuring Users in the Client Cabinet',
                'slug' => 'configuring-users',
                'summary' => 'Manage the auto-created admin, adjust permissions, and add a second user.',
                'content' => '<h3>Opening user settings</h3>'
                    .'<p>Click the configuration button and select <strong>Personnel and Groups</strong>. You will see a user acting as administrator — this account was registered automatically when the contract was created. Double-click it to open its settings.</p>'
                    .'<h3>Editing the existing user</h3>'
                    .'<ul>'
                    .'<li>The login cannot be changed, but the password can.</li>'
                    .'<li>To limit access rights, downgrade the administrator to a standard user, then open <strong>Permissions</strong> to restrict or grant items.</li>'
                    .'<li>Open the <strong>Objects</strong> tab and mark the objects available to this user, then click <strong>Save</strong>.</li>'
                    .'</ul>'
                    .'<h3>Adding a new user</h3>'
                    .'<ul>'
                    .'<li>Click <strong>Add New</strong> and fill in all required fields.</li>'
                    .'<li>On the <strong>Objects</strong> tab, select the objects for this user.</li>'
                    .'<li>On the <strong>Permissions</strong> tab, restrict or grant access, then click <strong>Save</strong>.</li>'
                    .'</ul>'
                    .'<p>Your account now lists two users. From here you can also view authorization history, filter by IP address, set a personal list of available reports, and delete unneeded users.</p>',
                'questions' => [
                    ["To reduce the administrator's access, what must you do first?", ['Downgrade the administrator to a standard user', 'Change the login', 'Delete the account'], 0],
                    ['Which field cannot be changed in the user settings?', ['Login', 'Password', 'Permissions'], 0],
                    ['Where do you select which objects a user can access?', ['The Objects tab', 'The Modules tab', 'The Reports tab'], 0],
                ],
            ],
            [
                'title' => 'Adding the First Object',
                'slug' => 'adding-the-first-object',
                'summary' => 'Add the first monitoring object to an empty account.',
                'content' => '<p>On a brand-new account the object inventory is completely empty, so you need to add the first one.</p>'
                    .'<h3>Adding an object</h3>'
                    .'<ul>'
                    .'<li>Click <strong>Add New</strong> and fill in all required fields.</li>'
                    .'<li>Enter the name of your object.</li>'
                    .'<li>Fill in the identification number — usually the IMEI, and in some cases the device ID.</li>'
                    .'<li>Specify the name of your device and select an option from the list.</li>'
                    .'</ul>'
                    ."<p>Once these minimum parameters are filled, the object can operate in the system. All remaining settings keep their default values. Click <strong>Save</strong>, and the first object appears in the account's list.</p>",
                'questions' => [
                    ["What is most commonly used as the object's identification number?", ['IMEI', 'VIN', 'License plate'], 0],
                    ["What is the state of a new account's object inventory?", ['Completely empty', 'Pre-filled with demo objects', 'Locked until support enables it'], 0],
                    ['After the minimum parameters are filled, the remaining settings are...', ['Left at their default values', 'Automatically deleted', 'Required before you can save'], 0],
                ],
            ],
            [
                'title' => 'Understanding Sensors in Pilot',
                'slug' => 'understanding-sensors',
                'summary' => 'How physical and virtual sensors turn raw signals into readable values.',
                'content' => '<h3>What a sensor is</h3>'
                    .'<p>A sensor in the Pilot monitoring system is a physical or virtual data source that converts a signal into easily understandable indicators — ignition status, fuel level, onboard voltage, engine operation, and temperature.</p>'
                    .'<h3>Physical sensors</h3>'
                    .'<p>Additional external devices or connections to the tracker. Examples: a DIN-1 ignition sensor lead, or fuel level sensors in the tank.</p>'
                    .'<h3>Virtual sensors</h3>'
                    .'<p>Values computed from other parameters. Examples:</p>'
                    .'<ul>'
                    ."<li>Ignition or engine operation determined from the vehicle's electrical system voltage (more than 13.5 volts means on).</li>"
                    .'<li>Motion inferred from accelerometer vibrations and GPS changes.</li>'
                    .'</ul>'
                    .'<p>In short, a sensor in Pilot is not always a physically connected device — it is often a rule or formula that turns an input signal into a value the user understands in daily use: liters, volts, degrees, or on/off.</p>',
                'questions' => [
                    ['What is a virtual sensor in Pilot?', ['A value computed from other parameters by a rule or formula', 'An external device wired to the tracker', 'A fuel float placed in the tank'], 0],
                    ['Above which onboard voltage is ignition typically considered "on"?', ['12 V', '13.5 V', '20 V'], 1],
                    ['Which of these is an example of a physical sensor?', ['A DIN-1 ignition lead connected to the tracker', 'Motion inferred from the accelerometer', 'Ignition computed from onboard voltage'], 0],
                ],
            ],
            [
                'title' => 'Adding an Ignition Sensor',
                'slug' => 'adding-an-ignition-sensor',
                'summary' => 'Create an ignition sensor by wire parameter or by onboard network voltage.',
                'content' => '<h3>Opening sensor settings</h3>'
                    .'<p>Right-click the object and choose <strong>Sensors</strong>, then click <strong>Add New</strong>. Select <strong>Ignition sensor</strong> from the list, and enter a name and a comment.</p>'
                    .'<h3>Method 1 — by ignition wire</h3>'
                    .'<ul>'
                    .'<li>Click <strong>Parameter List</strong> and select the parameter for the ignition sensor.</li>'
                    .'<li>Set the minimum and maximum values: minimum 1, maximum 1.</li>'
                    .'<li>A parameter value inside this interval counts as on; everything else is off. When the parameter sends 0, the sensor is off.</li>'
                    .'<li>Click <strong>Save</strong>. The ignition sensor appears in the list.</li>'
                    .'</ul>'
                    .'<h3>Method 2 — by onboard network voltage</h3>'
                    .'<p>Use this when the ignition wire (usually yellow) was not connected during installation.</p>'
                    .'<ul>'
                    .'<li>Double-click the sensor and switch to the onboard network parameter via <strong>Parameter List</strong>.</li>'
                    .'<li>This parameter uses a different range: set minimum 14 volts and maximum 20 volts.</li>'
                    .'<li>Values from 14 to 20 volts count as on; everything else is off. At 12 volts the sensor is off; above 14 volts it turns on.</li>'
                    .'<li>Click <strong>Save</strong>. Then click the object on the map to check status — starting the car raises onboard voltage to 14 volts and the ignition status changes to on.</li>'
                    .'</ul>',
                'questions' => [
                    ['How do you open the sensor settings for an object?', ['Right-click the object and select Sensors', 'Double-click an empty spot on the map', 'Open the Reports tab'], 0],
                    ['For the onboard-network method, which min/max values are set?', ['14 to 20 volts', '1 to 1', '0 to 12 volts'], 0],
                    ['If the ignition wire was not connected, which parameter is used instead?', ['The onboard network voltage', 'The GPS coordinates', 'The fuel level'], 0],
                ],
            ],
            [
                'title' => 'The Main Workspace (Home Tab)',
                'slug' => 'main-workspace',
                'summary' => 'Tour the Home tab: navigation, filters, statuses, map tools and object menu.',
                'content' => '<h3>The workspace</h3>'
                    .'<p>After logging in you land on the <strong>Home</strong> tab — your main workspace with the map, the object list, and the tools for real-time monitoring. The top navigation menu switches between sections (primary/online, history, reports, and others available in your plan); you can drag tabs to reorder them.</p>'
                    .'<h3>Finding objects</h3>'
                    .'<ul>'
                    .'<li>Sort objects by status, including Parking.</li>'
                    .'<li>Use the search bar to highlight an object with a yellow marker.</li>'
                    .'<li>Sort by groups or lists, or display objects by GeoZones.</li>'
                    .'<li>Use <strong>Tags</strong> to highlight objects in color by their status.</li>'
                    .'<li>The status counts at the top (moving, parking, no connection) are clickable and show the full list; you can export it to a file.</li>'
                    .'<li>Hover an object for a popup with sensor status; check it to see its location; click its icon for current info.</li>'
                    .'</ul>'
                    .'<h3>Map tools</h3>'
                    .'<p>Adjust zoom, print the area, draw and measure an area, measure distance, switch the map source, build a route between two points, find the distance to the nearest object, use the Geocoder for an address, and toggle traffic.</p>'
                    .'<h3>Object menu (right-click)</h3>'
                    .'<p>Available items depend on your plan: <strong>Current track</strong> (builds the latest track with a player), <strong>Follow object</strong>, <strong>Map</strong> (a mini-map that stays open across tabs), <strong>Editing</strong> (name, make, model), <strong>Temporary Block</strong> (stops the fee and changes status) and unblock, <strong>Cloning</strong> (with all settings), <strong>History</strong> (current day), and <strong>Delete</strong> (restorable via support).</p>',
                'questions' => [
                    ["How do you open an object's action menu?", ['Right-click the object in the list', 'Double-click the map', 'Press the search button'], 0],
                    ['What happens after you apply Temporary Block to an object?', ['No subscription fee is charged and its status changes', 'The object is permanently deleted', 'All its history is erased'], 0],
                    ['What does the Tags tool do?', ['Highlights objects in color according to their status', 'Exports the object list to a file', 'Builds a mileage report'], 0],
                ],
            ],
            [
                'title' => 'Working with History',
                'slug' => 'working-with-history',
                'summary' => 'Replay tracks and review trips, stops and sensor charts for a period.',
                'content' => '<h3>What History shows</h3>'
                    ."<p>The History section shows an object's movement on the map for a chosen period, together with the full chronology of events — stops, trips, and sensor readings such as fuel level or temperature.</p>"
                    .'<h3>Selecting a period</h3>'
                    .'<p>Specify an interval or use the quick buttons (yesterday, today, past week). Items include weekdays, weekends, and parking (data between trips), plus a <strong>Sort by date</strong> checkbox. Then run the search to display the trip and parking datasets.</p>'
                    .'<h3>Reading the track</h3>'
                    .'<p>The track color reflects the speed interval according to the current settings (default by default). On the left is the player: play the track and change its speed. The Follow icon centers the map on the object; another icon returns you to the main tab without losing history data, and the History tab brings you back. Hover the track to read data such as the current speed.</p>'
                    .'<h3>Events and charts</h3>'
                    .'<p>In the events area, check events to show them on the map and configure the columns (date, start/end time, duration, distance). Selecting a trip shows its speed chart at the bottom; hovering it points to the location on the map. Via the Sensors menu you can add a sensor (for example ignition) to the chart, where its on/off moments appear and activation is highlighted in green. GPS signal loss is highlighted in yellow. Uncheck parking, sort by date, and weekend days to view only weekday trips.</p>',
                'questions' => [
                    ['What does the track color represent in History?', ['The speed interval according to the current settings', 'The fuel level', 'The object type'], 0],
                    ['What is highlighted in yellow on the chart?', ['The time of GPS signal loss', 'Every parking period', 'Ignition turned on'], 0],
                    ['How do you view only weekday trips?', ['Uncheck the Weekend Days checkbox and run a search', 'Delete the object', 'Switch the map source'], 0],
                ],
            ],
            [
                'title' => 'Building Reports',
                'slug' => 'building-reports',
                'summary' => 'Generate, customize, group and chart reports, and understand access rules.',
                'content' => '<h3>What reports are</h3>'
                    .'<p>Reports turn accumulated data about movements and events into clearly structured tables.</p>'
                    .'<h3>Building a report</h3>'
                    .'<ul>'
                    .'<li>Specify the period, or use the quick buttons.</li>'
                    .'<li>Mark the vehicles in the list.</li>'
                    .'<li>Open <strong>Report Type</strong> and select the report you need — each has its own calculation logic, and some add extra items.</li>'
                    .'<li>Click <strong>Build Report</strong>. The system collects the data and shows it as a table (for example, mileage).</li>'
                    .'</ul>'
                    .'<h3>Working with a report</h3>'
                    .'<p>A report can be saved, its look customized, and automatic sending scheduled; in the lower right you choose the format and download it. To view events on the map, open the map, click the side arrow, then click an event in the table to see its trip track.</p>'
                    .'<h3>Multiple reports and views</h3>'
                    .'<p>Reports such as Trips and Stops open in their own tabs, so you can switch between them. You can disable unneeded items (for example remove parking to show only trips) and click a trip to see its track. The chart option adds a chart to the table. Reports can be grouped by calendar dates or by objects; a parking report can be split into weekly intervals or by days; a speed report is usually a chart where you drag with the mouse to extend the range and press Reset to reset zoom.</p>'
                    .'<h3>Access rules</h3>'
                    .'<p>Which reports you can access depends on two things: your plan (basic reports are always available; extras appear when a module such as fuel or drivers is connected) and your account configuration, which an admin can change.</p>',
                'questions' => [
                    ['What must you do before generating a report?', ['Specify the period and mark the vehicles', 'Delete all older reports', 'Connect a new module'], 0],
                    ['Basic reports are...', ['Always available in every plan', 'Only available with the fuel module', 'Available only to administrators'], 0],
                    ['How can a report be grouped?', ['By calendar dates or by objects', 'By IP address', 'By user password'], 0],
                ],
            ],
        ];
    }
}
