<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Program;
use App\Services\Chat\ProjectRagChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(
        private readonly ProjectRagChatService $chat,
    ) {}

    public function index(Request $request): Response
    {
        $organization = $request->user()->primaryOrganization();
        abort_unless($organization, 404);

        $session = $this->chat->sessionFor($request->user(), $organization);
        $session->load(['messages' => fn ($q) => $q->latest()->limit(50)]);

        $programs = $organization->programs()
            ->withCount('applications')
            ->orderBy('name')
            ->get()
            ->map(fn (Program $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'applications_count' => $p->applications_count,
            ]);

        return Inertia::render('Chat/Index', [
            'session' => [
                'id' => $session->id,
                'program_id' => $session->program_id,
            ],
            'messages' => $session->messages->sortBy('created_at')->values()->map(fn (ChatMessage $m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'sources' => $m->sources,
                'confidence' => $m->confidence,
                'program_id' => $m->program_id,
                'created_at' => $m->created_at?->toIso8601String(),
            ]),
            'programs' => $programs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $request->user()->primaryOrganization();
        abort_unless($organization, 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
        ]);

        if (! empty($validated['program_id'])) {
            abort_unless(
                $organization->programs()->where('id', $validated['program_id'])->exists(),
                403
            );
        }

        $session = $this->chat->sessionFor($request->user(), $organization);

        $this->chat->ask(
            $session,
            $organization,
            $request->user(),
            $validated['message'],
            $validated['program_id'] ?? null,
        );

        return redirect()->route('ask.index');
    }

    public function clear(Request $request): RedirectResponse
    {
        $organization = $request->user()->primaryOrganization();
        abort_unless($organization, 404);

        $session = $this->chat->sessionFor($request->user(), $organization);
        $session->messages()->delete();

        return redirect()->route('ask.index');
    }
}
