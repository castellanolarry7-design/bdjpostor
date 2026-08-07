<?php
use Illuminate\Support\Facades\Broadcast;
Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    if ($user->role === 'super_admin') return true;
    return $user->tenant_id === $tenantId;
});
