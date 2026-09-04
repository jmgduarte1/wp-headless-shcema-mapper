<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Domain\Schema;

final readonly class FormData
{
    /**
     * @param list<array<string, mixed>> $fields
     * @param array<string, mixed> $submit
     */
    public function __construct(
        public string $formId,
        public array $fields,
        public array $submit,
        public ?string $successMessage = null,
        public ?string $failureMessage = null,
    ) {
    }
}
