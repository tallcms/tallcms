<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $permissionClass = 'Spatie\\Permission\\Models\\Permission';
        $roleClass = 'Spatie\\Permission\\Models\\Role';

        if (! class_exists($permissionClass) || ! class_exists($roleClass)) {
            return;
        }

        // Skip if permissions table hasn't been created yet (e.g. fresh install runs these in order)
        $tableName = config('permission.table_names.permissions', 'permissions');
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $guardName = config('tallcms.auth.guard', 'web');

        $permission = $permissionClass::firstOrCreate([
            'name' => 'Manage:CodeInjection',
            'guard_name' => $guardName,
        ]);

        foreach (['administrator', 'super_admin'] as $roleName) {
            $role = $roleClass::where('name', $roleName)
                ->where('guard_name', $guardName)
                ->first();

            if ($role && ! $role->hasPermissionTo('Manage:CodeInjection')) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        $permissionClass = 'Spatie\\Permission\\Models\\Permission';

        if (! class_exists($permissionClass)) {
            return;
        }

        $tableName = config('permission.table_names.permissions', 'permissions');
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $guardName = config('tallcms.auth.guard', 'web');

        $permissionClass::where('name', 'Manage:CodeInjection')
            ->where('guard_name', $guardName)
            ->delete();
    }
};
