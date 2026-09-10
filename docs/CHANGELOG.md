# What's new — Pilot Academy

A short log of changes to the academy, newest first, in plain language.
Add a new entry here whenever something visible to admins or students changes.

<!-- This file IS the What's new page. The panel reads and parses it on every
     request (/admin/changelog), so there is no second copy to keep in step.

     The headings are structure, not decoration:

       ## <Month> <Year>   starts a release and adds a card to the page.
       ### Added | Changed | Fixed | Known limitations
                           sets the category of the items under it — that is
                           what the filter pills and the coloured dots read.
                           "Limitations" and "Known issues" mean the same thing.
       ### Anything else   still renders, keeping its own heading text.

     One `-` bullet is one entry, and its wrapped lines belong to it. A section
     written as prose instead of bullets renders whole. Comments like this one
     are stripped before anything is rendered, so notes to the next editor can
     live right here. -->

## 2.1.0 — September 2026

### Added

- **Final quiz health** (**Results → Final quiz health**, admins only) tells you
  whether the final quiz is doing its job. **First-time pass rate** is judged
  against a suggested 65–80% band — above it the quiz is likely too easy, below
  it the lessons probably don't teach what it asks — with a verdict per course,
  and **Too few to judge** until there are 10 first tries. **Days to
  certificate** shows the typical time from first lesson to certificate. Staff
  previews are left out of both.
- **The student home page always says what to do next.** The card at the top
  used to disappear the moment a student finished their last lesson — exactly
  when the final quiz unlocked, and nothing said so. It now reads **Your final
  quiz is ready**, with the pass mark and attempts left. A student who has just
  signed up sees **Start here** and the first lesson, instead of "welcome back".
- **Students can see their own progress.** Signed-in students get a **Your
  progress** card on the home page: courses in progress, completed, and
  certificates — plus their **last final quiz result**, which used to show once
  after submitting and then vanish. A failed attempt says how many tries are
  left, or to contact an administrator when there are none.
- **A Help page for students.** **Help** (the **?** in the top bar, on every
  page, logged in or not) explains how the academy works: what finishes a
  lesson, when the final quiz unlocks, where certificates are, and what to do
  when something will not finish. It is readable on a phone, with a contents
  list at the top.
- **What's new downloads as a PDF** — one release from the **PDF** link on its
  heading, or everything from **All releases (PDF)** beside the filters. For
  sending release notes to people without a panel account. The filters on
  screen do not change what goes into the file.
- **Edit your own profile.** Open the account menu (your initials, top right) →
  **Profile** to change your name, email or password. Creators could not change
  their own password before, because they have no access to **Users**.
- **Search the guide.** **Docs → Guide** now has a contents list beside it and a
  search box that hides the sections that do not mention what you typed.

### Changed

- **The left menu is grouped by what you are doing**: **Content** (Courses,
  Lessons, Products, Media Items), **People** (Users, Companies), **Results**
  (Certificates, Final quiz health) and **Docs** (Guide, What's new). Nothing was
  renamed and every screen keeps its address.
- **YouTube lessons use YouTube's privacy-enhanced player.** No tracking cookie
  is set until the student presses play, and the end of a video only suggests
  videos from the same channel — not whatever YouTube would recommend next.
- **The top bar fits on small phones.** With Help added, the student site's
  header stays on one line down to 360px wide, and **Log in**, **Log out** and
  **Register** are now comfortable to tap.

### Known limitations

- **Final quiz health cannot say which questions are the problem.** The academy
  keeps each attempt's total score, not the answer given to each question, so
  the page shows *Question difficulty* as **Not measured** rather than guessing.

### Fixed

- **A YouTube link that is not a video no longer saves silently.** A playlist,
  channel or Vimeo link used to save without complaint and leave the lesson
  with no video at all. The lesson form now refuses it and says what to paste
  instead. **YouTube live links** (`youtube.com/live/…`) used to be one of the
  casualties and now play.
- **Links saved before this fix are listed** on the Dashboard under **Content
  needing attention**, as *YouTube link that is not a playable video*, each
  with a link straight to the lesson.

### What's new is now a filterable list

**This page is built from `docs/CHANGELOG.md` itself**, read fresh on every
visit. Whoever ships a change writes it in that one file and it appears here —
there is no second copy to update and no way for the two to fall out of step.

**Search the box** at the top to find a change by any word in it, and **click a
category pill** to narrow to just the additions, the fixes, or the known
limitations. Months with nothing left in them drop out of the way rather than
sitting there empty.

## 2.0.0 — September 2026

### Videos remember where you stopped, and can be read instead of watched

**A video picks up where the student left it.** Someone interrupted 18 minutes
into a 25-minute video used to start again from the beginning. Their place is
saved to their account, so it follows them to another device. Being near the end
starts the video fresh, since that means they had finished.

**Lessons can now carry a transcript.** Add one in **Lessons** → **Video
transcript** (paste the captions from YouTube if you have them). Students get a
**Transcript** panel under the video they can open and read instead of watching
— which also means someone who cannot use audio can still complete the training.

**It also makes videos searchable.** Until now the words spoken in a video
matched nothing; student search now looks inside transcripts too.

### Tell us what you thought of a course

When a student finishes every lesson, the **Course complete** card asks whether
the course was useful — a thumbs up or down and an optional sentence.

Read it in **Courses** → **Edit** → **Student feedback**, with the student, their
partner company and what they wrote. Filter by verdict to see the complaints on
their own.

> **Students never see each other's feedback**, and there are no public star
> ratings. The dashboard could already tell you which lessons students *fail*.
> This answers the different question of which ones they found useless.

### Order lessons by dragging them, and reuse ones you already have

Open a course (**Courses** → **Edit**) and there is now a **Lessons** tab.

**Drag the rows to set the order.** That order is what students work through —
the course page, the lesson sidebar and "Continue where you left off" all follow
it. Nothing to save.

**Add existing lesson** moves a lesson you already wrote into this course,
searching by name and showing which course it sits in now.

> A lesson belongs to one course only, so this is a **move, not a copy** — it
> leaves the course it was in. Its video, text, questions and students' progress
> all move with it. To reuse material in two courses, **Duplicate** the course
> instead.

Creators only ever see and move lessons from their own products.

### The student site carries the real logo

The **log in**, **register** and **start learning** pages now open with the full
**PILOT ACADEMY** logo above the form, the same size and treatment as the admin
sign-in page — so both front doors look like the same product.

In the header, "Academy" is no longer blue. That blue made sense when the mark
was blue; since the mark turned amber the two had been fighting each other. The
name is now one colour and the amber mark carries the accent.

### The version is on screen

The bottom of the left menu now shows which version you are on, e.g. **v2.0.0**.
Click it to come straight here and read what changed.

### Six improvements to the student site

**Students can see how long things take.** Courses and lessons now show their
running time — on the home page, the course page, the lesson list and the lesson
itself — and the course page shows how much is **left**, e.g. "3 / 8 lessons ·
32 min left". Someone with fifteen minutes before a shift can now tell whether
to start.

> **This needs you to fill it in.** Right now most lessons have no duration set,
> so no time is shown. Add **Duration (minutes)** when you write a lesson. Leave
> the *course* duration empty and the academy adds its lessons up for you.

**A search box.** Students can search courses and lessons by name from the home
page. Only published material is ever returned — drafts stay invisible.

**A better video player for uploaded videos.** Speed controls (Normal, 1.25×,
1.5×, 2×) for people re-watching to revise, and the volume and speed a student
picks are remembered for the next lesson. YouTube lessons already had this.

**Quizzes say what they cost before you start.** The knowledge check now shows
"5 questions · 10 min limit · 2 attempts left" up front, instead of springing
the timer and attempt limit on someone after they begin.

**Finishing a course means something.** Completing the last lesson used to drop
students back on the home page with no acknowledgement. They now get a **Course
complete** card with what they finished, their certificate if there is one, and
the next course to take.

**It can be used without a mouse or with a screen reader.** A skip link, proper
labels on progress bars, and text alternatives everywhere a ✓ or a colour was
the only signal — so a student who uses a screen reader can complete training
their employer requires.

### Two fixes for students on phones

**Certificates can be opened on a phone again.** The link was there on a laptop
but disappeared on a narrow screen, so a student who had earned a certificate
had no way to reach it from their phone. The header now shows a 🎓 on small
screens and the full **Certificates** link from tablet size up.

**Uploaded lesson videos no longer take over the screen on iPhones.** They used
to jump to full screen the moment a student pressed play, hiding the lesson and
the quiz underneath. They now play in place, as YouTube lessons already did.

### A a tidier menu


The logo now reads **PILOT ACADEMY**, not just **PILOT** — in the admin panel,
on certificates and in the emails the academy sends. "ACADEMY" sits under the
Pilot wordmark in grey, the same way the other Pilot products write their name
(PILOT Video, PILOT IOT, PILOT Autoconductor), so the academy looks like part
of the family rather than a separate thing.

On the **sign-in page** the logo is now bigger than the **Sign in** heading, so
the brand leads and the heading reads as the label it is. The logo also shows
correctly in dark mode — until now the colour and white versions were both drawn,
one on top of the other, and neither was the size it was meant to be.

In the left menu, **Changelog** is now called **What's new**, and every section
has its own icon instead of five sharing one.

### Nudge students who have gone quiet

The **Students who have gone quiet** panel now has a **Send reminder** button on
each row, and a bulk version for chasing several at once. The student gets an
email with a personal link that signs them straight in and drops them on the
page showing their next unfinished lesson — no password to remember.

The panel also shows a **Reminded** column, so you can see who has already been
chased and when. Nobody can be reminded twice within 7 days, whoever clicks: a
bulk send skips anyone still inside that window and tells you how many it
skipped. Reminders appear in a student's **Activity** tab alongside their
lessons.

Being reminded is not progress, so a student stays on the list until they
actually come back and finish something.

## August 2026

### Students can pick up where they left off

The student home page now opens with a **Continue where you left off** card: the
next unfinished lesson in the course they are partway through, with a progress
bar and a **Resume** button. It appears only once someone has started something
and disappears when they finish. It works for signed-out visitors too, whose
progress is kept in their browser session.

### Export learner progress to a spreadsheet

**Users** → **Export learner progress** downloads a CSV: name, email, partner,
lessons completed, valid certificates, last activity, last login and join date.
Learners only, and revoked certificates are not counted.

### What students actually open

A **Most opened courses** chart shows which courses students opened over the
last 90 days. Certificates only tell you what people finished; this shows what
drew them in, including a course everyone starts and nobody completes.

### Copy a whole course in one click

**Courses** → the row → **Duplicate**. You get a new **Draft** with the same
lessons, videos, text, quiz questions and answers, and the same final quiz
settings and question bank — ready to edit into the next course rather than
built from scratch.

What is _not_ copied: student progress, quiz attempts and certificates. Those
belong to the course people actually took.

The copy is genuinely separate — editing a question in it never changes the
original — and it keeps the same product, so the creator who owned the original
owns the copy too.

### Publish or unpublish several at once

Tick the checkboxes in **Courses** or **Lessons** and use **Publish** or
**Unpublish** from the bulk menu. Setting up a new course no longer means
clicking through its lessons one at a time.

Bulk publishing a course still respects the rule that protects students: a
course with no published lesson is skipped rather than published empty, and you
are told by name which ones were left behind.

### Is the academy being used?

A new **Student activity** chart shows lessons finished and sign-ins per day
over the last 30 days. The panel could tell you totals before, but never
whether things were picking up or going quiet.

### The panel now warns you when content is broken

Four things could quietly break a course with nothing in the panel saying so.
A **Content needing attention** panel appears at the top of the dashboard when
any of them is true, with a link straight to the fix:

- A **question with no correct answer ticked**. Grading can never succeed, so
  the student is stuck on that lesson however they answer. Saving a question
  without a correct answer is now refused outright.
- A course whose **final quiz is on but has no questions** — students who finish
  every lesson reach a dead button.
- A **published course with no published lessons** — an empty course page.
- A **published lesson with no quiz** — it can never be marked finished, which
  also blocks the final quiz.

The panel is not shown at all when there is nothing wrong. Creators see only
their own products' problems.

### Which lessons students struggle with

A new **Lessons students struggle with** panel ranks lessons by how often
students fail their quiz, worst first. A high fail rate is usually a confusing
question rather than a weak student, so treat it as a list worth rereading.

Only graded attempts count — an attempt still in progress is not a failure —
and staff attempts are excluded, as everywhere else. A lesson needs at least
three attempts before it appears, so one bad day does not put it top.

### A dashboard worth opening

The panel's home screen now answers three questions at a glance.

- **The numbers.** Students and how many are active, lesson completions,
  published courses and lessons, certificates issued with the average score.
- **Progress by partner company.** A bar per partner showing how much of the
  published material their students have worked through. A partner with no
  students shows as zero rather than disappearing.
- **Students who have gone quiet.** Anyone who started a course, completed
  nothing for two weeks, and has no certificate — the one list on the page
  worth acting on. Finishing late still counts as finished, so nobody who
  earned a certificate appears here.

Every figure counts **learners only**: admins and creators never appear, not
their lesson completions and not the certificates they pick up while previewing
a final quiz.

**Courses** and **Lessons** now carry a number in the sidebar when something is
still in draft — hover it for an explanation. Creators only ever see a count of
their own products' work.

Press **Ctrl+K** (or **⌘K**) anywhere in the panel to search courses, lessons
and people. Results show the status, course or partner alongside the name, so
two people called the same thing are still telling apart. The sidebar can also
be collapsed now, and tables use the full width of the window.

### Product owners can write their own training — the Creator role

Product managers and module owners can now maintain the training for their own
product without being given the run of the platform. There are three roles:

- **Admin** — everything, exactly as before.
- **Creator** — courses and lessons for their assigned products only.
- **Learner** — takes courses on the student site; no panel access.

A new **Products** section lists the products/modules your training is about
(GARM, PTM, …). Assign a course to a product, then give someone the **Creator**
role in **Users** and tick the products they own. When they sign in they see
**Courses**, **Lessons** and **Media items** holding only their own products'
content — they can build and publish it just as you would.

Creators cannot see or edit another product's content (the rows are not in
their list, and a direct link does not work either), cannot open **Users**,
**Companies**, **Products** or **Certificates**, and cannot see student numbers
or the certificate report on the dashboard. Reports and the partner "Certified"
counts still measure **learners only**, so nobody is counted as a student just
because they have an account.

**Users** now opens on tabs — **All users**, **Admins**, **Creators**,
**Learners** — each with a count, so staff and students are no longer mixed
together in one list. There are **Role** and **Products** columns and filters
to match.

Reports count **learners only**, everywhere. Two places used to include staff
and no longer do: the dashboard's **Lesson completions** total (admins and
creators complete lessons while checking a course) and **Certificates issued by
course** (previewing a final quiz issues the admin a real certificate). Neither
counts towards student numbers now.

Nothing changed for your existing accounts: everyone who was an admin is still
an admin, everyone else is a learner. Existing courses have no product yet, so
they stay admin-only until you set one — that is the switch that hands a course
to its product owner.

### Courses are published on purpose, not by accident

Creating a course no longer puts it in front of students. Every new course
starts as a **Draft** that only admins can see, so you can build it — lessons,
quizzes and all — in peace, and open it to students when you are ready.

- **Courses** and **Lessons** now both have a **Status** column: **Draft**,
  **Published** or **Archived**, with **Publish** / **Unpublish** on each row.
- The **course** is the gate. New lessons stay **Published** as before, since
  nobody can see them until their course is published — so there is no extra
  step when you build a course. Use a lesson's **Unpublish** when you want to
  hold just that one back from a live course.
- Ready to go live? Click **Publish** on the course's row. A course needs at
  least one published lesson first, so students never open an empty course.
- Need it offline again? Click **Unpublish** — it returns to Draft and vanishes
  from the student site. **Nothing is deleted**: lessons, questions, student
  progress and issued certificates all stay exactly as they were.
- Retiring something for good? Open **Edit** and set **Status** to **Archived**.
  Also hidden from students, with its content and certificates kept.
- Drafts and archived items are hidden everywhere students could reach them —
  the course list, the lesson list inside a course, search engines' sitemap,
  quizzes and direct links — not just on the home page. As an admin you can
  still open a draft on the public site to preview it; a yellow bar reminds you
  students cannot see it.

Nothing changed for your existing courses and lessons: everything students could
see before this update is still **Published**.

## July 2026

### Certificate check no longer shows a score

The public verification page now shows only what matters to whoever is
checking: who it was issued to, the course, the date, and whether it is
**Valid** or **Revoked**. Passing is pass/fail, so the percentage is gone.
The score is still kept and visible to you in **Certificates**.

### Admins can preview the final quiz

Admins can now open and take any course's final quiz without finishing the
lessons first — useful for checking questions and the certificate. It issues a
real certificate to the admin account (revoke it afterwards under **Certificates**
if it was only a test). Students still have to finish every lesson.

### Final quiz link inside lessons

Students can now open the final quiz straight from the **Lessons** panel while
inside a lesson — no need to go back to the course page. If some lessons are
still unfinished, it shows how many are left. On the last lesson, the button
becomes **Take the final quiz**.

### Admin guide in the panel

A step-by-step guide for managers is now available in the panel under
**Guide** — how to build a course, run the final quiz, and issue certificates.

### Manage the final quiz and certificates in the panel

- A new **Certificates** section lists every issued certificate, with
  **Download**, **Resend email**, **Regenerate PDF**, **Revoke**, and **Export CSV**.
- Each course has a **Final quiz & certificate** settings block and a
  **Final questions** tab to build the question bank.
- The **Companies** list shows a **Certified** column (how many members passed).
- The dashboard shows **Certificates issued by course**.

### Cleaner certificate

Removed the score line from the printed certificate. The score is still kept
and shown on the verification page, in the student's account, and in the admin.

### Final quiz and certificates launched

After finishing every lesson in a course, students take a course-wide **final
quiz**. Passing (80% by default) automatically issues a **PDF certificate**
with the student's name, the course, the date, a unique number, and a QR code.
Certificates are emailed, available in the student's account, and can be
checked by anyone on a public verification page.
