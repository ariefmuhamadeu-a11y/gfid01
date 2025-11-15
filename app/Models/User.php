<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'employee_code', // 🔹 baru
        'name',
        'email',
        'password',
        'is_admin', // dari setup sebelumnya
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_owner' => 'boolean',
    ];

    public function employee()
    {
        // relasi: users.employee_code -> employees.code
        return $this->belongsTo(Employee::class, 'employee_code', 'code');
    }

    // helper: cek role dari employee
    public function employeeRole(): ?string
    {
        return $this->employee?->role;
    }

    public function role(): ?string
    {
        return $this->employee->role ?? null;
    }

    public function isOwner(): bool
    {
        return (bool) $this->is_owner;
    }

    public function isAdmin(): bool
    {
        // owner otomatis dianggap admin
        return $this->is_owner || $this->is_admin;
    }

/**
 * Cek apakah user punya satu role tertentu
 */
    public function hasRole(string $role): bool
    {
        if ($this->isOwner()) {
            return true; // owner boleh semua
        }

        return $this->role() === $role;
    }

/**
 * Cek apakah user punya salah satu dari banyak role
 */
    public function hasAnyRole(array $roles): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return in_array($this->role(), $roles, true);
    }

    public function getRoleAttribute(): ?string
    {
        if ($this->is_owner) {
            return 'owner';
        }

        if ($this->is_admin) {
            return 'admin';
        }

        return $this->employee?->role; // misal 'cutting', 'sewing', dll
    }

}
