<?php

namespace Database\Seeders;

use App\Models\CaseStudy;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class CaseStudySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->studies() as $study) {
            $relatedSlugs = $study['related_slugs'] ?? [];
            unset($study['related_slugs']);

            CaseStudy::updateOrCreate(
                ['slug' => $study['slug']],
                [
                    ...$study,
                    'related_lesson_ids' => $this->lessonIds($relatedSlugs),
                    'status' => CaseStudy::STATUS_DRAFT,
                    'is_anonymized' => true,
                    'is_customer_approved' => false,
                ],
            );
        }
    }

    private function lessonIds(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return Lesson::whereIn('slug', $slugs)->pluck('id')->all();
    }

    private function studies(): array
    {
        return [
            [
                'title' => 'Delivery arrival and departure monitoring with geofences',
                'slug' => 'delivery-arrival-departure-monitoring-geofences',
                'short_problem' => 'A delivery team needs consistent proof of arrival and departure at recurring customer sites without manually checking every route.',
                'industry' => 'Delivery',
                'features_used' => ['GeoZones', 'History', 'Reports', 'Notifications'],
                'difficulty' => CaseStudy::DIFFICULTY_INTERMEDIATE,
                'implementation_time' => '2-4 hours',
                'sort_order' => 10,
                'scenario_problem' => '<p>Illustrative scenario: a partner configures monitoring for vehicles that visit the same depots and customer sites every week. Dispatchers need to know when a vehicle enters or leaves a site and need a repeatable way to review missed stops or long dwell times.</p>',
                'desired_outcome' => '<p>Each important site is represented by a GeoZone, arrival and departure events can be reviewed in History or reports, and dispatchers have a clear exception list for late, missed, or unusually long visits.</p>',
                'prerequisites' => '<ul><li>Objects are created and reporting current GPS data.</li><li>Site boundaries are known and can be drawn as GeoZones.</li><li>Users have access to History and Reports for the relevant objects.</li></ul>',
                'pilot_features' => '<ul><li>GeoZones for delivery sites.</li><li>History to review tracks, stops, parking, and events.</li><li>Reports for repeated review of visits and exceptions.</li><li>Notifications or rules where the customer plan enables them.</li></ul>',
                'configuration_steps' => '<ol><li>Create or verify object groups for the delivery fleet.</li><li>Create a GeoZone for each monitored delivery site. Use a boundary large enough to avoid GPS jitter at the entrance but small enough to exclude nearby roads.</li><li>Apply the GeoZones to the relevant object group.</li><li>Use History for a known delivery day to confirm that entries, stops, and exits align with the expected site visits.</li><li>Create a saved report or exception view that shows site visits, stops, and parking intervals for the selected objects.</li><li>If notifications are available in the deployment, configure arrival or departure alerts only for sites where real-time action is required.</li></ol>',
                'testing_verification' => '<ol><li>Pick one vehicle and one known delivery route.</li><li>Open History for the delivery period and confirm the track crosses the GeoZone boundary at the expected time.</li><li>Confirm that stop or parking intervals are visible inside the site.</li><li>Run the saved report for the same period and compare the event times with History.</li><li>Ask the customer to validate one sample route before copying the pattern to all sites.</li></ol>',
                'expected_results' => '<p>Expected outcome is an auditable arrival/departure trail for configured sites. Results depend on GPS quality, GeoZone size, object reporting interval, and whether the vehicle stops close enough to the site boundary.</p>',
                'troubleshooting' => '<ul><li>If arrivals trigger too early, reduce the GeoZone or move the boundary away from approach roads.</li><li>If arrivals are missed, check GPS data gaps and object activity in History.</li><li>If reports show noise, group sites and objects more narrowly before broad rollout.</li></ul>',
                'adaptation' => '<p>The same pattern can be adapted for depot departures, technician site visits, school bus stops, or restricted areas. Start with a small set of high-value sites, validate the event quality, then expand.</p>',
                'source_note' => 'Draft scenario derived from existing Pilot Academy lessons covering GeoZones, History, Reports, object groups, and map/history review. Configuration must be validated against the customer plan before publishing.',
                'performance_claim_note' => 'No measured savings or customer performance claims are included.',
                'related_slugs' => ['main-workspace', 'working-with-history', 'building-reports'],
            ],
            [
                'title' => 'Overspeeding detection and escalation',
                'slug' => 'overspeeding-detection-and-escalation',
                'short_problem' => 'Fleet managers need a repeatable way to identify speeding events, review context, and escalate recurring driver behavior.',
                'industry' => 'Field service',
                'features_used' => ['Speed control', 'History', 'Reports', 'Notifications'],
                'difficulty' => CaseStudy::DIFFICULTY_INTERMEDIATE,
                'implementation_time' => '1-3 hours',
                'sort_order' => 20,
                'scenario_problem' => '<p>Illustrative scenario: a service fleet operates across urban and highway routes. Managers do not want to watch live movement all day, but they need exceptions for unsafe speed and a reliable review trail.</p>',
                'desired_outcome' => '<p>Speeding events are visible in History and reports, recurring events can be escalated to supervisors, and thresholds are clear enough that drivers and managers interpret them consistently.</p>',
                'prerequisites' => '<ul><li>Objects report GPS speed in History.</li><li>Managers have access to History and speed-related reports.</li><li>The customer has agreed on speed thresholds, escalation recipients, and review cadence.</li></ul>',
                'pilot_features' => '<ul><li>History track speed coloring and speed chart review.</li><li>Reports for speed events or speed summaries where available.</li><li>Rules or notifications for real-time exceptions where enabled.</li></ul>',
                'configuration_steps' => '<ol><li>Define the threshold policy with the customer, including grace periods and who receives alerts.</li><li>Verify in History that object speed is visible on tracks and charts.</li><li>Configure the relevant speed report or saved view for the object group.</li><li>If rule-based notifications are enabled, configure an alert for the agreed threshold and route it to the escalation recipient or distribution list.</li><li>Set a weekly review report for recurring events and driver coaching.</li></ol>',
                'testing_verification' => '<ol><li>Select a historical trip with known higher-speed segments.</li><li>Open History and verify that speed values and chart points appear where expected.</li><li>Run the report for the same object and period.</li><li>Confirm that the event count and timestamps are close enough for operational review.</li><li>Test one notification recipient before enabling broad escalation.</li></ol>',
                'expected_results' => '<p>Expected outcome is a manageable exception workflow instead of continuous live monitoring. Limitations include GPS gaps, local speed-limit interpretation outside Pilot, and alert fatigue if thresholds are too low.</p>',
                'troubleshooting' => '<ul><li>If every route triggers alerts, revisit thresholds and escalation rules.</li><li>If an event cannot be found on the map, compare report time zone and selected period with History.</li><li>If notifications are not delivered, confirm user permissions and recipient configuration.</li></ul>',
                'adaptation' => '<p>Use stricter thresholds for high-risk vehicle groups and softer weekly reporting for general coaching. The same pattern can also support harsh driving review if the customer has suitable device data.</p>',
                'source_note' => 'Draft scenario derived from existing Pilot Academy lessons covering History speed display, reports, and exception-based monitoring. Validate exact rule and notification availability before publishing.',
                'performance_claim_note' => 'No quantified safety or savings claim is included.',
                'related_slugs' => ['working-with-history', 'building-reports', 'main-workspace'],
            ],
            [
                'title' => 'Fuel event investigation',
                'slug' => 'fuel-event-investigation',
                'short_problem' => 'A customer needs a structured investigation path for suspected refueling, drain, or sensor anomalies without exposing raw customer data.',
                'industry' => 'Fuel logistics',
                'features_used' => ['Fuel sensors', 'History', 'Reports', 'Sensors'],
                'difficulty' => CaseStudy::DIFFICULTY_ADVANCED,
                'implementation_time' => '4-8 hours',
                'sort_order' => 30,
                'scenario_problem' => '<p>Illustrative scenario: a fleet uses fuel level data and wants partners to investigate sudden changes. The goal is to distinguish expected movement, refueling, GPS/data gaps, and possible drain events.</p>',
                'desired_outcome' => '<p>Partners can review fuel sensor configuration, compare fuel changes with vehicle movement in History, and produce a clear investigation note without publishing customer names, locations, or live vehicle data.</p>',
                'prerequisites' => '<ul><li>Fuel level sensor data is available for the object.</li><li>The object has enough History data for the investigation period.</li><li>Relevant users can access History, sensors, and reports.</li><li>Any screenshots are sanitized before they are used for training or customer communication.</li></ul>',
                'pilot_features' => '<ul><li>Sensor configuration and sensor charts.</li><li>History playback for the same period as the suspected event.</li><li>Reports that include fuel, trips, stops, and parking where available.</li></ul>',
                'configuration_steps' => '<ol><li>Verify that the object has a fuel sensor and that the sensor value appears in History or charts.</li><li>Select the investigation period around the reported event.</li><li>Review History for movement, stops, parking, GPS signal loss, and sensor changes.</li><li>Run the relevant report for the same object and period.</li><li>Compare the timing of fuel-level change against stops, ignition or engine state where configured, and GPS availability.</li><li>Record findings in an internal note with sanitized screenshots only.</li></ol>',
                'testing_verification' => '<ol><li>Use a known refueling or benign event first.</li><li>Confirm the fuel chart change lines up with a plausible stop or service visit.</li><li>Check whether the same event appears in the report and History.</li><li>Repeat with one suspected anomaly and document whether data quality supports a conclusion.</li></ol>',
                'expected_results' => '<p>Expected outcome is a repeatable evidence trail, not an automatic accusation. Limitations include sensor calibration, installation quality, reporting interval, GPS gaps, tank shape, and driving conditions.</p>',
                'troubleshooting' => '<ul><li>If fuel jumps while GPS is unavailable, mark the conclusion as limited by data quality.</li><li>If the sensor is noisy, review calibration and installation before treating events as operational exceptions.</li><li>If reports and History disagree, confirm the selected period, time zone, and object identity.</li></ul>',
                'adaptation' => '<p>The same investigation pattern can support temperature excursions, ignition anomalies, or sensor-based maintenance checks. Replace the fuel sensor with the relevant sensor and keep the same evidence discipline.</p>',
                'source_note' => 'Draft scenario derived from existing Pilot Academy lessons covering sensors, History, reports, and sensor charts. Validate exact fuel module availability and device data before publishing.',
                'performance_claim_note' => 'No measured fuel savings or loss prevention claim is included.',
                'related_slugs' => ['understanding-sensors', 'working-with-history', 'building-reports'],
            ],
        ];
    }
}
