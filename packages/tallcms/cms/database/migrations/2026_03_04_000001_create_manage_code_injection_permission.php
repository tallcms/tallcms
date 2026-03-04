<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $guardName = config('tallcms.auth.guard', 'web');
        $permissionClass = 'Spatie\\Permission\\Models\\Permission';
        $roleClass = 'Spatie\\Permission\\Models\\Role';

        if (! class_exists($permissionClass) || ! class_exists($roleClass)) {
            return;
        }

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

        $permissionClass::where('name', 'Manage:CodeInjection')->delete();
    }
};
