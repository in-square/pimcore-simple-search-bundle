<?php

namespace InSquare\PimcoreSimpleSearchBundle\Message;

final readonly class IndexElementMessage
{
    public function __construct(
        public string $type,
        public int    $id,
        public bool   $delete = false
    ) {}
}
