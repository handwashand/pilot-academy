# Plan: three bigger features from Support Training Hub

Status: **for discussion — nothing here is built.** Written 2026-09-10.

These three were built in the sister project (`support-engine`, branch
`hub-version2`). Each one changes how training works rather than how it looks,
so each needs a decision from you before code. For every feature: what it is,
what Pilot Academy already has to build on, the work, and the questions only you
can answer.

---

## 1. Refreshers — spaced repetition after a certificate

**What it is.** 30 and 90 days after someone earns a certificate, they get a
short quiz (5 questions) drawn from that course's final-quiz bank. It measures
whether the training *stuck*. In support-engine it grants nothing and takes
nothing away, and wrong answers come back with explanations.

**What we already have.**
- A per-course question bank (`finalQuestions`) and the attempt machinery
  (`QuizAttempt`, shuffle-and-take per attempt) in `FinalQuizController`.
- `certificates.issued_at` — the clock the 30/90 days run from.
- Email (certificate mail) and a reminder pattern (`RemindStudent`).

**The work.**
- Table `refreshers`: `user_id`, `course_id`, `certificate_id`, `due_at`,
  `interval_days` (30/90), `status` (due / completed / lapsed), `score`,
  `completed_at`. Created when a certificate is issued.
- A scheduled command that emails when one falls due and marks lapsed ones.
  **Nothing runs Laravel's scheduler today** — `DEPLOY.md` sets up no cron and
  `routes/console.php` schedules nothing. This needs
  `* * * * * php8.4 artisan schedule:run` on the server, which is a deploy change.
- A learner page (mobile-first) and a "Refreshers due" card on the home page.
- Admin: a per-course refresher score column, and a dashboard figure — "of
  people certified 90+ days ago, what share still pass".
- Questions need an **explanation** field to show on wrong answers — a new
  column on the question tables and a field in the editors.

**Decide first.**
1. Do refreshers count for anything, or are they purely a measurement? (The
   sister project chose "measurement only". Revoking a certificate on a bad
   refresher is a much bigger policy change.)
2. 30 and 90 days — or other intervals?
3. Do partner companies see their people's refresher results?

**Size:** about 2–3 days, including the scheduler, tests and the guides.

---

## 2. Video engagement — how much of a video actually gets watched

**What it is.** Per lesson, the **median** share of an uploaded video that
learners watch. Median, not mean: one person who opens a video and walks away
would drag an average down enough to condemn a video most people finished.

**What we already have — and why it is not enough.** `video_positions` stores
the **last** position per learner, and resets near the end ("being near the end
starts the video fresh"). A last position is not a watched share: someone who
watched all 25 minutes and scrubbed back to minute 3 reads as 12%. The data
cannot answer the question as it stands.

**The work.**
- Record the **furthest point reached** and the video's **real duration** (the
  player knows it — `video.duration` — so authors no longer have to type it
  for this). Either two columns on `video_positions` (`max_seconds`,
  `duration_seconds`) or a separate table as support-engine did. The column
  route is smaller; it must not touch `completedLessons`, for the reason the
  work log gives.
- Compute the median in PHP per lesson (dozens of rows, not millions).
- Show it on the **Hardest lessons** dashboard widget or a new "Least watched"
  one, beside the fail rate — a lesson people fail *and* abandon is the one to
  rewrite.
- **Uploaded video only**, said on screen. Measuring YouTube playback means
  loading YouTube's tracking API into the page, which also tells Google which
  of your partners watched what — and undoes the privacy-enhanced player just
  shipped.

**Decide first.**
1. Is uploaded-only acceptable, given most lessons may be YouTube? (Check the
   split before deciding — it may make this not worth it yet.)
2. Admin-only, or visible to creators for their own products?

**Size:** about 1 day. Starts collecting from deploy — no history.

---

## 3. A multilingual academy

**What it is.** In support-engine: English, Russian, Spanish and French. A
language switcher in both the student site and the panel, the choice saved to
the account, every interface string editable from the panel (one row per
string, a column per language, gaps marked red), and the guides in all four
languages with an "only in English" notice where a translation is missing.

**What we already have.** Nothing language-aware. Every string is inline
English in Blade; course and lesson content is single-language.

**The work — two very different sizes.**
- **Interface only** (buttons, headings, messages, the Help page): a
  `translations` table + helper, a locale middleware, a switcher, and a sweep
  through every student view replacing strings. Support-engine's sweep was
  ~340 portal strings and ~750 panel strings. Roughly 3–5 days for the student
  site; the panel doubles it.
- **Content** (course titles, lesson text, questions, transcripts, certificates
  in the learner's language): per-field translations and editors that show
  each language. This is the large part — every creator has to write each
  lesson more than once, and a lesson is only as available as its least
  translated field.

**Decide first.**
1. Which languages, and who writes the translations?
2. Interface only, or content too? (Interface-only with English lessons is a
   coherent first step; the reverse is not.)
3. Student site only, or the panel too? Creators may be English-speaking even
   where partners are not.
4. Certificates: printed in the learner's language, or always English?

**Size:** interface-only student site ≈ 1 week. Content translation is a
project of its own and should be scoped separately.

---

## Traps the sister project already paid for

- A `$navigationLabel` **property** is evaluated before the request's locale is
  set — translated labels must come from `getNavigationLabel()` methods.
- Translated text runs ~40% wider than English. The student header is already
  full at 375px (see the 2026-09-10 work log): a switcher needs a menu, not
  another icon.
- Refresher questions drawn from the final bank will be questions the learner
  has already seen — explanations matter more than novelty.
