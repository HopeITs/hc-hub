<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use RuntimeException;

/** The uploaded file is not an attendance report this app can read; the message is a translation key. */
class UnreadableReport extends RuntimeException {}
