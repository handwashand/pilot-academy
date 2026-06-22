<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Seeder;

class PilotQuickStartSeeder extends Seeder
{
    public function run(): void
    {
        // Reliable public placeholder video — replace per lesson with your own YouTube link.
        $sampleVideo = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

        $course = Course::updateOrCreate(
            ['slug' => 'pilot-quick-start'],
            [
                'title' => 'Pilot Quick Start',
                'description' => 'Everything a new user needs to get going with Pilot in under an hour.',
                'level' => 'beginner',
                'duration_minutes' => 45,
                'is_published' => true,
                'sort_order' => 1,
            ],
        );

        // Start clean so re-seeding is idempotent.
        $course->lessons()->delete();

        $lessons = [
            [
                'title' => 'Getting Started with Pilot',
                'slug' => 'getting-started',
                'level' => 'beginner',
                'summary' => 'Sign in, find your way around the dashboard, and understand the main areas of Pilot.',
                'content' => '<h2>Your first login</h2>'
                    . '<p>Pilot is a fleet management platform. After signing in, you land on the <strong>Dashboard</strong> — your home base that summarizes fleet activity, alerts, and key metrics at a glance.</p>'
                    . '<p>The left navigation groups everything into clear areas: <strong>Assets</strong> (your vehicles and devices), <strong>Map</strong> (live tracking), <strong>Reports</strong>, <strong>Rules &amp; Notifications</strong>, and <strong>People</strong>.</p>'
                    . '<h3>What you\'ll do in this lesson</h3>'
                    . '<ul><li>Sign in and recognize the dashboard layout</li><li>Use the left navigation to move between areas</li><li>Customize which widgets appear on your dashboard</li></ul>'
                    . '<p>Tip: anything you open frequently can be pinned to the dashboard, so the data you care about is one click away.</p>',
                'questions' => [
                    ['What screen do you land on right after signing in to Pilot?', ['The Dashboard', 'The billing page', 'A blank map', 'The settings menu'], 0],
                    ['Which area would you open to see your vehicles and devices?', ['Reports', 'Assets', 'Community', 'Sign out'], 1],
                    ['How can you keep frequently used data one click away?', ['Email it to yourself daily', 'Pin widgets to the dashboard', 'Print it', 'You can\'t'], 1],
                ],
            ],
            [
                'title' => 'The Live Map & Assets',
                'slug' => 'live-map-and-assets',
                'level' => 'beginner',
                'summary' => 'Track vehicles in real time, read asset status, and play back trip history on the map.',
                'content' => '<h2>Seeing your fleet live</h2>'
                    . '<p>The <strong>Map</strong> shows every active asset in real time. Each marker reflects status — moving, idling, or stopped — using color, so you can read the whole fleet at a glance.</p>'
                    . '<p>Click any asset to open its card: current speed, location, driver, and last update time. From there you can jump into <strong>trip history</strong> and replay where a vehicle has been over any time window.</p>'
                    . '<h3>Key actions</h3>'
                    . '<ul><li>Filter the map by group, status, or zone</li><li>Open an asset card for live detail</li><li>Replay historical trips with the timeline scrubber</li></ul>'
                    . '<p>Trip replay is one of the most-used features for resolving customer or driver questions.</p>',
                'questions' => [
                    ['What does the color of an asset marker on the map indicate?', ['The vehicle brand', 'Its status (moving, idling, stopped)', 'The driver\'s mood', 'Fuel brand'], 1],
                    ['What do you click to see an asset\'s live speed and location?', ['The asset card', 'The logout button', 'The invoice', 'Nothing — it\'s hidden'], 0],
                    ['Which feature answers "where was this vehicle at 3pm?"', ['Billing', 'Trip history / replay', 'Community forum', 'The quiz'], 1],
                ],
            ],
            [
                'title' => 'Building Reports & Dashboards',
                'slug' => 'reports-and-dashboards',
                'level' => 'intermediate',
                'summary' => 'Run built-in reports, schedule them, and pin custom charts to your dashboard.',
                'content' => '<h2>From raw data to insight</h2>'
                    . '<p>Pilot ships with a large catalog of <strong>built-in reports</strong> — utilization, idling, speeding, fuel, maintenance and more. Pick a report, set the date range and groups, and run it.</p>'
                    . '<p>Any report can be <strong>scheduled</strong> to email automatically — daily, weekly, or monthly — so stakeholders get numbers without logging in.</p>'
                    . '<h3>Custom dashboards</h3>'
                    . '<ul><li>Choose a report and apply filters</li><li>Save the result as a dashboard chart</li><li>Schedule recurring delivery to your team</li></ul>'
                    . '<p>Best practice: keep your dashboard to the 4–6 metrics that drive decisions.</p>',
                'questions' => [
                    ['What can you do so stakeholders get reports without logging in?', ['Nothing', 'Schedule reports by email', 'Call them', 'Disable reports'], 1],
                    ['How many metrics should a focused dashboard ideally show?', ['All of them', 'Around 4–6 key metrics', 'Exactly 1', 'At least 50'], 1],
                    ['Which is an example of a built-in report?', ['Idling report', 'Weather forecast', 'Stock prices', 'Recipe book'], 0],
                ],
            ],
            [
                'title' => 'Rules, Exceptions & Notifications',
                'slug' => 'rules-and-notifications',
                'level' => 'intermediate',
                'summary' => 'Set thresholds once, then let Pilot surface only the exceptions that need attention.',
                'content' => '<h2>Management by exception</h2>'
                    . '<p>A <strong>rule</strong> defines an acceptable limit — for example, a speed threshold or an allowed working-hours window. Pilot monitors continuously and flags an <strong>exception</strong> only when the limit is crossed.</p>'
                    . '<p>This is the core idea of <em>management by exception</em>: instead of watching everything, you watch the deviations. Less noise, more focus.</p>'
                    . '<h3>Setting it up</h3>'
                    . '<ul><li>Turn on a built-in rule or create a custom one</li><li>Set the threshold and which groups it applies to</li><li>Choose how you\'re notified: email, SMS, or distribution list</li></ul>'
                    . '<p>Tip: if a rule generates more than 2–3 alerts a day per person, turn it into a daily digest instead.</p>',
                'questions' => [
                    ['What does a rule flag when its limit is crossed?', ['An invoice', 'An exception', 'A new user', 'A map'], 1],
                    ['What is the core idea behind rules in Pilot?', ['Watch everything constantly', 'Management by exception', 'Disable notifications', 'Manual checking only'], 1],
                    ['If a rule creates many alerts per day, what\'s the better setup?', ['Ignore it', 'Turn it into a daily digest report', 'Delete all data', 'Send more alerts'], 1],
                ],
            ],
            [
                'title' => 'Users, Roles & Permissions',
                'slug' => 'users-and-permissions',
                'level' => 'intermediate',
                'summary' => 'Add team members, assign roles, and control exactly what each person can see and do.',
                'content' => '<h2>Giving people the right access</h2>'
                    . '<p>Under <strong>People</strong> you add users and assign each a <strong>role</strong> (a set of permissions). Roles control which areas and actions a person can access — from full administrator down to view-only.</p>'
                    . '<p>For finer control, Pilot supports <strong>custom permission sets</strong>, so you can grant exactly one capability without opening up the rest of the system.</p>'
                    . '<h3>Common tasks</h3>'
                    . '<ul><li>Add a user and assign a role</li><li>Restrict a user to specific vehicle groups</li><li>Create a custom permission set for a special role</li></ul>'
                    . '<p>Principle of least privilege: give each person the minimum access they need to do their job.</p>',
                'questions' => [
                    ['Where do you add new team members?', ['The Map', 'The People area', 'The invoice', 'Sign out'], 1],
                    ['What controls which areas a person can access?', ['Their role / permissions', 'Their email provider', 'The weather', 'Random chance'], 0],
                    ['What does "principle of least privilege" mean?', ['Give everyone admin', 'Give the minimum access needed', 'Remove all users', 'Share one password'], 1],
                ],
            ],
        ];

        foreach ($lessons as $i => $data) {
            $lesson = $course->lessons()->create([
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'content' => $data['content'],
                'youtube_url' => $sampleVideo,
                'duration_minutes' => 8,
                'is_published' => true,
                'sort_order' => $i + 1,
            ]);

            foreach ($data['questions'] as $qi => [$prompt, $answers, $correctIndex]) {
                $question = $lesson->questions()->create([
                    'prompt' => $prompt,
                    'sort_order' => $qi + 1,
                ]);

                foreach ($answers as $ai => $answerText) {
                    $question->options()->create([
                        'text' => $answerText,
                        'is_correct' => $ai === $correctIndex,
                        'sort_order' => $ai + 1,
                    ]);
                }
            }
        }
    }
}
