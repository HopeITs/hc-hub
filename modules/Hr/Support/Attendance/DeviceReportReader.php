<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Reads the attendance report exported from ZKTeco's Attendance Management program (columns
 * AC-No, Name, Time, State, ...) and returns one entry per punch: the device number and the time.
 *
 * Two forms are read: the program's own .xls export, which is an old Excel 5 stream made only of
 * text cells (read directly, see readRawLabels()), and a .csv / .txt saved from it. A modern
 * workbook (.xlsx, or an .xls Excel re-saved) needs a spreadsheet library the hub does not have:
 * it is refused with a message asking for the program's own file or a CSV.
 */
class DeviceReportReader
{
    private const DEVICE_HEADERS = ['ac-no', 'ac no', 'acno', 'ac-no.', 'badgenumber', 'user id', 'userid', 'person id', 'employee id'];

    private const TIME_HEADERS = ['time', 'datetime', 'date/time', 'date time', 'checktime', 'punch time'];

    private const FORMATS = ['d/m/Y H:i', 'd/m/Y H:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd-m-Y H:i', 'd-m-Y H:i:s', 'm/d/Y H:i', 'Y/m/d H:i:s', 'Y/m/d H:i'];

    /**
     * @return array{punches: list<array{device_no: string, at: CarbonImmutable}>, invalid: int}
     *
     * @throws UnreadableReport when the file is not a recognisable attendance report
     */
    public function read(string $path): array
    {
        $rows = $this->rows($path);
        [$headerAt, $deviceCol, $timeCol] = $this->findHeader($rows);

        $punches = [];
        $invalid = 0;

        foreach (array_slice($rows, $headerAt + 1) as $row) {
            $device = trim((string) ($row[$deviceCol] ?? ''));
            $rawTime = trim((string) ($row[$timeCol] ?? ''));

            if ($device === '' && $rawTime === '') {
                continue;
            }

            $at = $this->parseTime($rawTime);

            if ($device === '' || $at === null) {
                $invalid++;

                continue;
            }

            $punches[] = ['device_no' => $device, 'at' => $at];
        }

        return ['punches' => $punches, 'invalid' => $invalid];
    }

    /** @return list<array<int, string>> */
    private function rows(string $path): array
    {
        $head = (string) file_get_contents($path, false, null, 0, 8);

        // A raw Excel 5 stream starts with a BOF record (0x0809 / 0x0409 / 0x0209).
        if (strlen($head) >= 4 && in_array(unpack('v', $head)[1], [0x0809, 0x0409, 0x0209], true)) {
            return $this->readRawLabels($path);
        }

        // An OLE container (D0 CF 11 E0) or a zip (PK): a workbook saved by Excel.
        if (str_starts_with($head, "\xD0\xCF\x11\xE0") || str_starts_with($head, "PK\x03\x04")) {
            throw new UnreadableReport('hr_attendance.upload.workbook');
        }

        return $this->readCsv($path);
    }

    /**
     * Cells of a raw Excel stream made only of text records (0x0004): row, column, three attribute
     * bytes, a one byte length and the text. Text with Arabic letters is Windows-1256.
     *
     * @return list<array<int, string>>
     */
    private function readRawLabels(string $path): array
    {
        $data = (string) file_get_contents($path);
        $size = strlen($data);
        $pos = 0;
        $table = [];

        while ($pos + 4 <= $size) {
            $id = unpack('v', substr($data, $pos, 2))[1];
            $len = unpack('v', substr($data, $pos + 2, 2))[1];
            $body = substr($data, $pos + 4, $len);
            $pos += 4 + $len;

            if ($id !== 0x0004 || strlen($body) < 8) {
                continue;
            }

            $row = unpack('v', substr($body, 0, 2))[1];
            $col = unpack('v', substr($body, 2, 2))[1];
            $text = substr($body, 8, ord($body[7]));

            $table[$row][$col] = preg_match('/[\x80-\xFF]/', $text)
                ? (string) mb_convert_encoding($text, 'UTF-8', 'Windows-1256')
                : $text;
        }

        ksort($table);

        return array_values(array_map(function (array $cells): array {
            ksort($cells);

            return $cells;
        }, $table));
    }

    /** @return list<array<int, string>> */
    private function readCsv(string $path): array
    {
        $text = (string) file_get_contents($path);
        $text = str_starts_with($text, "\xEF\xBB\xBF") ? substr($text, 3) : $text;

        if (! mb_check_encoding($text, 'UTF-8')) {
            $text = (string) mb_convert_encoding($text, 'UTF-8', 'Windows-1256');
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $first = (string) ($lines[0] ?? '');
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn (string $d): int => substr_count($first, $d))->first();

        return array_values(array_map(
            fn (string $line): array => array_map(fn (?string $cell): string => (string) $cell, str_getcsv($line, $delimiter, '"', '')),
            array_filter($lines, fn (string $line): bool => trim($line) !== ''),
        ));
    }

    /**
     * @param  list<array<int, string>>  $rows
     * @return array{0: int, 1: int, 2: int} the header row and the device and time columns
     */
    private function findHeader(array $rows): array
    {
        foreach (array_slice($rows, 0, 15, true) as $at => $row) {
            $device = $time = null;

            foreach ($row as $col => $cell) {
                $name = mb_strtolower(trim((string) $cell));

                if ($device === null && in_array($name, self::DEVICE_HEADERS, true)) {
                    $device = $col;
                }

                if ($time === null && in_array($name, self::TIME_HEADERS, true)) {
                    $time = $col;
                }
            }

            if ($device !== null && $time !== null) {
                return [$at, $device, $time];
            }
        }

        throw new UnreadableReport('hr_attendance.upload.no-columns');
    }

    private function parseTime(string $text): ?CarbonImmutable
    {
        if ($text === '') {
            return null;
        }

        $zone = (string) config('app.timezone');

        foreach (self::FORMATS as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat('!'.$format, $text, $zone);
            } catch (Throwable) {
                continue;
            }

            if ($parsed instanceof CarbonImmutable && $parsed->format($format) === $text) {
                return $parsed;
            }
        }

        return null;
    }
}
