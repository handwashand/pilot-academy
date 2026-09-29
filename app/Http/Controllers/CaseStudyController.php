<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CaseStudyController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $industry = trim((string) $request->query('industry', ''));
        $feature = trim((string) $request->query('feature', ''));
        $difficulty = trim((string) $request->query('difficulty', ''));

        $base = CaseStudy::published();

        $industries = (clone $base)
            ->whereNotNull('industry')
            ->orderBy('industry')
            ->pluck('industry')
            ->filter()
            ->unique()
            ->values();

        $features = (clone $base)
            ->pluck('features_used')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $caseStudies = CaseStudy::published()
            ->with(['product', 'coverMediaItem'])
            ->matchingSearch($term)
            ->when($industry !== '', fn (Builder $query) => $query->where('industry', $industry))
            ->when($feature !== '', fn (Builder $query) => $query->whereJsonContains('features_used', $feature))
            ->when($difficulty !== '', fn (Builder $query) => $query->where('difficulty', $difficulty))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('academy.case-studies.index', [
            'caseStudies' => $caseStudies,
            'term' => $term,
            'industry' => $industry,
            'feature' => $feature,
            'difficulty' => $difficulty,
            'industries' => $industries,
            'features' => $features,
            'difficulties' => CaseStudy::difficultyLabels(),
        ]);
    }

    public function show(Request $request, CaseStudy $caseStudy)
    {
        abort_unless($caseStudy->isVisibleTo($request->user()), 404);

        $caseStudy->load(['product', 'coverMediaItem', 'diagramMediaItem']);

        return view('academy.case-studies.show', [
            'caseStudy' => $caseStudy,
            'relatedLessons' => $caseStudy->relatedLessons(),
        ]);
    }
}
