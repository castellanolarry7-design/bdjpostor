<?php

namespace App\Policies;

use App\Models\User;

/**
 * UserPolicy — reglas de quién puede tocar a quién.
 *
 * Esta clase NO EXISTÍA. UserController llamaba a $this->authorize(...) contra
 * una policy inexistente y, además, el Controller base no usaba el trait
 * AuthorizesRequests, así que esas llamadas reventaban con un 500. El efecto
 * secundario bueno es que fallaba cerrado (no se creaba nada); el malo es que
 * la gestión de usuarios no funcionaba y las reglas de negocio no existían.
 *
 * Reglas:
 *   - super_admin  → todo, en cualquier empresa.
 *   - admin        → solo usuarios de SU empresa, y nunca super_admins.
 *                    No puede crear ni ascender a nadie a super_admin.
 *   - user/cashier → solo su propio perfil, y sin tocar rol, estado ni empresa.
 *   - Nadie puede cambiarse su propio rol, desactivarse ni borrarse.
 */
class UserPolicy
{
    /** Roles que un admin de empresa puede asignar. Nunca super_admin. */
    private const ROLES_ASIGNABLES_POR_ADMIN = ['admin', 'user', 'cashier'];

    /** Campos que solo puede tocar quien administra, nunca el propio usuario. */
    private const CAMPOS_PRIVILEGIADOS = ['role', 'active', 'tenant_id'];

    public function viewAny(User $auth): bool
    {
        return $auth->isSuperAdmin() || $auth->isAdmin();
    }

    public function view(User $auth, User $target): bool
    {
        if ($auth->id === $target->id)  return true;   // su propio perfil
        if ($auth->isSuperAdmin())      return true;

        return $auth->isAdmin() && $this->mismaEmpresa($auth, $target) && ! $target->isSuperAdmin();
    }

    /**
     * @param  array<string,mixed>  $data  Cuerpo de la petición
     */
    public function create(User $auth, array $data = []): bool
    {
        $rol = $data['role'] ?? 'user';

        if ($auth->isSuperAdmin()) {
            return true;
        }

        if (! $auth->isAdmin()) {
            return false;
        }

        // Un admin de empresa no puede fabricarse un super_admin: sería
        // tomar el control de todas las empresas del sistema.
        if (! in_array($rol, self::ROLES_ASIGNABLES_POR_ADMIN, true)) {
            return false;
        }

        // Ni crear usuarios colgando de otra empresa.
        if (! empty($data['tenant_id']) && $data['tenant_id'] !== $auth->tenant_id) {
            return false;
        }

        return $auth->tenant_id !== null;
    }

    /**
     * @param  array<string,mixed>  $data  Cuerpo de la petición
     */
    public function update(User $auth, User $target, array $data = []): bool
    {
        $esUnoMismo    = $auth->id === $target->id;
        $tocaPrivilegios = $this->tocaCamposPrivilegiados($data, $target);

        // Nadie se cambia el rol, el estado ni la empresa a sí mismo,
        // ni siquiera el super_admin: es la vía clásica de escalada.
        if ($esUnoMismo && $tocaPrivilegios) {
            return false;
        }

        if ($esUnoMismo) {
            return true;   // nombre, email, contraseña de su propio perfil
        }

        if ($auth->isSuperAdmin()) {
            return true;
        }

        if (! $auth->isAdmin()) {
            return false;  // user y cashier solo se editan a sí mismos
        }

        // A partir de aquí: admin editando a otro de su empresa
        if (! $this->mismaEmpresa($auth, $target) || $target->isSuperAdmin()) {
            return false;
        }

        // No puede ascender a nadie a super_admin
        if (isset($data['role']) && ! in_array($data['role'], self::ROLES_ASIGNABLES_POR_ADMIN, true)) {
            return false;
        }

        // Ni mover usuarios a otra empresa
        if (isset($data['tenant_id']) && $data['tenant_id'] !== $auth->tenant_id) {
            return false;
        }

        return true;
    }

    public function toggleActive(User $auth, User $target): bool
    {
        // Desactivarse a uno mismo deja la empresa sin quien la administre
        if ($auth->id === $target->id) {
            return false;
        }

        if ($auth->isSuperAdmin()) {
            return true;
        }

        return $auth->isAdmin()
            && $this->mismaEmpresa($auth, $target)
            && ! $target->isSuperAdmin();
    }

    public function delete(User $auth, User $target): bool
    {
        if ($auth->id === $target->id) {
            return false;
        }

        if ($auth->isSuperAdmin()) {
            return true;
        }

        return $auth->isAdmin()
            && $this->mismaEmpresa($auth, $target)
            && ! $target->isSuperAdmin();
    }

    // ─── Auxiliares ──────────────────────────────────────────────────────

    private function mismaEmpresa(User $auth, User $target): bool
    {
        return $auth->tenant_id !== null && $auth->tenant_id === $target->tenant_id;
    }

    /**
     * ¿La petición intenta modificar rol, estado o empresa con un valor
     * distinto al actual? Reenviar el mismo valor no cuenta como cambio.
     *
     * @param  array<string,mixed>  $data
     */
    private function tocaCamposPrivilegiados(array $data, User $target): bool
    {
        foreach (self::CAMPOS_PRIVILEGIADOS as $campo) {
            if (! array_key_exists($campo, $data)) {
                continue;
            }
            if ($campo === 'active') {
                if ((bool) $data[$campo] !== (bool) $target->active) return true;
                continue;
            }
            if ((string) $data[$campo] !== (string) $target->{$campo}) return true;
        }

        return false;
    }
}
