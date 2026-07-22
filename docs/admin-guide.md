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
| **Users** | Your students (and other admins). See each person's progress and certificates. |
| **Companies** | Partner companies. Group students and see how many are certified. |
| **Certificates** | Every certificate that was issued. Download, resend, or revoke them. |
| **Media items** | A shared image library you can reuse as lesson covers. |
| **Dashboard** | The home screen: overall numbers and certificates by course. |

---

## 3. Quick start: from empty course to certificate

The path is always the same: **make a course → fill it with lessons → turn on the final quiz → the student passes → they get a certificate.**

### Step 1 · Create a course

1. In the left menu, click **Courses**.
2. At the top right, click **New course**.
3. Fill in the main fields:
   - **Course title** — the name, e.g. "Pilot Basics".
   - **Short description** — one or two sentences. Students see this on the course card.
   - The other fields (level, duration) are optional.
4. Turn on **Published** so students can see the course.
5. Click **Create**.

> Leave the **Final quiz & certificate** box off for now. We come back to it in Step 3, once the course has lessons.

### Step 2 · Add lessons

1. In the left menu, click **Lessons**, then **New lesson**.
2. Choose the **Course** and give the lesson a title.
3. Add a video: paste a link into **YouTube link**, or use **Or upload a video file**.
4. Write the lesson content in **Lesson text**.
5. Scroll to **Knowledge check (quiz)**. Click **Add question**, type the question, add answer options, and turn on **Correct** for the right one. Add as many questions as you want.
6. Turn on **Published** and click **Create**.
7. Repeat for every lesson in the course.

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

### Step 4 · Try it as a student

Open the public site (your address *without* `/admin`), go to the course, and finish the lessons. When every lesson is done, the **Final quiz** button unlocks. Pass it to see the whole flow for yourself.

### Step 5 · The certificate

When a student passes, the certificate is made **automatically**: a PDF with their name, the course, the date, a unique number, and a QR code. The student gets it by email and in their account. You can find every certificate under **Certificates**.

---

## 4. How to set things up

### Edit the final quiz questions

Open the course → **Final questions** tab. Here you can **Attach lesson question** (add one existing question), **Add all lesson questions** (add them all at once), or **New final question** (write a control question that lives only in the final).

> **Careful:** a question that came from a lesson is the *same* question — editing it here also changes it inside that lesson. To only take it out of the final quiz, use **Remove from bank** (this does not delete it).

### Upload your own certificate background

Open the course → **Final quiz & certificate** → **Certificate background**. Upload a full-page image (A4, landscape). The name, course, date, number, and QR code are placed on top automatically. Leave it empty to use the built-in framed design.

### Add a partner company and a student

1. Go to **Companies** → **New company** → enter the name → **Create**.
2. Then **Users** → **New user** → enter name and email, pick the **Company**, and leave **Is admin** off → **Create**.

Students can also sign up themselves on the site.

---

## 5. How to check things

### Who finished a course

Go to **Users** and open a student. The **Completed lessons** tab lists every lesson they finished. The **Dashboard** shows overall activity across all students.

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
| A student ran out of attempts | Open the course → **Final quiz & certificate** → raise **Max attempts**, or clear it for unlimited. |

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
| Published | A switch that makes a course or lesson visible to students. |

---

*This guide is also shown inside the admin panel under **Guide**. When admin features for courses, the final quiz, or certificates change, update this file (`docs/admin-guide.md`) in the same change.*
