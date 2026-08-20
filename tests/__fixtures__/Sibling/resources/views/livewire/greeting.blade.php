<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('sibling::layout')] class extends Component
{
    public string $greeting = 'Hello';
};
?>

<div>{{ $greeting }}</div>
