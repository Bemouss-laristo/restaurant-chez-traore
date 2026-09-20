<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Liste complète : la recherche par nom, email ou rôle filtre à la frappe. */
    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::options(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = now();

        User::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Employé créé avec succès.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Mot de passe laissé vide = on garde l'ancien.
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Employé mis à jour.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return back()->with('status', 'Employé supprimé.');
    }

    /** Active / désactive un compte (sans le supprimer). */
    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', 'Statut du compte mis à jour.');
    }
}
