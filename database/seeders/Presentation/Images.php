<?php

namespace Database\Seeders\Presentation;

/**
 * Placeholder pictures for the presentation data, drawn with GD (no internet needed): initials avatars, company
 * logos, office / building photos and certificate images. Every picture is deterministic for the same inputs, so
 * running the seeder again rewrites the same files instead of adding new ones.
 */
final class Images
{
    /** Brand-friendly background colours (r, g, b). */
    private const PALETTE = [
        [122, 1, 14], [31, 78, 121], [46, 125, 91], [199, 96, 20], [88, 61, 143], [0, 121, 140],
        [158, 45, 78], [64, 82, 102], [134, 98, 9], [29, 104, 160], [112, 53, 24], [37, 99, 77],
    ];

    public static function fontBold(): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
    }

    public static function font(): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
    }

    public static function serifBold(): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSerif-Bold.ttf');
    }

    /** @return array{0: int, 1: int, 2: int} */
    public static function color(int $seed): array
    {
        return self::PALETTE[abs($seed) % count(self::PALETTE)];
    }

    // ------------------------------------------------------------------ avatar

    /** A round-cornered square with the person's initials, 400x400 PNG. */
    public static function avatar(string $name, int $seed): string
    {
        $size = 400;
        $img = self::canvas($size, $size);
        [$r, $g, $b] = self::color($seed);
        self::gradient($img, 0, 0, $size, $size, [$r, $g, $b], [min(255, $r + 45), min(255, $g + 45), min(255, $b + 45)]);

        $initials = self::initials($name);
        self::centered($img, 150, 262, imagecolorallocate($img, 255, 255, 255), self::fontBold(), $initials, $size);

        return self::png($img);
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn ($part) => $part !== ''));
        $letters = mb_substr($parts[0] ?? 'U', 0, 1).(count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '');

        return mb_strtoupper($letters);
    }

    // ------------------------------------------------------------------ portrait

    /**
     * An illustrated head-and-shoulders profile picture, 400x400 PNG: a soft background, shoulders in a shirt, a
     * face with eyes, brows and a smile, and hair. Skin, hair, shirt and background vary with the seed, and the
     * hair style with the gender, so a group of students does not look alike.
     */
    public static function portrait(string $gender, int $seed): string
    {
        $size = 400;
        $img = self::canvas($size, $size);
        $pick = fn (array $list, int $shift) => $list[(abs($seed) * 7 + $shift * 13) % count($list)];

        $skin = $pick([[241, 206, 170], [224, 172, 128], [198, 142, 98], [166, 112, 76], [128, 84, 56], [250, 220, 190]], 1);
        $hair = $pick([[28, 22, 20], [52, 36, 28], [86, 58, 38], [20, 18, 24], [112, 80, 52], [64, 52, 46]], 2);
        $shirt = $pick([[122, 1, 14], [31, 78, 121], [46, 125, 91], [64, 82, 102], [199, 96, 20], [88, 61, 143], [240, 240, 244], [37, 99, 77]], 3);
        $background = $pick([[214, 226, 238], [236, 226, 214], [222, 236, 226], [236, 218, 224], [226, 224, 240], [238, 236, 212]], 4);

        self::gradient($img, 0, 0, $size, $size, $background, array_map(fn ($v) => max(0, $v - 26), $background));
        $skinC = imagecolorallocate($img, ...$skin);
        $shade = imagecolorallocate($img, ...array_map(fn ($v) => max(0, $v - 28), $skin));
        $hairC = imagecolorallocate($img, ...$hair);
        $shirtC = imagecolorallocate($img, ...$shirt);
        $female = strcasecmp($gender, 'female') === 0;
        $style = $seed % 3; // hair style within the gender

        // hair behind the head (long hair falls over the shoulders)
        if ($female && $style !== 1) {
            imagefilledellipse($img, 200, 190, 250, 280, $hairC);
            imagefilledrectangle($img, 78, 190, 322, 330, $hairC);
        }

        // shoulders and shirt
        imagefilledellipse($img, 200, 420, 360, 250, $shirtC);
        imagefilledrectangle($img, 20, 330, 380, 400, $shirtC);
        // neck
        imagefilledrectangle($img, 172, 262, 228, 340, $shade);
        imagefilledellipse($img, 200, 340, 82, 56, $shade);
        // collar
        $collar = imagecolorallocate($img, 255, 255, 255);
        imagefilledpolygon($img, [168, 336, 200, 372, 232, 336, 212, 330, 200, 346, 188, 330], $collar);

        // head
        imagefilledellipse($img, 200, 190, 164, 196, $skinC);
        imagefilledellipse($img, 118, 196, 26, 40, $skinC); // ears
        imagefilledellipse($img, 282, 196, 26, 40, $skinC);

        // hair on top
        if ($female) {
            imagefilledellipse($img, 200, 118, 188, 130, $hairC);
            imagefilledpolygon($img, [112, 150, 140, 104, 200, 96, 270, 120, 288, 160, 240, 130, 190, 128, 150, 140], $hairC);
            if ($style === 1) { // a bob
                imagefilledellipse($img, 120, 200, 46, 130, $hairC);
                imagefilledellipse($img, 280, 200, 46, 130, $hairC);
            }
            if ($style === 2) { // a bun
                imagefilledellipse($img, 200, 66, 56, 56, $hairC);
            }
        } else {
            imagefilledellipse($img, 200, 126, 176, 112, $hairC);
            imagefilledrectangle($img, 118, 126, 282, 150, $hairC);
            imagefilledpolygon($img, [118, 150, 132, 132, 150, 150, 120, 176], $hairC);
            imagefilledpolygon($img, [282, 150, 268, 132, 250, 150, 280, 176], $hairC);
            if ($style === 2) { // a side part
                imagefilledellipse($img, 160, 104, 120, 60, $hairC);
            }
        }
        // forehead line cut so the face shows under the hair
        imagefilledellipse($img, 200, 168, 140, 70, $skinC);
        imagefilledrectangle($img, 130, 150, 270, 168, $skinC);

        // eyes, brows, nose, smile
        $white = imagecolorallocate($img, 255, 255, 255);
        $dark = imagecolorallocate($img, 36, 28, 28);
        foreach ([164, 236] as $x) {
            imagefilledellipse($img, $x, 196, 26, 17, $white);
            imagefilledellipse($img, $x, 196, 12, 12, $dark);
            imagesetthickness($img, 4);
            imageline($img, $x - 15, 176, $x + 14, 174, $hairC);
            imagesetthickness($img, 1);
        }
        imagesetthickness($img, 3);
        imageline($img, 200, 200, 196, 226, $shade);
        imageline($img, 196, 226, 206, 228, $shade);
        imagesetthickness($img, 4);
        imagearc($img, 200, 236, 54, 34, 20, 160, imagecolorallocate($img, 150, 70, 70));
        imagesetthickness($img, 1);
        // a little cheek colour
        $blush = imagecolorallocatealpha($img, 230, 110, 100, 100);
        imagefilledellipse($img, 148, 226, 26, 16, $blush);
        imagefilledellipse($img, 252, 226, 26, 16, $blush);

        return self::png($img);
    }

    // ------------------------------------------------------------------ company logo

    /** A simple mark: a coloured rounded tile, a shape, the first letters of the name and a thin accent. 512x512 PNG. */
    public static function logo(string $company, int $seed): string
    {
        $size = 512;
        $img = self::canvas($size, $size);
        [$r, $g, $b] = self::color($seed);
        imagefilledrectangle($img, 0, 0, $size, $size, imagecolorallocate($img, 255, 255, 255));
        $tile = imagecolorallocate($img, $r, $g, $b);
        self::roundedRect($img, 36, 36, $size - 36, $size - 36, 70, $tile);

        $light = imagecolorallocatealpha($img, 255, 255, 255, 96);
        match ($seed % 3) {
            0 => imagefilledellipse($img, 390, 130, 190, 190, $light),
            1 => imagefilledpolygon($img, [330, 80, 460, 80, 460, 210], $light),
            default => imagefilledrectangle($img, 330, 80, 460, 210, $light),
        };

        $white = imagecolorallocate($img, 255, 255, 255);
        $words = preg_split('/\s+/u', trim($company)) ?: [];
        $mark = mb_strtoupper(mb_substr($words[0] ?? 'C', 0, 1).mb_substr($words[1] ?? '', 0, 1));
        self::centered($img, 150, 300, $white, self::fontBold(), $mark, $size);
        imagefilledrectangle($img, 156, 336, 356, 342, $white);
        self::centered($img, 30, 404, $white, self::font(), mb_strtoupper(mb_substr($words[0] ?? '', 0, 14)), $size);

        return self::png($img);
    }

    // ------------------------------------------------------------------ office / building photos

    /** 0: the building from the street, 1: an open office, 2: a meeting room. 1200x800 JPEG. */
    public static function officePhoto(string $company, int $variant, int $seed): string
    {
        $w = 1200;
        $h = 800;
        $img = imagecreatetruecolor($w, $h);
        mt_srand($seed * 31 + $variant);
        match ($variant % 3) {
            0 => self::drawBuilding($img, $w, $h, $company),
            1 => self::drawOffice($img, $w, $h, $company),
            default => self::drawMeetingRoom($img, $w, $h, $company),
        };
        mt_srand();

        return self::jpeg($img);
    }

    private static function drawBuilding($img, int $w, int $h, string $company): void
    {
        self::gradient($img, 0, 0, $w, 560, [128, 186, 235], [226, 240, 252]);
        $grass = imagecolorallocate($img, 94, 152, 90);
        imagefilledrectangle($img, 0, 560, $w, $h, $grass);
        imagefilledrectangle($img, 0, 640, $w, $h, imagecolorallocate($img, 88, 92, 99)); // road
        for ($x = 40; $x < $w; $x += 160) {
            imagefilledrectangle($img, $x, 716, $x + 80, 724, imagecolorallocate($img, 240, 240, 240));
        }
        // clouds
        $cloud = imagecolorallocatealpha($img, 255, 255, 255, 40);
        foreach ([[220, 110, 220, 70], [260, 90, 150, 60], [880, 160, 260, 80], [940, 130, 160, 60]] as [$cx, $cy, $cw, $ch]) {
            imagefilledellipse($img, $cx, $cy, $cw, $ch, $cloud);
        }
        // neighbouring towers
        $neighbours = [[40, 250, 190], [250, 200, 170], [880, 230, 210], [1070, 290, 130]];
        foreach ($neighbours as [$x, $top, $width]) {
            imagefilledrectangle($img, $x, $top, $x + $width, 590, imagecolorallocate($img, 120, 138, 160));
            self::windows($img, $x + 12, $top + 14, $x + $width - 12, 580, 26, 34, imagecolorallocate($img, 190, 214, 236));
        }
        // the company's building
        [$r, $g, $b] = self::color(abs(crc32($company)));
        $x1 = 330; $x2 = 850; $top = 130;
        imagefilledrectangle($img, $x1, $top, $x2, 600, imagecolorallocate($img, 238, 241, 245));
        imagefilledrectangle($img, $x1, $top, $x2, $top + 46, imagecolorallocate($img, $r, $g, $b));
        self::centered($img, 24, $top + 33, imagecolorallocate($img, 255, 255, 255), self::fontBold(), mb_strtoupper(mb_substr($company, 0, 26)), $w);
        self::windows($img, $x1 + 24, $top + 72, $x2 - 24, 520, 44, 54, imagecolorallocate($img, 103, 160, 214));
        imagefilledrectangle($img, 540, 520, 640, 600, imagecolorallocate($img, 60, 88, 122)); // door
        imagefilledrectangle($img, 646, 520, 746, 600, imagecolorallocate($img, 60, 88, 122));
        // trees
        foreach ([260, 940, 1040] as $tx) {
            imagefilledrectangle($img, $tx - 6, 560, $tx + 6, 620, imagecolorallocate($img, 96, 66, 40));
            imagefilledellipse($img, $tx, 530, 90, 100, imagecolorallocate($img, 52, 130, 72));
        }
    }

    private static function drawOffice($img, int $w, int $h, string $company): void
    {
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 236, 238, 242)); // wall
        self::gradient($img, 0, 500, $w, $h, [190, 170, 145], [150, 128, 104]); // floor
        // window wall with sky
        imagefilledrectangle($img, 60, 70, 520, 400, imagecolorallocate($img, 255, 255, 255));
        self::gradient($img, 72, 82, 508, 388, [140, 195, 240], [224, 240, 252]);
        imagefilledrectangle($img, 288, 82, 292, 388, imagecolorallocate($img, 255, 255, 255));
        // ceiling lights
        foreach ([620, 820, 1020] as $lx) {
            imagefilledrectangle($img, $lx, 40, $lx + 120, 56, imagecolorallocate($img, 255, 250, 224));
        }
        // desks with monitors in two rows
        $desk = imagecolorallocate($img, 244, 244, 246);
        $edge = imagecolorallocate($img, 120, 120, 128);
        foreach ([[110, 470], [450, 470], [790, 470], [270, 610], [610, 610], [950, 610]] as [$dx, $dy]) {
            imagefilledrectangle($img, $dx, $dy, $dx + 260, $dy + 22, $desk);
            imagerectangle($img, $dx, $dy, $dx + 260, $dy + 22, $edge);
            imagefilledrectangle($img, $dx + 20, $dy + 22, $dx + 30, $dy + 120, $edge);
            imagefilledrectangle($img, $dx + 230, $dy + 22, $dx + 240, $dy + 120, $edge);
            imagefilledrectangle($img, $dx + 70, $dy - 70, $dx + 190, $dy - 6, imagecolorallocate($img, 34, 40, 49)); // monitor
            imagefilledrectangle($img, $dx + 76, $dy - 64, $dx + 184, $dy - 12, imagecolorallocate($img, 70 + mt_rand(0, 60), 120 + mt_rand(0, 60), 190));
            imagefilledrectangle($img, $dx + 124, $dy - 6, $dx + 136, $dy + 2, $edge);
            imagefilledellipse($img, $dx + 130, $dy + 80, 70, 40, imagecolorallocate($img, 52, 62, 80)); // chair
        }
        // plants
        foreach ([620, 1130] as $px) {
            imagefilledrectangle($img, $px, 330, $px + 36, 400, imagecolorallocate($img, 140, 98, 64));
            imagefilledellipse($img, $px + 18, 300, 90, 80, imagecolorallocate($img, 52, 130, 72));
        }
        self::sign($img, $w, $company, 590, 150);
    }

    private static function drawMeetingRoom($img, int $w, int $h, string $company): void
    {
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 226, 230, 236));
        self::gradient($img, 0, 560, $w, $h, [168, 140, 112], [120, 96, 72]);
        // wall display
        imagefilledrectangle($img, 330, 90, 870, 380, imagecolorallocate($img, 28, 32, 38));
        self::gradient($img, 342, 102, 858, 368, [24, 70, 128], [60, 130, 196]);
        [$r, $g, $b] = self::color(abs(crc32($company)));
        imagefilledrectangle($img, 380, 140, 620, 160, imagecolorallocate($img, 255, 255, 255));
        imagefilledrectangle($img, 380, 190, 560, 206, imagecolorallocatealpha($img, 255, 255, 255, 60));
        imagefilledrectangle($img, 640, 190, 820, 340, imagecolorallocate($img, $r, $g, $b));
        // table
        imagefilledellipse($img, 600, 600, 900, 220, imagecolorallocate($img, 92, 64, 42));
        imagefilledellipse($img, 600, 586, 900, 220, imagecolorallocate($img, 148, 104, 70));
        // chairs round the table
        foreach ([[250, 520], [420, 470], [600, 455], [780, 470], [950, 520], [300, 700], [500, 735], [700, 735], [900, 700]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, 90, 56, imagecolorallocate($img, 52, 62, 80));
            imagefilledellipse($img, $cx, $cy - 14, 70, 36, imagecolorallocate($img, 72, 84, 108));
        }
        // laptops
        foreach ([[470, 575], [660, 575]] as [$lx, $ly]) {
            imagefilledrectangle($img, $lx, $ly, $lx + 70, $ly + 38, imagecolorallocate($img, 200, 204, 212));
        }
        self::sign($img, $w, $company, 80, 40);
    }

    /** A small name plate. */
    private static function sign($img, int $w, string $company, int $x, int $y): void
    {
        $text = mb_strtoupper(mb_substr($company, 0, 26));
        $box = imagettfbbox(26, 0, self::fontBold(), $text);
        $tw = $box[2] - $box[0];
        imagefilledrectangle($img, $x - 20, $y - 40, $x + $tw + 20, $y + 20, imagecolorallocatealpha($img, 20, 26, 34, 30));
        imagettftext($img, 26, 0, $x, $y, imagecolorallocate($img, 255, 255, 255), self::fontBold(), $text);
    }

    private static function windows($img, int $x1, int $y1, int $x2, int $y2, int $cw, int $ch, int $color): void
    {
        for ($y = $y1; $y + $ch <= $y2; $y += $ch + 12) {
            for ($x = $x1; $x + $cw <= $x2; $x += $cw + 12) {
                imagefilledrectangle($img, $x, $y, $x + $cw, $y + $ch, $color);
            }
        }
    }

    // ------------------------------------------------------------------ certificate

    /** A plain certificate: border, title, the student's name, issuer, date and a seal. 1200x850 PNG. */
    public static function certificate(string $title, string $student, string $issuer, string $date, int $seed): string
    {
        $w = 1200;
        $h = 850;
        $img = self::canvas($w, $h);
        [$r, $g, $b] = self::color($seed);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, 255, 253, 248));
        $accent = imagecolorallocate($img, $r, $g, $b);
        imagesetthickness($img, 10);
        imagerectangle($img, 30, 30, $w - 30, $h - 30, $accent);
        imagesetthickness($img, 2);
        imagerectangle($img, 56, 56, $w - 56, $h - 56, $accent);
        imagesetthickness($img, 1);

        $ink = imagecolorallocate($img, 40, 44, 52);
        $muted = imagecolorallocate($img, 104, 110, 120);
        self::centered($img, 20, 150, $muted, self::fontBold(), 'CERTIFICATE', $w);
        self::centered($img, 52, 230, $accent, self::serifBold(), 'of Completion', $w);
        imagefilledrectangle($img, 450, 262, 750, 266, $accent);
        self::centered($img, 20, 330, $muted, self::font(), 'This is proudly presented to', $w);
        self::centered($img, 46, 420, $ink, self::serifBold(), $student, $w);
        self::centered($img, 20, 490, $muted, self::font(), 'for successfully completing', $w);
        foreach (self::wrap($title, 40) as $i => $line) {
            self::centered($img, 32, 560 + $i * 46, $ink, self::fontBold(), $line, $w);
        }
        self::centered($img, 20, 700, $muted, self::font(), 'Issued by '.$issuer, $w);
        self::centered($img, 18, 740, $muted, self::font(), $date, $w);
        // seal
        imagefilledellipse($img, 1010, 690, 150, 150, $accent);
        imagefilledellipse($img, 1010, 690, 124, 124, imagecolorallocate($img, 255, 253, 248));
        imagefilledellipse($img, 1010, 690, 108, 108, $accent);
        self::centered($img, 20, 698, imagecolorallocate($img, 255, 255, 255), self::fontBold(), 'SEAL', 2020);

        return self::png($img);
    }

    /** @return list<string> */
    private static function wrap(string $text, int $width): array
    {
        return array_slice(explode("\n", wordwrap($text, $width, "\n", true)), 0, 2);
    }

    // ------------------------------------------------------------------ drawing helpers

    private static function canvas(int $w, int $h)
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        return $img;
    }

    private static function gradient($img, int $x1, int $y1, int $x2, int $y2, array $from, array $to): void
    {
        $height = max(1, $y2 - $y1);
        for ($y = $y1; $y < $y2; $y++) {
            $t = ($y - $y1) / $height;
            $color = imagecolorallocate(
                $img,
                (int) ($from[0] + ($to[0] - $from[0]) * $t),
                (int) ($from[1] + ($to[1] - $from[1]) * $t),
                (int) ($from[2] + ($to[2] - $from[2]) * $t),
            );
            imageline($img, $x1, $y, $x2, $y, $color);
        }
    }

    private static function roundedRect($img, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        foreach ([[$x1 + $radius, $y1 + $radius], [$x2 - $radius, $y1 + $radius], [$x1 + $radius, $y2 - $radius], [$x2 - $radius, $y2 - $radius]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $radius * 2, $radius * 2, $color);
        }
    }

    /** Text centred in a box of width $width, with its baseline at $baseline. */
    private static function centered($img, int|float $size, int $baseline, int $color, string $font, string $text, int $width): void
    {
        $box = imagettfbbox($size, 0, $font, $text);
        $x = (int) (($width - ($box[2] - $box[0])) / 2 - $box[0]);
        imagettftext($img, $size, 0, max(0, $x), $baseline, $color, $font, $text);
    }

    private static function png($img): string
    {
        ob_start();
        imagepng($img, null, 7);

        return (string) ob_get_clean();
    }

    private static function jpeg($img): string
    {
        ob_start();
        imagejpeg($img, null, 82);

        return (string) ob_get_clean();
    }
}
