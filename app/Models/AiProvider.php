<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A text-translation provider an admin has set up under Settings → Configs.
 *
 * Only the two known providers exist, each with a fixed official address, so a
 * saved token can never be sent to a host an admin typed in.
 */
class AiProvider extends Model
{
    public const CHATGPT = 'chatgpt';

    public const DEEPSEEK = 'deepseek';

    /** @var array<string, array{label: string, url: string, model: string}> */
    public const PROVIDERS = [
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

    /** The provider if it is switched on and has a token; otherwise null. */
    public static function usable(string $provider): ?self
    {
        if (! isset(self::PROVIDERS[$provider])) {
            return null;
        }

        $row = static::where('provider', $provider)->first();

        return $row && $row->enabled && filled($row->api_key) ? $row : null;
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
