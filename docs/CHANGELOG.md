# What's new — Pilot Academy

A short log of changes to the academy, newest first, in plain language.
Add a new entry here whenever something visible to admins or students changes.

## August 2026

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

**Users** now shows a **Role** column and a **Products** column, and can be
filtered by either.

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

*This changelog is also shown in the admin panel under **Changelog**.*
