# Dashboard Improvements Plan

Last updated: 2026-09-30

## Objective

Make the `/admin` dashboard trustworthy, useful to both panel roles, and focused
on decisions rather than additional totals. Learner information must remain
admin-only. Creators should receive a content workspace scoped to their assigned
products.

## Current Findings

- All existing widgets report learner activity and are therefore admin-only.
  Creators can enter the panel but have no useful dashboard content.
- **Active students** currently means learners who completed a lesson at any
  point, rather than learners active during a recent period.
- **Progress by partner company** counts every lesson with Published status,
  including lessons that partners cannot reach because all of their courses are
  drafts.
- The partner percentage assumes every learner is expected to complete every
  available lesson. The academy has no enrolment or course-assignment model, so
  this should eventually become an engagement table instead of an implied
  completion target.
- A learner with any valid certificate is absent from the stalled list, even if
  they later stall in another course. Correct course-level follow-up needs a
  stronger learner/course progress identity.
- Dashboard metrics mix lifetime, 14-day, 30-day, and 90-day windows without a
  shared period filter.
- Case Studies, Tutorials, and Webinars are not represented in dashboard
  reporting.
- Activity events identify content by mutable labels. Reliable per-course
  funnels and drill-downs need stable content identifiers.

## Status

| Phase | Status | Scope |
| --- | --- | --- |
| 1. Metric accuracy | Complete | Recent active learners and learner-visible lesson counts are implemented and verified. |
| 2. Creator workspace | Complete | Product-scoped content status cards with direct links are implemented and verified. |
| 3. Dashboard filters and partner engagement | Complete | Shared date/partner/product/course filters and an honest engagement table. |
| 4. Course-level learner journey | Complete | Stable activity subjects, funnel, and course-specific stalled learners. |
| 5. Resource engagement | Complete | Signed-in Case Study, Tutorial, and Webinar usage. |
| 6. Visual verification | In progress | Automated rendering is covered; a manual browser screenshot review remains. |

## Phase 1: Metric Accuracy

- [x] Define active learners as learners with their own activity in the last 30
  days; exclude staff activity and reminder events.
- [x] Show the 30-day definition in every shipped panel language.
- [x] Add one reusable Lesson query scope for lessons that are both published
  and attached to at least one published course.
- [x] Use that scope for the headline available-lesson count.
- [x] Use the same scope for partner-company progress calculations.
- [x] Add regression tests for recent activity, expired activity, staff
  exclusion, and lessons that exist only inside draft courses.
- [x] Refine the 30-day activity graph to show unique active learners alongside
  lesson completions, with localized dates and without overlapping area fills.

## Phase 2: Creator Workspace

- [x] Add a creator-only dashboard widget.
- [x] Count Courses, Case Studies, Tutorials, and Webinars for assigned products
  only.
- [x] Show total, published, draft, and archived counts with links to each content list.
- [x] Keep the widget hidden from admins, who already receive the learner
  dashboard, and from learners, who cannot enter the panel.
- [x] Translate all labels and descriptions in English, Russian, Spanish,
  French, and Portuguese.
- [x] Test product scoping and role visibility.

## Phase 3: Filters and Partner Engagement

- [x] Add a dashboard-wide date range with a clear 30-day default.
- [x] Add company, product and course filters for administrators.
- [x] Replace the company completion percentage with a table showing partner,
  learners, active learners, completions, certificates, and last activity.
- [x] Add a direct link from every partner row to its detail screen.
- [x] Keep Content Health on its dedicated page and sidebar badge rather than
  recreating the removed dashboard warning card.

## Phase 4: Course-Level Learner Journey

- [x] Store a stable subject type and subject ID on future activity events while
  retaining the historical display label.
- [x] Add indexes for period/type/subject, product and course reporting.
- [x] Build a signed-in learner funnel: course opened, lesson opened, lesson
  completed, course completed, certificate issued.
- [x] Make stalled follow-up course-specific so completing one course does not
  hide a learner who stalled in another.
- [x] Preserve shared-lesson behavior through `course_lesson`.

## Phase 5: Resource Engagement

- [x] Use signed-in-only usage. Anonymous browsing remains untracked; adding it
  would require a separate privacy and retention decision.
- [x] Record Case Study opens, Tutorial opens, Webinar opens and joins, and recording
  opens with stable resource IDs.
- [x] Add a compact resource-engagement view with product and period filters.

## Phase 6: Visual Verification

- [x] Render the administrator and creator dashboards in feature and Livewire
  tests, including empty data and translated labels.
- [x] Keep every new chart full-width with a stable maximum height and integer
  axes; keep filters responsive from one to five columns.
- [ ] Review authenticated desktop and mobile screenshots in a real browser.

## Verification

For each implemented phase:

- Run focused dashboard, creator-role, translation, and guide tests.
- Run Pint on changed PHP files.
- Load `/admin` as an administrator and a product creator.
- Verify an empty academy, one assigned product, several products, draft-only
  content, and content belonging to another creator.
- Confirm creators receive no learner names, companies, progress, attempts,
  feedback, or certificate information.
- Update `docs/CHANGELOG.md`, `docs/admin-guide.md`, its localized copies, and
  the `agent.md` work log for visible changes.

### 2026-09-30 Results

- [x] Dashboard, creator-role, panel translation, student translation, admin
  guide, guide-menu, and changelog tests: 102 passed (5,427 assertions).
- [x] Pint passed for all seven changed PHP source and test files.
- [x] `/admin` in the rebuilt app container returns the expected 302 redirect to
  `/admin/login`; authenticated admin and creator dashboard rendering is covered
  by feature and Livewire tests.
- [x] Empty data, recent and expired activity, reminder exclusion, staff
  exclusion, draft-only lessons, role visibility, and assigned-product scoping
  are covered by regression tests.
- [x] Changelog and all five admin guides are updated.
- [ ] A manual browser screenshot review was not run because this repository has
  no browser automation dependency; visual layout remains a follow-up check.

### 2026-09-30 Activity Graph Refinement

- [x] Daily activity is now the number of distinct learners with a login,
  course open, lesson open, lesson completion, or course completion that day.
- [x] Reminder events and all staff activity remain excluded.
- [x] The graph uses a blue line for active learners and green bars for lesson
  completions, with a bottom legend, indexed tooltips, localized dates, at most
  ten visible x-axis labels, integer y-axis ticks, and a stable 320px height.
- [x] Dashboard, roles, translations, guides, and changelog: 101 tests passed
  (5,427 assertions). Pint passed for the two changed PHP files.

### 2026-09-30 Filters, Journey and Resources

- [x] Administrators have a 30-day date range plus partner, product and course
  filters; creators keep their product-scoped content workspace without learner
  filters.
- [x] Partner engagement replaces the unsupported completion percentage.
- [x] New activity events retain labels for history and add stable subject,
  course and product IDs for reporting.
- [x] Learner journey and resource engagement use signed-in learner activity;
  anonymous usage is intentionally not collected.
- [x] Stalled follow-up is course-specific, including its displayed course,
  completion count and last completion.
- [x] Focused dashboard, Case Study, Tutorial and Webinar tests: 64 passed (265
  assertions). Full-suite and formatting results are recorded after the final
  verification pass.

## Decisions

- Phase 1 uses the existing activity log and content statuses; it needs no
  migration or dependency.
- Creator metrics are editorial counts only. Learner reporting remains
  administrator-only through `ReportsOnLearners`.
- Activity labels remain for readable history and backwards compatibility;
  new events also carry stable content identities. Historical label-only events
  remain visible in unfiltered reports but cannot be assigned to a product or
  course retroactively.
- Resource reporting is signed-in only. Anonymous aggregate analytics are not
  introduced without a separate privacy, retention and consent decision.
- The existing Content Health page remains the single definition and location
  for broken content.
