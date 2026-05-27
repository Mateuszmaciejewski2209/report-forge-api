<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Support\Reports\ReportTemplateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:128'],
            'brand_color' => ['nullable', 'string', 'max:64'],
            'pdf_template' => ['nullable', 'string', Rule::in(ReportTemplateRegistry::allowedIds())],
        ]);

        $user->update($validated);

        return response()->json([
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        if ($user->company_logo && Storage::disk('public')->exists($user->company_logo)) {
            Storage::disk('public')->delete($user->company_logo);
        }

        $path = $validated['logo']->store('logos/'.$user->id, 'public');
        $user->update(['company_logo' => $path]);

        return response()->json([
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function deleteLogo(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->company_logo && Storage::disk('public')->exists($user->company_logo)) {
            Storage::disk('public')->delete($user->company_logo);
        }

        $user->update(['company_logo' => null]);

        return response()->json([
            'user' => new UserResource($user->fresh()),
        ]);
    }
}
