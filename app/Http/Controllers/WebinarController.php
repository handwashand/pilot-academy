<?php

namespace App\Http\Controllers;

use App\Models\Webinar;
use Illuminate\Http\Request;

class WebinarController extends Controller
{
    /**
     * What is coming up, and what can be watched back. Drafts stay out of both
     * lists; an editor previews one from the panel.
     */
    public function index(Request $request)
    {
        return view('academy.webinars', [
            'upcoming' => Webinar::published()->upcoming()->with(['product', 'contentTranslations'])->get(),
            'past' => Webinar::published()->past()->with(['product', 'contentTranslations'])->limit(30)->get(),
        ]);
    }

    public function show(Request $request, Webinar $webinar)
    {
        abort_unless($webinar->isVisibleTo($request->user()), 404);

        $webinar->load(['product', 'coverMediaItem', 'contentTranslations']);

        return view('academy.webinar', ['webinar' => $webinar]);
    }
}
