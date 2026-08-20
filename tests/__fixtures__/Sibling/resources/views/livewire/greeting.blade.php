<?php

use Livewire\Component;

new class extends Component
{
    public string $greeting = 'Hello';
};
?>

<div>{{ $greeting }}</div>
