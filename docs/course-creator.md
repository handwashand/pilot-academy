# Creating a course in Pilot Academy

How any agent or person builds a course in the live academy
(academy.pilot-gps.com), from an idea to a draft the owner can publish.

**Credentials are not in this file.** They are in `course-creator.local.md` at
the repository root, which git ignores. If it is missing, ask the owner. Never
copy them into this file, a commit, a PR or the shared skills library.

## The rules

1. **Teach PILOT, not the industry.** Every lesson teaches one PILOT screen,
   setting or report, using the labels the software actually shows. Industry
   context, such as why idling wastes fuel, gets a sentence or two at most.
2. **Source everything from docs.pilot-gps.com.** Use the current guide
   version. Link the pages you used at the end of each lesson. Never describe a
   screen the docs do not show. If the docs are unclear, say so in the lesson
   or leave it out.
3. **Reuse before you write.** Search the existing lessons first. A lesson
   added to a second course is shared, not copied, so add it rather than
   rewrite it.
4. **Every course has a final quiz.** Turn on **Enable final quiz**, set the
   **Pass mark (%)** to **80** (the academy's standard), and fill the bank (see
   *Final questions*).
5. **Every question has 3 to 6 answer options** and exactly one correct
   answer, unless you deliberately choose **Multiple select**. Vary the position
   of the correct answer. Don't always put it first.
6. **The course stays a draft.** The owner reviews and publishes it.
7. **Found something broken in production? Record it.** Add it to *Open
   threads* in `AGENTS.md` and tell the owner. Fix it in code if the fix is
   code. Do not work around it silently.

## Steps

### 1. Study what exists

- **Content → Courses**: read one or two published courses in the same area,
  for their length, level and lesson size. A course is usually 6 to 14 lessons.
- **Content → Lessons**: set **Per page** to 50 and read every page. The search
  box does not filter when typed into by automation, so read the list instead.
  Note lessons that fit, and drafts (don't reuse a draft without saying so).

### 2. Research the docs

- The contents page, `docs.pilot-gps.com/contents.html`, lists every topic.
  Pick only the pages the course needs.
- Take the exact menu paths, field names, buttons and column names.
- Screenshots live at `docs.pilot-gps.com/images/<name>.png`. Pages usually
  have `…_screen.png` (full screens) and `…_custom.png` (details). Open each
  image before using it: some are 30-pixel icons, not screenshots.

### 3. Create the course

**Content → Courses → New course**:
- **Course title**, **Short description** (one or two sentences on what the
  learner will be able to do), **URL slug** (short, lower case, hyphens).
- **Level**: the closest match. **Written in**: English.
- **Product / module** and **Who it's for**: fill them if they are clear,
  otherwise leave them for the owner.
- Leave **Status** as Draft and click **Create**.

### 4. Write each new lesson

**Content → Lessons → New lesson**:
- **Title**: names the task ("Configuring Driving Quality Rules").
- **Slug**, and a **Short summary** of one sentence.
- **Lesson text**, in this order: why it matters (2 to 3 sentences), where it
  is in PILOT, numbered steps, what each field or column means, how to act on
  it, then a *Source:* line linking the docs pages.
  - Use **Heading 2** for sections, lists for steps and fields, **bold** for
    UI labels.
  - **Keep lists one level deep.** No list inside a list. Deep nesting can
    break saving (see `course-creator.local.md`).
- **Images**: put the cursor at the end of a heading, click the paperclip
  (**Attach files**), upload the screenshot, write the **Alt text** (what the
  screen shows), then **Submit**. Use one or two per lesson, at the step they
  illustrate.
- **Knowledge check**: 3 questions, each with 3 to 6 options and one
  **Correct**. A lesson is finished only by passing it, so test the lesson's
  key fact, not trivia.
- **Status**: the form defaults to Published. That is fine, because the course
  is the gate and stays a draft.

### 5. Put the lessons in the course

On the course's **Lessons** tab: **Add existing lesson** for each lesson, in
teaching order. Reused, foundational lessons go first, then the new ones. The
tab loads only once it is scrolled into view.

### 6. Final questions

On the course's **Final questions** tab:
1. **Add all lesson questions** brings in every lesson's knowledge check.
2. **New final question**: add at least 6 to 8 *scenario* questions that join
   several lessons ("A vehicle's fuel is above its norm. What next?").
   **Single choice**, 4 options.
3. Large banks can also go in through **Upload from Excel**: download the
   example file first.

Then, on the course form, turn on **Enable final quiz**, set **Pass mark (%)**
to 80, and click **Save changes**.

### 7. Check and hand over

- Re-open each new lesson: the text, images and questions are all saved.
- The Lessons tab lists every lesson in order. The Final questions count is
  what you expect.
- Report to the owner: the course link, the lessons (new and reused), the
  question count, anything you could not confirm from the docs, and any
  production problem you found.

## Doing it with browser automation

Agents usually drive the panel with Playwright. Points that save time:
- Run Playwright scripts from `.playwright-mcp/`, which is git-ignored.
  Delete the scripts and downloaded images when you finish.
- **Rich text**: dispatch a `paste` event carrying `text/html` onto
  `.ProseMirror`. Headings and lists survive.
- **Create button**: two buttons are named Create on a lesson page (the
  cover-image picker has one). Use the form's
  `button[type=submit]` with the text Create.
- **Answer fields**: `getByRole('textbox', { name: /^Answer/ })`. A plain label
  match also finds the "Answer options" section.
- **Add existing lesson**: its picker does not respond to typing. Call it
  through Livewire on the `LessonsRelationManager` component instead:
  `mountAction('attach', {}, {table: true})`, then
  `set('mountedActions.0.data.recordId', id)`, then `callMountedAction()`.
- A creator account cannot preview a draft course on the student site (404).
  Check images by opening their `/storage/...` address.
