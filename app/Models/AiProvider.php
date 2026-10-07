<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    /** @var array<string, array{label: string, url: string, model: string}> */
    public const PROVIDERS = [
        // Descript translates the speech in lesson videos, not text; it has no
        // model setting either. Its token is used by DescriptClient.
        self::DESCRIPT => [
            'label' => 'Descript',
            'url' => 'https://descriptapi.com/v1',
            'model' => '',
        ],
        // DeepL is not a chat model: it has no model setting, and its address
        // depends on the key (see deeplSettings()).
        self::DEEPL => [
            'label' => 'DeepL',
            'url' => 'https://api.deepl.com',
            'model' => '',
        ],
        self::CHATGPT => [
            'label' => 'ChatGPT',
            'url' => 'https://api.openai.com/v1/chat/completions',
            'model' => 'gpt-4o-mini',
        ],
        self::DEEPSEEK => [
            'label' => 'DeepSeek',
            'url' => 'https://api.deepseek.com/chat/completions',
            'model' => 'deepseek-chat',
        ],
    ];

    protected $fillable = ['provider', 'enabled', 'api_key', 'model'];

    /** Never serialised, so it cannot reach a response or a log by accident. */
    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'api_key' => 'encrypted',
        ];
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
    public static function saved(string $provider): ?self
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
}
