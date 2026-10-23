<?php

namespace App\TryOn\Exceptions;

use RuntimeException;

/** The uploaded file is not an image, uses a disallowed format, or is corrupt. Message is user-facing. */
class InvalidImageException extends RuntimeException {}
