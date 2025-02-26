<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Advance;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class AdvancePolicy
{
    use HandlesAuthorization;

    /**
     * Get the appropriate permission based on the resource being accessed.
     */
    protected function getPermissionForResource(string $action, ?string $resource = null): string
    {
        // Si estamos en un contexto de resource específico, usar su permiso
        if ($resource) {
            return "{$action}_{$resource}";
        }

        // De lo contrario, verificar de qué resource viene la solicitud
        $requestPath = request()->path();

        if (Str::contains($requestPath, 'advance-user')) {
            return "{$action}_AdvanceUserResource";
        } elseif (Str::contains($requestPath, 'advance-pending')) {
            return "{$action}_AdvancePendingResource";
        } elseif (Str::contains($requestPath, 'advance-approved')) {
            return "{$action}_AdvanceApprovedResource";
        } elseif (Str::contains($requestPath, 'advance-completed')) {
            return "{$action}_AdvanceCompletedResource";
        } elseif (Str::contains($requestPath, 'advance-legalization')) {
            return "{$action}_AdvanceLegalizationResource";
        } elseif (Str::contains($requestPath, 'advance-treasury')) {
            return "{$action}_AdvanceTreasuryResource";
        } else {
            // Permiso predeterminado
            return "{$action}_AdvanceResource";
        }
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        $permission = $this->getPermissionForResource('view_any');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Advance $advance): bool
    {
        $permission = $this->getPermissionForResource('view');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $permission = $this->getPermissionForResource('create');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Advance $advance): bool
    {
        $permission = $this->getPermissionForResource('update');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Advance $advance): bool
    {
        $permission = $this->getPermissionForResource('delete');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        $permission = $this->getPermissionForResource('delete_any');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Advance $advance): bool
    {
        $permission = $this->getPermissionForResource('force_delete');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        $permission = $this->getPermissionForResource('force_delete_any');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Advance $advance): bool
    {
        $permission = $this->getPermissionForResource('restore');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        $permission = $this->getPermissionForResource('restore_any');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Advance $advance): bool
    {
        $permission = $this->getPermissionForResource('replicate');
        return $user->can($permission);
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        $permission = $this->getPermissionForResource('reorder');
        return $user->can($permission);
    }
}
