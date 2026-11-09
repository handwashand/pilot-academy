# AI course generator

Provider-independent course drafts on top of the ChatGPT and DeepSeek rows
already stored under Settings → Integrations. There is no second API key and
no separate client for translation.

## What is built

- `AiProvider` records, per known model, whether it can return text and JSON,
  its context and output limits, and an estimated token price. Document input
  and web search are off for every model: the app does not ask a model to
  fetch a URL.
- `App\Services\Llm\ChatClient` sends one chat-completions request to the
  provider's fixed address, with the encrypted token from `ai_providers`. It
  records token counts and an estimated cost on `ai_usages`. The prompt, the
  answer and the token are not logged.
- `App\Actions\GenerateCourseWithAi` asks for a JSON course, checks it, and
  on a bad answer asks the **same** provider once more. It then saves a
  **draft** course, lessons and a single-answer knowledge check. Students
  do not see the course until it is published in the usual way.
- Each saved course and lesson gets an `ai_content_revisions` row naming the
  provider and model that produced that save.
- Settings → Integrations stores a default provider, an optional fallback, a
  daily limit and a monthly budget on `ai_generation_settings`. Fallback runs
  only when the setting is on **and** the request ticks it, and only after
  the chosen provider fails to answer (it is down, the token is rejected, or
  the account is out of credit). A bad JSON answer is not a reason to switch.
- Content → Generate course is the screen. It stays hidden until an admin
  ticks **Draft courses with ChatGPT or DeepSeek** on that account. Admins do
  not receive the right automatically. A creator also needs a product they own.
- Each draft is shown two published courses (`SampleCourseStyle`), a different
  pair as more drafts are made. The model is told to match their shape and
  tone and not to copy them. Drafts are left out. A creator only supplies
  courses from their own products.
- Translation still goes through `LlmTranslator`. That client was not rewritten.

Adding another chat provider later means one entry in `AiProvider::PROVIDERS`
(address, models, capabilities) and, if it speaks chat completions, no change
to `GenerateCourseWithAi`.

## Not built yet

These stay out until they are asked for. The client and the revision rows are
the pieces they should call.

1. **Source documents and URLs.** A shared extractor in the app, then the
   extracted text is handed to whichever provider was picked. Models are not
   given a tool to browse.
2. **Separate steps.** Outline, one lesson, and one quiz are further calls
   with the same client. The screen today does the whole draft in one call,
   capped at six lessons, so it can finish inside a web request. This app
   does not run a queue worker.
3. **Regenerate one lesson or quiz** with a different provider. The revision
   table already records each save; the screen for a second pass is not there.
4. **Publish from the generator.** Publishing stays the existing course
   action, so a generated course cannot go live by accident.

## Deploy

`php8.4 artisan migrate --force` creates `ai_usages`, `ai_content_revisions`
and `ai_generation_settings`. No token is copied or rotated.
