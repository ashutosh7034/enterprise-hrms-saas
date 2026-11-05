<?php

namespace App\Exceptions;

use Exception;

class ConcurrentDraftEditException extends Exception
{
    public function __construct(
        string $message = "This onboarding draft was updated in another session.",
        public int $currentVersion = 1,
        public ?string $lastSavedAt = null
    ) {
        parent::__construct($message);
    }
}

