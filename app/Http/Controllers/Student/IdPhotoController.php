<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\FileUploadService;
use App\Services\ImageProcessor;
use App\Support\UploadDisks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Optional 2x2 picture on the Documents tab. Stored on the private disk as a 600x600 JPEG and only
 * served through show(). It never counts toward Documents or profile completion.
 */
class IdPhotoController extends Controller
{
    public const SIZE = 600;

    public function __construct(private ImageProcessor $images, private FileUploadService $files) {}

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Always the signed-in student's own record.
        $student = Auth::user()->student()->firstOrFail();
        $request->validate([
            'id_photo' => ImageProcessor::rules(),
            'crop_x' => ['nullable', 'numeric', 'between:0,1'],
            'crop_y' => ['nullable', 'numeric', 'between:0,1'],
            'crop_size' => ['nullable', 'numeric', 'between:0.05,1'],
        ], ImageProcessor::messages('id_photo'));

        $crop = $request->filled(['crop_x', 'crop_y', 'crop_size'])
            ? ['x' => (float) $request->input('crop_x'), 'y' => (float) $request->input('crop_y'), 'size' => (float) $request->input('crop_size')]
            : null;
        $previous = $student->id_photo_path;
        $path = $this->images->storeSquare($request->file('id_photo'), 'id_photo', 'id-photos', UploadDisks::private(), self::SIZE, $crop);

        try {
            $student->update(['id_photo_path' => $path]);
        } catch (\Throwable $exception) {
            Storage::disk(UploadDisks::private())->delete($path);
            throw $exception;
        }

        if ($previous) {
            $this->files->delete($previous, UploadDisks::private());
        }

        return $this->respond($request, $student, 'Your 2x2 picture was saved. Generate your resume again so it appears on it.');
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $student = Auth::user()->student()->firstOrFail();
        $previous = $student->id_photo_path;

        if ($previous) {
            $student->update(['id_photo_path' => null]);
            $this->files->delete($previous, UploadDisks::private());
        }

        return $this->respond($request, $student, $previous ? '2x2 picture removed.' : 'There is no 2x2 picture to remove.');
    }

    /** The only way to open a 2x2 picture; see StudentPolicy::viewIdPhoto. */
    public function show(Student $student)
    {
        $this->authorize('viewIdPhoto', $student);
        $disk = Storage::disk(UploadDisks::private());
        abort_unless($student->id_photo_path && $disk->exists($student->id_photo_path), 404);

        return $disk->response($student->id_photo_path, null, [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    public static function url(Student $student): ?string
    {
        if (! $student->id_photo_path || ! Storage::disk(UploadDisks::private())->exists($student->id_photo_path)) {
            return null;
        }

        // The random file name changes on every upload, so it doubles as the cache-buster.
        return route('students.id-photo', $student, false).'?v='.substr(md5($student->id_photo_path), 0, 12);
    }

    private function respond(Request $request, Student $student, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'url' => self::url($student->fresh())]);
        }

        return redirect()->to(route('student.profile.edit').'#documents')->with('success', $message);
    }
}
