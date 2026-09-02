# Pilot Academy — Admin Guide

*How to build a course, run the final test, and give out certificates — step by step, in plain English.*

**For:** course managers · **You need:** an admin login · **Where:** your site address + `/admin`

---

## 1. Log in and find your way

Open your site address in a browser and add `/admin` at the end. Sign in with your admin email and password.

You land on the **Dashboard** — a quick summary of how many students you have, how many lessons they finished, and how many certificates were issued. The menu on the left takes you to every part of the academy.

---

## 2. The menu at a glance

Each item in the left menu has one job.

| Menu item | What it is for |
|---|---|
| **Courses** | Create courses and turn on the final quiz and certificate. |
| **Lessons** | Add lessons to a course: a video, text, and quiz questions. |
| **Products** | The products/modules your training is about (GARM, PTM, …) and who owns each one. |
| **Users** | Everyone with an account, and their role. See each student's progress and certificates. |
| **Companies** | Partner companies. Group students and see how many are certified. |
| **Certificates** | Every certificate that was issued. Download, resend, or revoke them. |
| **Media items** | A shared image library you can reuse as lesson covers. |
| **Dashboard** | The home screen: overall numbers, progress by partner, and who has gone quiet. |

---

## 3. Quick start: from empty course to certificate

The path is always the same: **make a course → fill it with lessons → turn on the final quiz → publish it → the student passes → they get a certificate.**

### Step 1 · Create a course

1. In the left menu, click **Courses**.
2. At the top right, click **New course**.
3. Fill in the main fields:
   - **Course title** — the name, e.g. "Pilot Basics".
   - **Short description** — one or two sentences. Students see this on the course card.
   - **Duration (minutes)** — optional, but **students now see it**, so it is worth
     filling in. If you leave the course duration empty, the academy adds up the
     durations of its lessons instead. If nothing has a duration, no time is shown.
   - The other fields (level) are optional.
4. Click **Create**.

> New courses are saved as a **Draft**. Students cannot see a draft — you publish it yourself in Step 4, once the lessons are in place.

> Leave the **Final quiz & certificate** box off for now. We come back to it in Step 3, once the course has lessons.

### Step 2 · Add lessons

1. In the left menu, click **Lessons**, then **New lesson**.
2. Choose the **Course** and give the lesson a title.
3. Add a video: paste a link into **YouTube link**, or use **Or upload a video file**.
4. Write the lesson content in **Lesson text**.
5. Set **Duration (minutes)** — how long the lesson takes. Students see this on
   the lesson card, in the lesson list and on the course page ("32 min left"),
   and it is how they decide whether to start now or come back later. It also
   adds up into the course total when the course has no duration of its own.
6. Scroll to **Knowledge check (quiz)**. Click **Add question**, type the question, add answer options, and turn on **Correct** for the right one. Add as many questions as you want.
7. Leave **Status** on **Published** and click **Create**.
8. Repeat for every lesson in the course.

> New lessons are **Published** straight away — and that is safe, because
> students still see nothing until the *course* is published in Step 4. So there
> is no second step to remember.
>
> The **Lessons** list has the same **Status** column and **Publish** /
> **Unpublish** actions as Courses, for when you want to hold one lesson back
> while the rest of the course is live, or retire an old one with **Archived**.
> Unpublishing never deletes anything — the text, video, questions and student
> progress all stay.

#### Put the lessons in the right order, and reuse ones you already have

Open the course (**Courses** → **Edit**) and look at the **Lessons** tab. It
lists every lesson in that course.

- **To change the order**, drag a row by the handle on its left. The order you
  set here is the order students work through, on the course page, in the lesson
  sidebar and in "Continue where you left off". There is nothing to save.
- **To reuse a lesson that already exists**, click **Add existing lesson**,
  search for it and move it across. The search shows which course each lesson is
  in now.

> **A lesson lives in one course only.** Adding an existing lesson to this course
> takes it *out* of the course it is in now — it is a move, not a copy. Its
> video, text, questions and students' progress all travel with it. If you want
> the same material in two courses, use **Duplicate** on the course instead.

> You can only do this once the course exists, so on a brand-new course finish
> **Create** first and the **Lessons** tab appears.

**Screen — Knowledge check (quiz):**

| Field | Example |
|---|---|
| Question | What screen do you land on after signing in? |
| Answer + **Correct** | The Dashboard — ✅ Correct |
| Answer | The billing page |

> Each lesson has its own short quiz. A student must answer it correctly to finish the lesson. Finishing **all** lessons is what unlocks the final quiz.

### Step 3 · Turn on the final quiz

1. Go to **Courses** and open your course (click **Edit**).
2. Find **Final quiz & certificate** and turn on **Enable final quiz**.
3. Set **Pass mark (%)** — the score needed to pass. The default is 80%.
4. Optional — **Questions per attempt**: how many questions to show, chosen at random each try. Leave empty to use all of them.
5. Optional — **Max attempts**: how many tries a student gets. Leave empty for unlimited.
6. Click **Save**.
7. Open the **Final questions** tab and click **Add all lesson questions**. This fills the quiz with every question from your lessons.

**Screen — Final quiz & certificate (Course edit):**

| Field | Example |
|---|---|
| Enable final quiz | ✅ on |
| Pass mark (%) | 80 |
| Questions per attempt | empty = all |
| Max attempts | empty = unlimited |
| Certificate background (optional) | your image, or leave empty |

**Screen — Final questions (bank):**

| Question | Type | Source | Options |
|---|---|---|---|
| What screen do you land on after signing in? | Single | Getting Started | 4 |
| Which area shows your vehicles? | Single | The Live Map | 4 |
| Pick the two report types you can schedule | Multiple | Course-only | 4 |

> You can remove any question you don't want with **Remove from bank**, or write a new one with **New final question** (a final question can have one *or several* correct answers).

### Step 4 · Publish the course

Until now the course has been a **Draft** — you can see it, students cannot.

1. Go to **Courses**. The **Status** column shows where each course stands:
   **Draft**, **Published** or **Archived**.
2. On your course's row, click **Publish** and confirm.

The course is live for students straight away. Two things to know:

- A course needs at least one **published lesson** before it can be published —
  otherwise students would open an empty course. If **Publish** says *"Add a
  lesson first"*, go to **Lessons** and publish one.
- Changed your mind? Click **Unpublish**. The course goes back to Draft and
  disappears from the student site. Nothing is deleted — lessons, questions,
  progress and certificates all stay exactly as they were.

**Screen — Courses:**

| Course | Status | Actions |
|---|---|---|
| Pilot Quick Start | Published | Unpublish · Edit |
| Introduction to Leadership | Draft | Publish · Edit |
| Old Onboarding 2024 | Archived | Edit |

> Want to retire a course for good? Open **Edit** and set **Status** to
> **Archived**. Like a draft, it disappears from the student site, but its
> certificates and history stay intact.

> **Doing several at once:** tick the checkboxes on the left of **Courses** or
> **Lessons**, then pick **Publish** or **Unpublish** from the bulk menu that
> appears. A course with no published lesson is skipped rather than published
> empty, and you are told which ones by name.

### Step 5 · Try it as a student

Open the public site (your address *without* `/admin`), go to the course, and finish the lessons. When every lesson is done, the **Final quiz** button unlocks. Pass it to see the whole flow for yourself.

> As an admin you can also open a **draft** course on the public site to preview
> it — a yellow bar reminds you students cannot see it yet.

### Step 6 · The certificate

When a student passes, the certificate is made **automatically**: a PDF with their name, the course, the date, a unique number, and a QR code. The student gets it by email and in their account. You can find every certificate under **Certificates**.

---

## 4. How to set things up

### Edit the final quiz questions

Open the course → **Final questions** tab. Here you can **Attach lesson question** (add one existing question), **Add all lesson questions** (add them all at once), or **New final question** (write a control question that lives only in the final).

> **Careful:** a question that came from a lesson is the *same* question — editing it here also changes it inside that lesson. To only take it out of the final quiz, use **Remove from bank** (this does not delete it).

### Upload your own certificate background

Open the course → **Final quiz & certificate** → **Certificate background**. Upload a full-page image (A4, landscape). The name, course, date, number, and QR code are placed on top automatically. Leave it empty to use the built-in framed design.

### Build a course from an existing one

If the next course is much like one you already have, do not start from an empty
page. **Courses** → the row → **Duplicate**.

You get a new **Draft** carrying everything that makes up the course: lessons in
order with their videos and text, every quiz question and answer, and the final
quiz settings and question bank. Rename it, edit what differs, publish.

> Student progress and certificates are **not** copied — they belong to the
> course people actually took. And the copy is fully independent: editing a
> question in it never changes the original.

### Let a product owner write their own training

Product managers can maintain the training for their own product without you
handing over the whole platform. They get the **Creator** role.

There are three roles:

| Role | What they can do |
|---|---|
| **Admin** | Everything: all courses, users, partners, certificates and settings. |
| **Creator** | Courses and lessons **for their assigned products only**. Nothing else. |
| **Learner** | Takes courses on the student site. No panel access at all. |

To set one up:

1. **Products** → **New product**. Name it after the product, e.g. `GARM`.
2. **Users** → open the person → set **Role** to **Creator**.
3. A **Products / modules** box appears. Tick the products they own — one or
   several — and save.

That is it. When they sign in to `/admin` they see **Courses**, **Lessons** and
**Media items**, holding only their own products' content. They can build
courses and lessons and publish them, exactly as you would.

What a Creator **cannot** do:

- See or edit any other product's courses or lessons — the rows are not in
  their list, and a direct link does not work either.
- Open **Users**, **Companies**, **Products** or **Certificates**.
- See the dashboard's student numbers or certificate report.
- Delete anything in bulk, reorder the course list, or delete shared media.

> Assigning a course to a product is what hands it over. A course with no
> product stays admin-only, which is where every course you already have
> starts — set the **Product / module** field on a course to pass it on.

> Need to take the rights away? Set their **Role** back to **Learner**, or
> untick the product. Their courses and lessons stay exactly as they are.

### Add a partner company and a student

1. Go to **Companies** → **New company** → enter the name → **Create**.
2. Then **Users** → **New user** → enter name and email, pick the **Company**, and leave **Is admin** off → **Create**.

Students can also sign up themselves on the site.

---

## 5. How to check things

### Find people by role

**Users** opens on tabs across the top: **All users**, **Admins**, **Creators**
and **Learners**, each with a count. Click one to see only those accounts. There
are also **Role** and **Product / module** filters if you want to combine them
with a company.

> **Learners** is the tab that matters for reporting: every number on the
> dashboard, and the **Certified** column under Companies, counts learners only.
> Admin and creator accounts are never included — not their lesson completions,
> and not the certificates they pick up while previewing a final quiz.

### Who finished a course

Go to **Users** and open a student. The **Completed lessons** tab lists every lesson they finished. The **Dashboard** shows overall activity across all students.

### Read the Dashboard

The home screen has three panels:

| Panel | What it tells you |
|---|---|
| **Content needing attention** | Only appears when something is broken for students. Each row links straight to the fix. |
| The numbers along the top | Students and how many are active, lesson completions, published courses and lessons, certificates issued with the average score. |
| **Progress by partner company** | How much of the published material each partner's students have worked through. A partner with no students yet reads as 0. |
| **Students who have gone quiet** | Started a course, completed nothing for two weeks, and no certificate. The one list here worth acting on — **Send reminder** emails them a link straight back to their next lesson, or **Open** to see the person. |
| **Lessons students struggle with** | Lessons ranked by how often students fail the quiz. |
| **Student activity** | Lessons finished and sign-ins per day over the last 30 days — whether use is picking up or going quiet. |
| **Most opened courses** | What students opened in the last 90 days, finished or not. |

**What "content needing attention" catches:**

| Warning | What the student sees |
|---|---|
| Question with no correct answer | A quiz they can never pass, however they answer |
| Final quiz on, question bank empty | A **Final quiz** button that leads nowhere |
| Published course, no published lessons | An empty course page |
| Published lesson with no quiz | A lesson they can never mark finished |

> The first one used to be easy to create by accident — forget to tick
> **Correct** and the lesson becomes impossible. The form now refuses to save a
> question without a correct answer, and this panel lists any that slipped
> through before.

> **Lessons students struggle with** is usually telling you a question is
> unclear, not that the students are weak. A lesson needs at least three graded
> attempts before it appears, and attempts still in progress do not count
> against it.

### Chase a student who stopped

On **Students who have gone quiet**, click **Send reminder** on their row — or
tick several and use the bulk **Send reminders**. They get an email with a
personal link that signs them in and opens their next lesson.

The **Reminded** column shows who has already been chased. Nobody can be
reminded twice inside 7 days, no matter who clicks, so two managers working the
same list cannot double up — a bulk send simply skips anyone inside that window
and tells you how many it skipped.

> A reminder is not progress. The student stays on the list until they come back
> and finish a lesson, which is what you actually wanted.

> Reminders are recorded in the student's **Activity** tab, so you can see the
> full history: what they did, and when someone chased them.

> Every number counts **students only**. Your own account and your creators are
> never included, even though you complete lessons and pick up certificates
> while checking a course.

> Someone who finished — even long after they started — never appears in the
> quiet list. It is about outstanding work, not slow work.

### Find anything quickly

Press **Ctrl+K** (**⌘K** on a Mac) anywhere in the panel and start typing. It
searches courses, lessons and people. Each result shows its status, course or
partner underneath, so two records with the same name are still telling apart.

A number next to **Courses** or **Lessons** in the left menu means something is
still in draft and invisible to students. Hover it to see what it counts.

### Who got a certificate

Open **Certificates**. Each row shows the student, their company, the course, the score, the date, the number, and whether it is **Valid** or **Revoked**. Use the filters at the top to narrow by course, partner, or status.

### Coverage by partner company

Open **Companies**. The **Certified** column shows how many members have a valid certificate out of the total — for example **4 / 10**.

### Is one certificate real?

Open your site address + `/certificates/` + the certificate number (or scan the QR code on the PDF). The page shows the name, course, date, and status — **Valid** or **Revoked**. Anyone can use it, even without logging in.

---

## 6. What to do if…

| Situation | What to do |
|---|---|
| The student didn't get the email | **Certificates** → the row → **Resend email**. They can also download it from their account. |
| A certificate was issued by mistake | **Certificates** → **Revoke**. Public check shows "Revoked". Changed your mind? **Restore**. |
| The PDF is empty or won't download | **Certificates** → **Regenerate PDF**, then **Download** again. |
| You need a list for a report | **Certificates** → **Export CSV** (open in Excel or Google Sheets). |
| You need everyone's progress, not just certificates | **Users** → **Export learner progress**. One row per student: lessons done, certificates, last activity. |
| A student ran out of attempts | Open the course → **Final quiz & certificate** → raise **Max attempts**, or clear it for unlimited. |
| Students can't find a course | Check the **Status** column in **Courses**. Only **Published** courses appear on the student site — click **Publish** on the row. |
| One lesson is missing from a live course | **Lessons** → check its **Status** → **Publish**. A lesson shows up only if both it *and* its course are published. |
| A creator says their course is missing | Check the course's **Product / module** field. A creator only sees courses filed under a product assigned to them in **Users**. |
| You need to take a course or lesson offline | The row → **Unpublish**. Nothing is lost; publish it again whenever you like. |

---

## 7. Common questions

**Do certificates expire?**
No. They are permanent and are never removed when a course changes.

**Can a student retake the final quiz after passing?**
No. Once they pass, the certificate is issued and the final quiz is locked for them.

**Where does the name on the certificate come from?**
The student types their full name when they start the final quiz, and it is printed exactly as typed. It can't be edited in the admin panel, so if it is wrong, ask your developer to correct it.

**What if email isn't set up on the server?**
The certificate is still created. The student can download it from their account, and you can use **Download** or **Resend email** from Certificates once email works.

**Can I change the pass mark?**
Yes — per course, in the **Final quiz & certificate** section.

**How many certificates can one student get for a course?**
One valid certificate per course.

**Can I test the final quiz without finishing all the lessons?**
Yes. Admins can open and take any course's final quiz straight away (from the course page or the lesson sidebar), even without completing the lessons — handy for checking your questions and certificate. It issues a real certificate to your admin account, which you can revoke afterwards under **Certificates** if it was only a test. Students still have to finish every lesson first.

---

## 8. Words used here

| Term | Meaning |
|---|---|
| Final quiz | The test for the whole course, taken after all lessons are done. |
| Question bank | The set of questions the final quiz picks from. |
| Pass mark | The score a student needs to pass, e.g. 80%. |
| Attempt | One try at the final quiz. |
| Certificate | The PDF proof that a student passed a course. |
| Revoke | Cancel a certificate so it shows as invalid. |
| Partner | A company that your students belong to. |
| Admin | Runs the whole platform. |
| Creator | A product owner who writes the training for their own product(s) only. |
| Learner | A student who takes courses. |
| Product / module | A thing your training is about (GARM, PTM, …). A course belongs to one, and that is what decides which Creator owns it. |
| Draft | A course or lesson you are still working on. Only admins can see it. |
| Published | Live on the student site. Courses and lessons both get there via the **Publish** action in their list. |
| Archived | Retired. Hidden from students, but its content, progress and certificates stay. |

---

*This guide is also shown inside the admin panel under **Guide**. When admin features for courses, the final quiz, or certificates change, update this file (`docs/admin-guide.md`) in the same change.*
