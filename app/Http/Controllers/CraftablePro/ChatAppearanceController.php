<?php

declare(strict_types=1);

namespace App\Http\Controllers\CraftablePro;

use App\Http\Controllers\Controller;
use App\Http\Requests\CraftablePro\UpdateChatAppearanceRequest;
use App\Settings\ChatSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChatAppearanceController extends Controller
{
    public function index(ChatSettings $settings): Response
    {
        abort_unless(Gate::allows('craftable-pro.settings.edit'), 403);

        return Inertia::render('ChatAppearance/Index', [
            'chatAppearance' => [
                'public_color'   => $settings->public_color,
                'internal_color' => $settings->internal_color,
            ],
        ]);
    }

    public function update(ChatSettings $settings, UpdateChatAppearanceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $settings->public_color   = $validated['public_color'];
        $settings->internal_color = $validated['internal_color'];
        $settings->save();

        return redirect()->back()->with(['message' => ___('craftable-pro', 'Settings successfully updated')]);
    }
}
