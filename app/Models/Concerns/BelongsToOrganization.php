<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrgScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Evitar aplicar en consola de migraciones/seeders si no hay org en contexto
        if (function_exists('current_org_id') && ($orgId = current_org_id())) {
            $builder->where($model->getTable().'.organization_id', $orgId);
        }
    }
}

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization()
    {
        static::addGlobalScope(new OrgScope);

        // Asignar org automáticamente al crear
        static::creating(function (Model $model) {
            if (function_exists('current_org_id') && !$model->getAttribute('organization_id')) {
                $model->setAttribute('organization_id', current_org_id());
            }
        });
    }
}
