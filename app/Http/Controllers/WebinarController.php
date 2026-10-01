<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvent;
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

        ActivityEvent::record($request->user(), ActivityEvent::TYPE_WEBINAR_OPENED, $webinar->title, $request->path(), $webinar);

        return view('academy.webinar', ['webinar' => $webinar]);
    }

    public function join(Request $request, Webinar $webinar)
    {
        abort_unless($webinar->isVisibleTo($request->user()) && filled($webinar->join_url), 404);

        ActivityEvent::record($request->user(), ActivityEvent::TYPE_WEBINAR_JOINED, $webinar->title, $request->path(), $webinar);

        return redirect()->away($webinar->join_url);
    }

    public function recording(Request $request, Webinar $webinar)
    {
        abort_unless($webinar->isVisibleTo($request->user()) && $webinar->hasRecording(), 404);

        ActivityEvent::record($request->user(), ActivityEvent::TYPE_WEBINAR_RECORDING_OPENED, $webinar->title, $request->path(), $webinar);

        return redirect()->away($webinar->recording_url);
    }
}
