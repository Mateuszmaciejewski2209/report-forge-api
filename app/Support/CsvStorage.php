<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CsvStorage
{
    public function storeUpload(User $user, UploadedFile $file): string
    {
        $token = Str::uuid()->toString();
        $relative = "uploads/{$user->id}/{$token}.csv";

        Storage::disk('local')->putFileAs(
            "uploads/{$user->id}",
            $file,
            "{$token}.csv",
        );

        return $token;
    }

    public function absolutePath(User $user, string $token): ?string
    {
        $relative = "uploads/{$user->id}/{$token}.csv";

        if (! Storage::disk('local')->exists($relative)) {
            return null;
        }

        return Storage::disk('local')->path($relative);
    }

    public function moveToReport(User $user, string $token, string $reportCode): ?string
    {
        $from = "uploads/{$user->id}/{$token}.csv";
        $to = "reports/{$reportCode}.csv";

        if (! Storage::disk('local')->exists($from)) {
            return null;
        }

        Storage::disk('local')->makeDirectory('reports');
        Storage::disk('local')->move($from, $to);

        return $to;
    }
}
