<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Counter extends Component
{
    public int $count = 0;

    public function render(): View
    {
        return view('sibling::show');
    }
}
