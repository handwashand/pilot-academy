<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * A text-translation provider an admin has set up under Settings → Integrations.
 *
 * Only the known providers exist, each with a fixed official address, so a
 * saved token can never be sent to a host an admin typed in.
 *
 * Each provider has one shared row (switch, model, and an optional token for
 * all admins or for everyone with the right) and any number of personal rows,
 * one per admin, holding only that admin's own token. withToken() is the one
 * place that decides which token a person gets.
 */
class AiProvider extends Model
{
    public const CHATGPT = 'chatgpt';

    public const DEEPSEEK = 'deepseek';

    public const CLAUDE = 'claude';

    public const DEEPL = 'deepl';

    public const DESCRIPT = 'descript';

    /** @var array<string, array{label: string, url: string, model: string, model_options: array<string, string>, capabilities?: array<string, array<string, mixed>>}> */
    public const PROVIDERS = [
        // Descript translates the speech in lesson videos, not text; it has no
        // model setting either. Its token is used by DescriptClient.
        self::DESCRIPT => [
            'label' => 'Descript',
            'url' => 'https://descriptapi.com/v1',
            'model' => '',
            'model_options' => [],
        ],
        // DeepL is not a chat model: it has no model setting, and its address
        // depends on the key (see deeplSettings()).
        self::DEEPL => [
            'label' => 'DeepL',
            'url' => 'https://api.deepl.com',
            'model' => '',
            'model_options' => [],
        ],
        self::CHATGPT => [
            'label' => 'ChatGPT',
            'url' => 'https://api.openai.com/v1/chat/completions',
            // The middle of the list by price: the default every provider gets
            // (owner's choice, 2026-11-25). A dearer model warns on Integrations.
            'model' => 'gpt-4o',
            // Checked against OpenAI's pricing and model pages on 2026-11-24.
            // GPT-4 Turbo and GPT-3.5 Turbo were dropped: OpenAI no longer
            // prices them.
            'model_options' => [
                'gpt-6-luna' => 'GPT-6 Luna',
                'gpt-6.2-sol' => 'GPT-6.2 Sol',
                'gpt-6.2-astra' => 'GPT-6.2 Astra',
                'gpt-4o-mini' => 'GPT-4o mini',
                'gpt-4o' => 'GPT-4o',
            ],
            // Price estimates, not an invoice. Input then
            // output, US dollars per million tokens. Documents and web search
            // stay off: the app reads source text itself. GPT-6 prices are the
            // rate up to 272,000 input tokens; OpenAI charges more above it.
            // A reasoning model is called differently (see chatOptions()).
            'capabilities' => [
                'gpt-6-luna' => ['text' => true, 'json' => true, 'reasoning' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1050000, 'max_output_tokens' => 128000, 'input_usd_per_million' => 0.10, 'output_usd_per_million' => 0.50],
                'gpt-6.2-sol' => ['text' => true, 'json' => true, 'reasoning' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1050000, 'max_output_tokens' => 128000, 'input_usd_per_million' => 2.00, 'output_usd_per_million' => 10.00],
                'gpt-6.2-astra' => ['text' => true, 'json' => true, 'reasoning' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1050000, 'max_output_tokens' => 128000, 'input_usd_per_million' => 8.00, 'output_usd_per_million' => 40.00],
                'gpt-4o-mini' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 128000, 'max_output_tokens' => 16384, 'input_usd_per_million' => 0.15, 'output_usd_per_million' => 0.60],
                'gpt-4o' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 128000, 'max_output_tokens' => 16384, 'input_usd_per_million' => 2.50, 'output_usd_per_million' => 10.00],
            ],
        ],
        self::DEEPSEEK => [
            'label' => 'DeepSeek',
            'url' => 'https://api.deepseek.com/chat/completions',
            // deepseek-chat and deepseek-reasoner were retired on 2026-07-24;
            // migration 2026_11_24_000001 moves saved rows to deepseek-flash.
            'model' => 'deepseek-flash',
            'model_options' => [
                'deepseek-flash' => 'DeepSeek Flash',
                'deepseek-v4-pro' => 'DeepSeek V4 Pro',
            ],
            // Peak-hour prices, the higher of DeepSeek's two rates, so the
            // budget is never under-counted.
            'capabilities' => [
                'deepseek-flash' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1000000, 'max_output_tokens' => 384000, 'input_usd_per_million' => 0.30, 'output_usd_per_million' => 1.20],
                'deepseek-v4-pro' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1000000, 'max_output_tokens' => 384000, 'input_usd_per_million' => 1.32, 'output_usd_per_million' => 3.96],
            ],
        ],
        // Messages API, not chat completions (LlmTranslator::askClaude). Haiku's
        // price is the short-prompt rate (at or under 100,000 input tokens).
        self::CLAUDE => [
            'label' => 'Claude',
            'url' => 'https://api.anthropic.com/v1/messages',
            'model' => 'claude-sonnet-5-5',
            'model_options' => [
                'claude-haiku-5-5' => 'Claude Haiku 5.5',
                'claude-sonnet-5-5' => 'Claude Sonnet 5.5',
                'claude-opus-5-5' => 'Claude Opus 5.5',
                'claude-fable-5-2' => 'Claude Fable 5.2',
            ],
            'capabilities' => [
                'claude-haiku-5-5' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1000000, 'max_output_tokens' => 128000, 'input_usd_per_million' => 0.10, 'output_usd_per_million' => 0.50],
                'claude-sonnet-5-5' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1000000, 'max_output_tokens' => 128000, 'input_usd_per_million' => 2.00, 'output_usd_per_million' => 10.00],
                'claude-opus-5-5' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1000000, 'max_output_tokens' => 128000, 'input_usd_per_million' => 4.00, 'output_usd_per_million' => 20.00],
                'claude-fable-5-2' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 1000000, 'max_output_tokens' => 128000, 'input_usd_per_million' => 8.00, 'output_usd_per_million' => 40.00],
            ],
        ],
    ];

    /** A personal token: only the admin who added it uses it. */
    public const SCOPE_PERSONAL = 'personal';

    /** A shared token every admin may use. */
    public const SCOPE_ADMINS = 'admins';

    /** A shared token for admins and anyone holding the provider's right (the original behaviour). */
    public const SCOPE_EVERYONE = 'everyone';

    /**
     * Descript continues jobs from a background command with no one signed in,
     * so it could not tell whose personal token a job was started with.
     *
     * @var array<int, string>
     */
    public const SHARED_ONLY = [self::DESCRIPT];

    protected $fillable = ['provider', 'user_id', 'scope', 'added_by', 'enabled', 'api_key', 'model'];

    /** Never serialised, so it cannot reach a response or a log by accident. */
    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * Stored encrypted with APP_KEY. A token saved under a different key cannot
     * be read back — that reads as "no token", so the page asks for it again
     * rather than failing. (The stock `encrypted` cast throws, which took the
     * Integrations page down on a rebuilt local container.)
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return null;
                }
            },
            set: fn (?string $value): ?string => blank($value) ? null : Crypt::encryptString($value),
        );
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * The provider's shared row, or an empty unsaved one. It holds the switch
     * and the model for everyone, and the shared token when there is one.
     */
    public static function for(string $provider): self
    {
        return static::firstOrNew(['provider' => $provider, 'user_id' => null]);
    }

    /** The person's own row for a provider, or an empty unsaved one. */
    public static function personalFor(string $provider, User $user): self
    {
        return static::firstOrNew(['provider' => $provider, 'user_id' => $user->id], ['scope' => self::SCOPE_PERSONAL]);
    }

    /** Whether a shared token's reach includes this person. No one signed in is the system itself (a background command). */
    public function sharedWith(?User $user): bool
    {
        return $this->scope !== self::SCOPE_ADMINS || $user === null || $user->isAdmin();
    }

    /**
     * The token row this person would use, switched on or off: their own token
     * first, then the shared one if its reach includes them. Null when neither
     * exists. A personal row comes back carrying the provider's switch and
     * model (only in memory; it is never saved like that), because those are
     * set once for everyone.
     *
     * Descript and DeepL use this directly: a saved token overrides the
     * server's .env, and the switch here decides.
     */
    public static function withToken(string $provider, ?User $user = null): ?self
    {
        if (! isset(self::PROVIDERS[$provider])) {
            return null;
        }

        $user ??= auth()->user();
        $shared = static::query()->where('provider', $provider)->whereNull('user_id')->first();

        if ($user && ! in_array($provider, self::SHARED_ONLY, true)) {
            $own = static::query()->where('provider', $provider)->where('user_id', $user->id)->first();

            if ($own && filled($own->api_key)) {
                $own->enabled = (bool) $shared?->enabled;
                $own->model = $shared?->model;

                return $own;
            }
        }

        return $shared && filled($shared->api_key) && $shared->sharedWith($user) ? $shared : null;
    }

    /** The token row this person may use right now: switched on and holding a token. */
    public static function usable(string $provider, ?User $user = null): ?self
    {
        $row = static::withToken($provider, $user);

        return $row && $row->enabled ? $row : null;
    }

    /** The token's last four characters, the only part ever shown. */
    public function tokenEnding(): ?string
    {
        return filled($this->api_key) ? mb_substr((string) $this->api_key, -4) : null;
    }

    /**
     * Settings for DeepLClient. A DeepL API Free key ends in ":fx" and only
     * works on the Free host; every other key works on the Pro host. Those are
     * the only two addresses the client accepts.
     *
     * @return array<string, mixed>
     */
    public function deeplSettings(): array
    {
        return [
            'enabled' => (bool) $this->enabled,
            'key' => $this->api_key,
            'base_url' => str_ends_with((string) $this->api_key, ':fx')
                ? 'https://api-free.deepl.com'
                : 'https://api.deepl.com',
        ];
    }

    public function label(): string
    {
        return self::PROVIDERS[$this->provider]['label'] ?? $this->provider;
    }

    public function endpoint(): string
    {
        return self::PROVIDERS[$this->provider]['url'];
    }

    public function modelName(): string
    {
        return filled($this->model) ? $this->model : self::PROVIDERS[$this->provider]['model'];
    }

    /**
     * What the named model can do, or null when this app has no record of it.
     * An unknown model gets the classic request shape.
     *
     * @return array<string, mixed>|null
     */
    public static function capabilitiesFor(string $provider, string $model): ?array
    {
        if (! isset(self::PROVIDERS[$provider])) {
            return null;
        }

        $capabilities = self::PROVIDERS[$provider]['capabilities'][$model] ?? null;

        return is_array($capabilities) ? $capabilities : null;
    }

    /**
     * How many times the provider's default model a chosen model costs, input
     * and output prices together, rounded to a whole number; null when it is
     * not dearer or a price is unknown. Integrations warns with it.
     */
    public static function costMultiple(string $provider, ?string $model): ?int
    {
        $default = self::PROVIDERS[$provider]['model'] ?? '';
        $price = fn (?array $capabilities): float => (float) (($capabilities['input_usd_per_million'] ?? 0) + ($capabilities['output_usd_per_million'] ?? 0));

        if (blank($model) || $model === $default) {
            return null;
        }

        $base = $price(self::capabilitiesFor($provider, $default));
        $chosen = $price(self::capabilitiesFor($provider, $model));

        if ($base <= 0 || $chosen <= $base * 1.2) {
            return null;
        }

        return max(2, (int) round($chosen / $base));
    }

    /** The display name of a provider's model, or its id when the app has none. */
    public static function modelLabel(string $provider, string $model): string
    {
        return self::PROVIDERS[$provider]['model_options'][$model] ?? $model;
    }

    /**
     * The chat-completions settings for one translation request. OpenAI's
     * reasoning models take max_completion_tokens (max_tokens is deprecated
     * and refused by them), a reasoning effort, and no temperature. Everything else keeps the classic fields.
     *
     * @return array<string, mixed>
     */
    public static function chatOptions(string $provider, string $model, float $temperature, ?int $maxTokens = null): array
    {
        if (self::capabilitiesFor($provider, $model)['reasoning'] ?? false) {
            // Reasoning counts against the limit, so leave it room.
            return array_filter([
                'reasoning_effort' => 'low',
                'max_completion_tokens' => $maxTokens === null ? null : max($maxTokens, 32000),
            ], fn ($value): bool => $value !== null);
        }

        return array_filter([
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ], fn ($value): bool => $value !== null);
    }
}
