<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Livewire;

/**
 * Not a Livewire component, so it must not be registered as one.
 */
class PostFormatter
{
    public function format(string $post): string
    {
        return trim($post);
    }
}
