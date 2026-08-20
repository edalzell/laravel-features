<?php

namespace Edalzell\Features\Tests\Fixtures\Sibling\Livewire\Posts;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShowPost extends Component
{
    public function render(): View
    {
        return view('sibling::show');
    }
}
