<?php

namespace App\Models;

use App\Enum\SystemRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{

    protected $appends = ['label'];

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::headline($this->name),
        );
    }


    //functions

    //roles for hub user
    public static function getHubRoles()
    {
        $rolesToFind = [SystemRole::HubMember->value];

        if (auth()->user()->isSuperAdmin())
            $rolesToFind[] = SystemRole::HubManager->value;

        return Role::query()
            ->whereIn('name', $rolesToFind)
            ->get();
    }
}
