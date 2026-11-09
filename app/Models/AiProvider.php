<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * A text-translation provider an admin has set up under Settings → Integrations.
 *
 * Only the two known providers exist, each with a fixed official address, so a
 * saved token can never be sent to a host an admin typed in.
 */
class AiProvider extends Model
{
    public const CHATGPT = 'chatgpt';

    public const DEEPSEEK = 'deepseek';

    public const DEEPL = 'deepl';

    public const DESCRIPT = 'descript';

    /**
     * Chat providers the course generator may use. DeepL and Descript are not
     * chat models, so they stay out of this list.
     *
     * @var array<int, string>
     */
    public const GENERATION = [self::CHATGPT, self::DEEPSEEK];

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
            'model' => 'gpt-4o-mini',
            'model_options' => [
                'gpt-4o-mini' => 'GPT-4o mini',
                'gpt-4o' => 'GPT-4o',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ],
            // Estimates for the generation budget, not an invoice. Input then
            // output, US dollars per million tokens. Documents and web search
            // stay off: the app reads source text itself.
            'capabilities' => [
                'gpt-4o-mini' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 128000, 'max_output_tokens' => 16384, 'input_usd_per_million' => 0.15, 'output_usd_per_million' => 0.60],
                'gpt-4o' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 128000, 'max_output_tokens' => 16384, 'input_usd_per_million' => 2.50, 'output_usd_per_million' => 10.00],
                'gpt-4-turbo' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 128000, 'max_output_tokens' => 4096, 'input_usd_per_million' => 10.00, 'output_usd_per_million' => 30.00],
                'gpt-3.5-turbo' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 16385, 'max_output_tokens' => 4096, 'input_usd_per_million' => 0.50, 'output_usd_per_million' => 1.50],
            ],
        ],
        self::DEEPSEEK => [
            'label' => 'DeepSeek',
            'url' => 'https://api.deepseek.com/chat/completions',
            'model' => 'deepseek-chat',
            'model_options' => [
                'deepseek-chat' => 'DeepSeek Chat',
                'deepseek-reasoner' => 'DeepSeek Reasoner',
            ],
            'capabilities' => [
                'deepseek-chat' => ['text' => true, 'json' => true, 'documents' => false, 'web_search' => false, 'context_tokens' => 128000, 'max_output_tokens' => 8192, 'input_usd_per_million' => 0.14, 'output_usd_per_million' => 0.28],
                // Reasoner does not accept a JSON response format.
                'deepseek-reasoner' => ['text' => true, 'json' => false, 'documents' => false, 'web_search' => false, 'context_tokens' => 128000, 'max_output_tokens' => 8192, 'input_usd_per_million' => 0.55, 'output_usd_per_million' => 2.19],
            ],
        ],
    ];

    protected $fillable = ['provider', 'enabled', 'api_key', 'model'];

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

    /** The saved row, or an empty unsaved one. */
    public static function for(string $provider): self
    {
        return static::firstOrNew(['provider' => $provider]);
    }

    /**
     * The row if an admin saved a token for it, switched on or off; otherwise
     * null. Descript and DeepL use this: a saved token overrides the server's
     * .env, and its switch decides, so switching off here really is off.
     */
    public static function withToken(string $provider): ?self
    {
        $row = static::where('provider', $provider)->first();

        return $row && filled($row->api_key) ? $row : null;
    }

    /** The provider if it is switched on and has a token; otherwise null. */
    public static function usable(string $provider): ?self
    {
        if (! isset(self::PROVIDERS[$provider])) {
            return null;
        }

        $row = static::where('provider', $provider)->first();

        return $row && $row->enabled && filled($row->api_key) ? $row : null;
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
     * An unknown model is not offered for course generation.
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

    public static function modelSupportsJson(string $provider, string $model): bool
    {
        return (bool) (self::capabilitiesFor($provider, $model)['json'] ?? false);
    }

    /** Switched on, with a token, and at least one model that can return JSON. */
    public static function availableForGeneration(string $provider): bool
    {
        return in_array($provider, self::GENERATION, true)
            && self::usable($provider) !== null
            && self::modelsForGeneration($provider) !== [];
    }

    /**
     * Models an instructor may pick for course generation.
     *
     * @return array<string, string>
     */
    public static function modelsForGeneration(string $provider): array
    {
        if (! isset(self::PROVIDERS[$provider])) {
            return [];
        }

        $options = self::PROVIDERS[$provider]['model_options'] ?? [];

        return array_filter(
            $options,
            fn (string $label, string $model): bool => self::modelSupportsJson($provider, $model),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /** The saved model when it can return JSON, otherwise the first that can. */
    public static function preferredGenerationModel(string $provider): ?string
    {
        $models = array_keys(self::modelsForGeneration($provider));

        if ($models === []) {
            return null;
        }

        $saved = self::for($provider)->modelName();

        return in_array($saved, $models, true) ? $saved : $models[0];
    }

    /**
     * Providers an instructor can generate with right now.
     *
     * @return array<string, string>
     */
    public static function generationChoices(): array
    {
        $choices = [];

        foreach (self::GENERATION as $provider) {
            if (self::availableForGeneration($provider)) {
                $choices[$provider] = self::PROVIDERS[$provider]['label'];
            }
        }

        return $choices;
    }
}
