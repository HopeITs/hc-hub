<?php

namespace Modules\Hr\Support\Recruitment\Exceptions;

use RuntimeException;

/** The antivirus could not answer. Says nothing about the file; try again later. */
class RecruitmentScannerUnavailableException extends RuntimeException {}
