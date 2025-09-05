<?php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        // Global scope por org (solo en web; evitamos interferir con migraciones/queues si querés)
        static::addGlobalScope('org', function (Builder $q) {
            if (app()->runningInConsole()) return;
            $orgId = current_org_id(null); // devuelve null si guest
            if ($orgId) $q->where($q->getModel()->getTable().'.organization_id', $orgId);
        });

        // Autollenado al crear
        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $model->organization_id = current_org_id();
            }
        });
    }
}
