<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Apariencia')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Apariencia') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Apariencia')" :subheading="__('Elegí cómo se ve el sistema para vos')">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Claro') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Oscuro') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('Sistema') }}</flux:radio>
        </flux:radio.group>
    </x-pages::settings.layout>
</section>
