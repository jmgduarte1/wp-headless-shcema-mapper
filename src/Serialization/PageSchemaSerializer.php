<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Serialization;

use HeadlessAngular\Schema\Domain\Schema\PageSchema;

interface PageSchemaSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(PageSchema $schema): array;
}
