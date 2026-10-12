<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CaseStudies\CaseStudyResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Tutorials\TutorialResource;
use App\Filament\Resources\Webinars\WebinarResource;
use App\Models\CaseStudy;
use App\Models\Course;
use App\Models\Tutorial;
use App\Models\Webinar;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/** A content-only home for creators, scoped to their assigned products. */
class CreatorContentOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isCreator();
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $productIds = auth()->user()?->products()->pluck('products.id')->all() ?? [];

        return [
            $this->contentStat(
                __t('admin_nav.courses.nav'),
                Course::query()->whereIn('product_id', $productIds),
                CourseResource::getUrl(),
                'heroicon-m-rectangle-stack',
            ),
            $this->contentStat(
                __t('admin_nav.case_studies.nav'),
                CaseStudy::query()->whereIn('product_id', $productIds),
                CaseStudyResource::getUrl(),
                'heroicon-m-briefcase',
            ),
            $this->contentStat(
                __t('admin_nav.tutorials.nav'),
                Tutorial::query()->whereIn('product_id', $productIds),
                TutorialResource::getUrl(),
                'heroicon-m-play-circle',
            ),
            $this->contentStat(
                __t('admin_nav.webinars.nav'),
                Webinar::query()->whereIn('product_id', $productIds),
                WebinarResource::getUrl(),
                'heroicon-m-video-camera',
            ),
        ];
    }

    private function contentStat(string $label, Builder $query, string $url, string $icon): Stat
    {
        $total = (clone $query)->count();
        $published = (clone $query)->where('status', Course::STATUS_PUBLISHED)->count();
        $drafts = (clone $query)->where('status', Course::STATUS_DRAFT)->count();
        $archived = (clone $query)->where('status', Course::STATUS_ARCHIVED)->count();

        return Stat::make($label, $total)
            ->description(__t('admin_widgets.creator.status', [
                'published' => $published,
                'drafts' => $drafts,
                'archived' => $archived,
            ]))
            ->descriptionIcon($icon)
            // Default grey, like the admin cards: the line already says how many
            // drafts there are, so amber said it a second time in a weaker way.
            ->url($url);
    }
}
