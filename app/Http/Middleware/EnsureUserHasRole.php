<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorise l'accès à une route uniquement si l'utilisateur connecté
 * possède l'un des rôles attendus ET que son compte est actif.
 *
 * Utilisation dans les routes :
 *   ->middleware('role:admin')
 *   ->middleware('role:admin,gerant')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Non connecté ou compte désactivé : accès refusé.
        if ($user === null || ! $user->is_active) {
            abort(Response::HTTP_FORBIDDEN);
        }

        // Le rôle de l'utilisateur ne fait pas partie des rôles autorisés.
        if (! in_array($user->role->value, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, "Vous n'avez pas l'autorisation d'accéder à cette page.");
        }

        return $next($request);
    }
}
