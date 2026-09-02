# What's new — Pilot Academy

A short log of changes to the academy, newest first, in plain language.
Add a new entry here whenever something visible to admins or students changes.

## 1.2.0 — September 2026

### The student site carries the real logo

The **log in**, **register** and **start learning** pages now open with the full
**PILOT ACADEMY** logo above the form, the same size and treatment as the admin
sign-in page — so both front doors look like the same product.

In the header, "Academy" is no longer blue. That blue made sense when the mark
was blue; since the mark turned amber the two had been fighting each other. The
name is now one colour and the amber mark carries the accent.

### The version is on screen

The bottom of the left menu now shows which version you are on, e.g. **v1.2.0**.
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

---

_This changelog is also shown in the admin panel under **Changelog**._
