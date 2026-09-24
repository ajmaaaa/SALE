<?php

namespace App\Services;

class QrCodeService
{
    /**
     * Generate an SVG QR code for the given text.
     * Uses standard Version 3 (29x29) or Version 4 (33x33) QR Code.
     */
    public static function svg(string $data, int $size = 200): string
    {
        $matrix = (new static)->generateMatrix($data);
        $dimension = count($matrix);
        $margin = 4;
        $totalModules = $dimension + ($margin * 2);
        $scale = $size / $totalModules;

        $path = '';
        for ($r = 0; $r < $dimension; $r++) {
            for ($c = 0; $c < $dimension; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = ($c + $margin) * $scale;
                    $y = ($r + $margin) * $scale;
                    $path .= sprintf('M%.2f,%.2fh%.2fv%.2fh-%.2fz ', $x, $y, $scale, $scale, $scale);
                }
            }
        }

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" shape-rendering="crispEdges">',
            $size, $size, $size, $size
        );
        $svg .= sprintf('<rect width="100%%" height="100%%" fill="#ffffff"/>');
        $svg .= sprintf('<path d="%s" fill="#0f172a"/>', trim($path));
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Generate Code 128 Barcode as pure SVG.
     */
    public static function barcodeSvg(string $code, int $width = 260, int $height = 70): string
    {
        // Simple and robust Code 128-B generator
        $patterns = [
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
            '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
        ];

        $startB = 104;
        $stop = 106;
        $chars = str_split($code);
        $values = [$startB];
        $checksum = $startB;

        foreach ($chars as $idx => $char) {
            $ascii = ord($char);
            $val = $ascii - 32;
            if ($val < 0 || $val > 95) {
                $val = 0;
            }
            $values[] = $val;
            $checksum += ($idx + 1) * $val;
        }

        $values[] = $checksum % 103;
        $values[] = $stop;

        $bars = '';
        foreach ($values as $v) {
            $bars .= $patterns[$v] ?? $patterns[0];
        }

        $totalUnits = 0;
        $barsLen = strlen($bars);
        for ($i = 0; $i < $barsLen; $i++) {
            $totalUnits += (int) $bars[$i];
        }

        $unitWidth = ($width - 20) / $totalUnits;
        $curX = 10.0;
        $barHeight = $height - 18;
        $svgBars = '';

        for ($i = 0; $i < $barsLen; $i++) {
            $w = (int) $bars[$i] * $unitWidth;
            $isBlack = ($i % 2 === 0);
            if ($isBlack) {
                $svgBars .= sprintf('<rect x="%.2f" y="5" width="%.2f" height="%d" fill="#0f172a"/>', $curX, $w, $barHeight);
            }
            $curX += $w;
        }

        $textY = $height - 2;
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d">',
            $width, $height, $width, $height
        );
        $svg .= sprintf('<rect width="100%%" height="100%%" fill="#ffffff"/>');
        $svg .= $svgBars;
        $svg .= sprintf('<text x="%d" y="%d" text-anchor="middle" font-family="monospace" font-size="11" font-weight="bold" fill="#334155">%s</text>', $width / 2, $textY, htmlspecialchars($code));
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Generate QR Code Matrix (V3 / V4 compatible).
     */
    public function generateMatrix(string $data): array
    {
        // QR Version 3 (29x29) can hold 42 bytes (M) or 55 bytes (L)
        // If data > 50 chars, use Version 4 (33x33)
        $len = strlen($data);
        $version = $len > 44 ? 4 : 3;
        $size = 17 + (4 * $version); // 29 for V3, 33 for V4

        // Initialize empty matrix (-1 = unset)
        $matrix = array_fill(0, $size, array_fill(0, $size, -1));

        // 1. Finder patterns
        $this->placeFinderPattern($matrix, 0, 0);
        $this->placeFinderPattern($matrix, 0, $size - 7);
        $this->placeFinderPattern($matrix, $size - 7, 0);

        // 2. Alignment patterns
        $alignPos = $version === 3 ? [6, 22] : [6, 26];
        foreach ($alignPos as $r) {
            foreach ($alignPos as $c) {
                if (($r === 6 && $c === 6) || ($r === 6 && $c === $size - 7) || ($r === $size - 7 && $c === 6)) {
                    continue;
                }
                $this->placeAlignmentPattern($matrix, $r, $c);
            }
        }

        // 3. Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $bit = ($i % 2 === 0) ? 1 : 0;
            if ($matrix[6][$i] === -1) {
                $matrix[6][$i] = $bit;
            }
            if ($matrix[$i][6] === -1) {
                $matrix[$i][6] = $bit;
            }
        }

        // 4. Dark module
        $matrix[$size - 8][8] = 1;

        // 5. Reserve format information areas
        for ($i = 0; $i < 9; $i++) {
            if ($matrix[8][$i] === -1) {
                $matrix[8][$i] = 0;
            }
            if ($matrix[$i][8] === -1) {
                $matrix[$i][8] = 0;
            }
        }
        for ($i = $size - 8; $i < $size; $i++) {
            if ($matrix[8][$i] === -1) {
                $matrix[8][$i] = 0;
            }
            if ($matrix[$i][8] === -1) {
                $matrix[$i][8] = 0;
            }
        }

        // 6. Encode Data (Byte mode 0100 + length + data bytes + terminator)
        $codewords = $this->encodeData($data, $version);
        $eccCodewords = $this->generateEcc($codewords, $version);
        $allCodewords = array_merge($codewords, $eccCodewords);

        // Convert codewords to bit array
        $bits = [];
        foreach ($allCodewords as $byte) {
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = ($byte >> $b) & 1;
            }
        }

        // 7. Place data bits in zig-zag
        $bitIdx = 0;
        $totalBits = count($bits);
        $dir = -1; // upwards
        $c = $size - 1;

        while ($c > 0) {
            if ($c === 6) { // skip vertical timing column
                $c--;
            }
            $rows = $dir === -1 ? range($size - 1, 0) : range(0, $size - 1);
            foreach ($rows as $r) {
                for ($colOffset = 0; $colOffset <= 1; $colOffset++) {
                    $col = $c - $colOffset;
                    if ($matrix[$r][$col] === -1) {
                        $matrix[$r][$col] = ($bitIdx < $totalBits) ? $bits[$bitIdx++] : 0;
                    }
                }
            }
            $dir = -$dir;
            $c -= 2;
        }

        // 8. Apply standard Mask Pattern 0: (row + col) % 2 == 0
        for ($r = 0; $r < $size; $r++) {
            for ($col = 0; $col < $size; $col++) {
                if (! $this->isFunctionModule($r, $col, $size, $version)) {
                    if (($r + $col) % 2 === 0) {
                        $matrix[$r][$col] ^= 1;
                    }
                }
            }
        }

        // 9. Write Format Information: Level L (01), Mask 000 -> 01000 = 0x08 -> BCH encoded = 0x77c4 ^ 0x5412 = 0x23d6
        $formatBits = [0, 1, 0, 0, 0, 1, 1, 1, 1, 0, 1, 0, 1, 1, 0];
        $this->placeFormatInfo($matrix, $formatBits, $size);

        return $matrix;
    }

    private function placeFinderPattern(array &$m, int $top, int $left): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $row = $top + $r;
                $col = $left + $c;
                if ($row < 0 || $row >= count($m) || $col < 0 || $col >= count($m)) {
                    continue;
                }
                if ($r === -1 || $r === 7 || $c === -1 || $c === 7) {
                    $m[$row][$col] = 0; // Separator
                } elseif ($r === 0 || $r === 6 || $c === 0 || $c === 6) {
                    $m[$row][$col] = 1; // Outer black box
                } elseif ($r === 1 || $r === 5 || $c === 1 || $c === 5) {
                    $m[$row][$col] = 0; // White border
                } else {
                    $m[$row][$col] = 1; // 3x3 Center
                }
            }
        }
    }

    private function placeAlignmentPattern(array &$m, int $centerR, int $centerC): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $row = $centerR + $r;
                $col = $centerC + $c;
                if ($row >= 0 && $row < count($m) && $col >= 0 && $col < count($m)) {
                    if (abs($r) === 2 || abs($c) === 2 || ($r === 0 && $c === 0)) {
                        $m[$row][$col] = 1;
                    } else {
                        $m[$row][$col] = 0;
                    }
                }
            }
        }
    }

    private function placeFormatInfo(array &$m, array $bits, int $size): void
    {
        // Top-left
        $coordsTopLeft = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
        ];
        foreach ($coordsTopLeft as $i => $pos) {
            $m[$pos[0]][$pos[1]] = $bits[$i];
        }

        // Bottom-left & Top-right
        $m[$size - 1][8] = $bits[0];
        $m[$size - 2][8] = $bits[1];
        $m[$size - 3][8] = $bits[2];
        $m[$size - 4][8] = $bits[3];
        $m[$size - 5][8] = $bits[4];
        $m[$size - 6][8] = $bits[5];
        $m[$size - 7][8] = $bits[6];

        for ($i = 0; $i < 8; $i++) {
            $m[8][$size - 8 + $i] = $bits[7 + $i];
        }
    }

    private function isFunctionModule(int $r, int $c, int $size, int $version): bool
    {
        // Finder patterns + separators
        if ($r < 9 && $c < 9) {
            return true;
        }
        if ($r < 9 && $c >= $size - 8) {
            return true;
        }
        if ($r >= $size - 8 && $c < 9) {
            return true;
        }

        // Timing patterns
        if ($r === 6 || $c === 6) {
            return true;
        }

        // Dark module
        if ($r === $size - 8 && $c === 8) {
            return true;
        }

        // Alignment pattern
        $alignCenter = $version === 3 ? 22 : 26;
        if (abs($r - $alignCenter) <= 2 && abs($c - $alignCenter) <= 2) {
            return true;
        }

        return false;
    }

    private function encodeData(string $data, int $version): array
    {
        $maxDataCodewords = $version === 3 ? 55 : 80;
        $len = strlen($data);

        // Byte mode indicator: 0100 (4 bits)
        // Character count indicator: 8 bits
        $bitBuffer = '';
        $bitBuffer .= '0100';
        $bitBuffer .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT);

        for ($i = 0; $i < $len; $i++) {
            $bitBuffer .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
        }

        // Terminator (up to 4 zeroes)
        $neededBits = $maxDataCodewords * 8;
        $termLen = min(4, $neededBits - strlen($bitBuffer));
        $bitBuffer .= str_repeat('0', max(0, $termLen));

        // Pad to byte boundary
        while (strlen($bitBuffer) % 8 !== 0) {
            $bitBuffer .= '0';
        }

        // Convert bits to byte codewords
        $codewords = [];
        $chunks = str_split($bitBuffer, 8);
        foreach ($chunks as $chunk) {
            $codewords[] = bindec($chunk);
        }

        // Add pad bytes: alternating 0xEC (236) and 0x11 (17)
        $padBytes = [236, 17];
        $padIdx = 0;
        while (count($codewords) < $maxDataCodewords) {
            $codewords[] = $padBytes[$padIdx % 2];
            $padIdx++;
        }

        return $codewords;
    }

    private function generateEcc(array $dataCodewords, int $version): array
    {
        $eccCount = $version === 3 ? 15 : 20;

        // Generator polynomial for QR Reed Solomon
        $genPoly = $this->rsGeneratorPoly($eccCount);

        // Reed Solomon encoding
        $info = array_pad($dataCodewords, count($dataCodewords) + $eccCount, 0);

        for ($i = 0; $i < count($dataCodewords); $i++) {
            $lead = $info[$i];
            if ($lead !== 0) {
                $leadLog = $this->gfLog($lead);
                for ($j = 0; $j <= $eccCount; $j++) {
                    $info[$i + $j] ^= $this->gfMulByLog($genPoly[$j], $leadLog);
                }
            }
        }

        return array_slice($info, count($dataCodewords));
    }

    private function rsGeneratorPoly(int $eccCount): array
    {
        $poly = [1];
        for ($i = 0; $i < $eccCount; $i++) {
            $next = [1, $this->gfExp($i)];
            $newPoly = array_fill(0, count($poly) + 1, 0);
            for ($p = 0; $p < count($poly); $p++) {
                for ($n = 0; $n < 2; $n++) {
                    $newPoly[$p + $n] ^= $this->gfMul($poly[$p], $next[$n]);
                }
            }
            $poly = $newPoly;
        }

        return $poly;
    }

    // Galois Field GF(256) with primitive polynomial 0x11d
    private static ?array $gfExpTable = null;

    private static ?array $gfLogTable = null;

    private function initGfTables(): void
    {
        if (self::$gfExpTable !== null) {
            return;
        }

        self::$gfExpTable = array_fill(0, 512, 0);
        self::$gfLogTable = array_fill(0, 256, 0);

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gfExpTable[$i] = $x;
            self::$gfLogTable[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$gfExpTable[$i] = self::$gfExpTable[$i - 255];
        }
    }

    private function gfExp(int $n): int
    {
        $this->initGfTables();

        return self::$gfExpTable[$n % 255];
    }

    private function gfLog(int $n): int
    {
        $this->initGfTables();

        return self::$gfLogTable[$n];
    }

    private function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }
        $this->initGfTables();

        return self::$gfExpTable[self::$gfLogTable[$x] + self::$gfLogTable[$y]];
    }

    private function gfMulByLog(int $x, int $logY): int
    {
        if ($x === 0) {
            return 0;
        }
        $this->initGfTables();

        return self::$gfExpTable[self::$gfLogTable[$x] + $logY];
    }
}
