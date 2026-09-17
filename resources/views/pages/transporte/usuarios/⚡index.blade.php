<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Usuarios')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'administrativo';

    public function mount(): void
    {
        abort_unless(Auth::user()->isAdministrador(), 403);
    }

    #[Computed]
    public function usuarios()
    {
        return User::orderBy('name')->get();
    }

    public function crear(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password']);
        $this->role = 'administrativo';
        Flux::modal('usuario-form')->show();
    }

    public function editar(int $id): void
    {
        $usuario = User::findOrFail($id);

        $this->editingId = $usuario->id;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->password = '';
        $this->role = $usuario->role;

        Flux::modal('usuario-form')->show();
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->editingId],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', 'in:administrador,administrativo'],
        ]);

        $usuario = User::updateOrCreate(['id' => $this->editingId], [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            ...($validated['password'] ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        Flux::modal('usuario-form')->close();
        unset($this->usuarios);

        Flux::toast(variant: 'success', text: __('Usuario guardado.'));
    }

    public function eliminar(int $id): void
    {
        abort_if($id === Auth::id(), 403, __('No podés eliminar tu propio usuario.'));

        User::whereKey($id)->delete();

        unset($this->usuarios);
        Flux::toast(variant: 'success', text: __('Usuario eliminado.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Usuarios') }}</flux:heading>
            <flux:subheading>{{ __('Accesos al sistema: Administrador y Administrativo.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="crear">
            {{ __('Nuevo usuario') }}
        </flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Nombre') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Rol') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->usuarios as $usuario)
                <flux:table.row wire:key="usuario-{{ $usuario->id }}">
                    <flux:table.cell class="font-medium">{{ $usuario->name }}</flux:table.cell>
                    <flux:table.cell>{{ $usuario->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$usuario->role === 'administrador' ? 'blue' : 'zinc'">
                            {{ ucfirst($usuario->role) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="editar({{ $usuario->id }})" />
                        @if ($usuario->id !== auth()->id())
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="eliminar({{ $usuario->id }})"
                                wire:confirm="{{ __('¿Eliminar este usuario?') }}"
                            />
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="usuario-form" class="max-w-lg" @close="$set('editingId', null)">
        <form wire:submit="guardar" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Editar usuario') : __('Nuevo usuario') }}
            </flux:heading>

            <flux:input wire:model="name" :label="__('Nombre')" required autofocus />
            <flux:input wire:model="email" :label="__('Email')" type="email" required />
            <flux:input wire:model="password" :label="__('Contraseña')" type="password" placeholder="{{ $editingId ? __('Dejar en blanco para no cambiarla') : '' }}" viewable />

            <flux:select wire:model="role" :label="__('Rol')">
                <flux:select.option value="administrador">{{ __('Administrador') }}</flux:select.option>
                <flux:select.option value="administrativo">{{ __('Administrativo') }}</flux:select.option>
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
