<?php

use Livewire\Component;

new class extends Component
{
    public string $greeting = 'Welcome';
};
?>

<div>{{ $greeting }}</div>
