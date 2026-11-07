<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Modules\Locations\Models\State;
use App\Support\Storage\MediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected MediaStorageService $mediaStorage
    ) {}

    /**
     * Show the profile edit form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile ?? $user->profile()->create(['user_id' => $user->id]);
        $states = State::where('is_active', true)->orderBy('name')->get();

        return view('account.profile', [
            'user' => $user,
            'profile' => $profile,
            'states' => $states,
        ]);
    }

    /**
     * Update user profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone,' . $user->id],
            'bio' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', 'string', 'max:50'],
            'seeking' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state_code' => ['required', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'hide_location_details' => ['boolean'],
            'hide_age' => ['boolean'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $validated['display_name'] ?? $validated['name'],
                'bio' => $validated['bio'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'seeking' => $validated['seeking'] ?? null,
                'city' => $validated['city'],
                'state_code' => strtoupper($validated['state_code']),
                'zip_code' => $validated['zip_code'] ?? null,
                'hide_location_details' => $request->boolean('hide_location_details'),
                'hide_age' => $request->boolean('hide_age'),
            ]
        );

        return back()->with('status', 'Profile successfully updated.');
    }

    /**
     * Upload and sanitize avatar with EXIF stripping.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:8192'],
        ]);

        $user = $request->user();
        $stored = $this->mediaStorage->storeSanitizedPhoto(
            file: $request->file('avatar'),
            directory: 'avatars/' . $user->id
        );

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['avatar_path' => $stored['path']]
        );

        return back()->with('status', 'Profile photo uploaded and sanitized (EXIF metadata stripped).');
    }
}
