<?php

namespace App\Models\Concerns;

use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * A course or lesson whose slug changes keeps its old address working: the old
 * slug is stored in slug_redirects, and a link that still uses it is sent on
 * (301) to the same page under the new slug. A form posted to an old address
 * is served as it is, so nothing a student submits is lost. Giving a record a
 * slug that used to redirect makes that address live again.
 *
 * The using model defines slugRedirectType(): 'course' or 'lesson'.
 */
trait RemembersOldSlugs
{
    public static function bootRemembersOldSlugs(): void
    {
        static::updated(function (Model $model): void {
            if (! $model->wasChanged('slug')) {
                return;
            }

            $type = static::slugRedirectType();
            $old = (string) $model->getOriginal('slug');

            // The new slug is a real address now, never a redirect.
            SlugRedirect::where('model_type', $type)->where('slug', $model->slug)->delete();

            if ($old !== '') {
                SlugRedirect::updateOrCreate(['model_type' => $type, 'slug' => $old], ['model_id' => $model->getKey()]);
            }
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return parent::resolveRouteBinding($value, $field)
            ?? static::followOldSlug($value, $field);
    }

    /**
     * The record an old slug points to, or null for a real 404. On a page
     * visit, the browser is sent to the new address instead (the exception).
     */
    public static function followOldSlug(mixed $value, ?string $field, ?callable $scope = null): ?Model
    {
        if (($field ?? 'slug') !== 'slug' || ! is_string($value) || $value === '') {
            return null;
        }

        $type = static::slugRedirectType();
        $id = SlugRedirect::where('model_type', $type)->where('slug', $value)->value('model_id');
        $query = static::query()->whereKey($id);

        if ($scope) {
            $scope($query);
        }

        $model = $id ? $query->first() : null;

        if (! $model || $model->slug === $value) {
            return $model;
        }

        $request = request();

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            throw new HttpResponseException(redirect()->to(static::addressWithSlug($value, $model->slug, $type), 301));
        }

        return $model;
    }

    /** The requested address with this record's segment swapped for its new slug. */
    private static function addressWithSlug(string $old, string $new, string $type): string
    {
        $request = request();
        $segments = $request->segments();
        $after = $type === 'course' ? 'courses' : 'lessons';

        foreach ($segments as $i => $segment) {
            if ($segment === $old && ($segments[$i - 1] ?? null) === $after) {
                $segments[$i] = $new;

                break;
            }
        }

        $query = $request->getQueryString();

        return url(implode('/', array_map('rawurlencode', $segments))).($query ? '?'.$query : '');
    }
}
