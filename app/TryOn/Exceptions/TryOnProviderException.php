<?php

namespace App\TryOn\Exceptions;

use RuntimeException;

/**
 * Provider xatosi. Xabar foydalanuvchiga ko'rsatilishi mumkin — shuning uchun
 * ichida HECH QACHON API kaliti yoki xom javob bo'lmasligi kerak.
 */
class TryOnProviderException extends RuntimeException {}
