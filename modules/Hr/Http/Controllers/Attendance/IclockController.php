<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Hr\Support\Attendance\DevicePush;

/**
 * The address a ZKTeco device calls when its Cloud Server is set to ADMS ("/iclock/..."). The device
 * is no browser: no login, no session, no token. It is trusted only by its registered serial number
 * (DevicePush::resolve), and an unregistered or blocked one gets a plain refusal, so it keeps its log
 * and tries again after HR adds it. Every answer is plain text, as the device expects.
 *
 *  - GET  /iclock/cdata       hello: the serial, and what to send from where
 *  - POST /iclock/cdata       a table of records (ATTLOG = punches); anything else is acknowledged
 *  - GET  /iclock/getrequest  "any commands for me?": no, thank you
 *  - POST /iclock/devicecmd   the result of a command: noted
 */
class IclockController extends Controller
{
    public function cdata(Request $request, DevicePush $push): Response
    {
        $device = $push->resolve($this->serial($request), $request->ip(), $this->queryString($request, 'pushver'));

        if ($device === null) {
            return $this->text('UNREGISTERED', 403);
        }

        if ($request->isMethod('GET')) {
            return $this->text($push->options($device));
        }

        $table = strtoupper((string) $this->queryString($request, 'table'));

        if ($table !== 'ATTLOG') {
            // Operation log, users, photos: not used here. Acknowledged so the device moves on.
            $held = array_filter(preg_split('/\R/', $request->getContent()) ?: [], fn (string $line): bool => trim($line) !== '');

            return $this->text('OK:'.count($held));
        }

        return $this->text('OK:'.$push->ingestAttlog($device, $request->getContent(), $this->queryString($request, 'Stamp')));
    }

    public function getrequest(Request $request, DevicePush $push): Response
    {
        $device = $push->resolve($this->serial($request), $request->ip(), $this->queryString($request, 'pushver'));

        return $device === null ? $this->text('UNREGISTERED', 403) : $this->text('OK');
    }

    public function devicecmd(Request $request, DevicePush $push): Response
    {
        $device = $push->resolve($this->serial($request), $request->ip());

        return $device === null ? $this->text('UNREGISTERED', 403) : $this->text('OK');
    }

    /** The serial arrives as SN (older firmware: sn). */
    private function serial(Request $request): ?string
    {
        return $this->queryString($request, 'SN') ?? $this->queryString($request, 'sn');
    }

    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) ? $value : null;
    }

    private function text(string $body, int $status = 200): Response
    {
        return response($body, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
