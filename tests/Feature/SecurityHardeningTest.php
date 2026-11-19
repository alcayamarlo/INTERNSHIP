<?php

use App\Enums\UserRole;
use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/* ---------- Browser protection headers ---------- */

it('sends the protection headers on public and signed-in pages', function () {
    config(['security.csp_enforce' => false]); // the default; a local .env may already enforce
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    foreach ([$this->get(route('login')), $this->actingAs($admin)->get(route('admin.dashboard'))] as $response) {
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy')
            ->assertHeader('Content-Security-Policy-Report-Only')
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Strict-Transport-Security'); // plain http
    }
});

it('enforces the CSP only when switched on, and sends HSTS only over HTTPS', function () {
    config(['security.csp_enforce' => true]);
    $this->get(route('login'))->assertHeader('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');

    $this->get(str_replace('http://', 'https://', route('login')))
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('allows the Pusher hosts of the configured cluster for live notifications', function () {
    config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.options.cluster' => 'ap1']);
    expect(SecurityHeaders::policy())->toContain("connect-src 'self' wss://ws-ap1.pusher.com https://sockjs-ap1.pusher.com https://stats.pusher.com");

    config(['broadcasting.default' => 'log']);
    expect(SecurityHeaders::policy())->toContain("connect-src 'self';")->not->toContain('pusher');
});

it('lets pages load code only from this site and the listed hosts', function () {
    config(['broadcasting.default' => 'log']);
    $policy = SecurityHeaders::policy();

    expect($policy)->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'")
        ->toContain("form-action 'self'")
        ->toContain("base-uri 'self'");

    preg_match_all('#https?://[a-z0-9.-]+#', $policy, $hosts);
    expect(array_values(array_unique($hosts[0])))->toEqualCanonicalizing([
        'https://cdn.jsdelivr.net', 'https://cdnjs.cloudflare.com', 'https://fonts.googleapis.com', 'https://fonts.gstatic.com',
    ]);
});

/* ---------- Uploaded images lose their metadata ---------- */

/** A JPEG with an EXIF block (orientation "rotate 90°" and a fake GPS text) and a comment. */
function shJpegWithExif(int $width = 40, int $height = 20): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 30, 30));
    ob_start();
    imagejpeg($image, null, 90);
    $jpeg = (string) ob_get_clean();

    $tiff = 'II'.pack('v', 42).pack('V', 8)
        .pack('v', 1).pack('vvVvv', 0x0112, 3, 1, 6, 0).pack('V', 0)
        .'GPS-SECRET-10.3157N-123.8854E';
    $app1 = "Exif\0\0".$tiff;
    $comment = 'CAMERA-SECRET-PHONE';
    $segments = "\xFF\xE1".pack('n', strlen($app1) + 2).$app1."\xFF\xFE".pack('n', strlen($comment) + 2).$comment;

    return substr($jpeg, 0, 2).$segments.substr($jpeg, 2); // right after the SOI marker
}

/** A PNG with a text chunk. */
function shPngWithText(): string
{
    $image = imagecreatetruecolor(30, 30);
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();
    $data = "Location\0GPS-SECRET-PNG";
    $chunk = pack('N', strlen($data)).'tEXt'.$data.pack('N', crc32('tEXt'.$data));

    return substr($png, 0, 33).$chunk.substr($png, 33); // after the signature and IHDR
}

it('stores uploaded JPEG and PNG images without camera or location data', function () {
    Storage::fake('local');
    $jpeg = shJpegWithExif();
    expect($jpeg)->toContain('GPS-SECRET')->toContain('CAMERA-SECRET');

    $path = app(FileUploadService::class)->upload(UploadedFile::fake()->createWithContent('certificate.jpg', $jpeg), 'competencies/1', 'cert', 'local');
    $stored = Storage::disk('local')->get($path);

    expect($path)->toEndWith('.jpg')
        ->and($stored)->not->toContain('GPS-SECRET')->not->toContain('CAMERA-SECRET')->not->toContain('Exif')
        ->and(getimagesizefromstring($stored)['mime'])->toBe('image/jpeg');
    if (function_exists('exif_read_data')) {
        // The photo was turned upright, since its orientation lived in the removed EXIF data.
        expect(array_slice(getimagesizefromstring($stored), 0, 2))->toBe([20, 40]);
    }

    $png = app(FileUploadService::class)->upload(UploadedFile::fake()->createWithContent('id.png', shPngWithText()), 'documents/1', 'id', 'local');
    expect(Storage::disk('local')->get($png))->not->toContain('GPS-SECRET-PNG')
        ->and(getimagesizefromstring(Storage::disk('local')->get($png))[0])->toBe(30);
});

it('stores PDFs exactly as uploaded', function () {
    Storage::fake('local');
    $pdf = "%PDF-1.4\n% a small test document\n%%EOF\n";

    $path = app(FileUploadService::class)->upload(UploadedFile::fake()->createWithContent('resume.pdf', $pdf), 'portfolios/1', 'doc', 'local');

    expect(Storage::disk('local')->get($path))->toBe($pdf);
});
