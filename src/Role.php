<?php

namespace Devdojo\Teams;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Role implements Arrayable, JsonSerializable
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public string $key,
        public string $name,
        public array $permissions = [],
        public string $description = '',
    ) {}

    /**
     * Set the role's human-friendly description.
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Determine whether the role grants the given permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array('*', $this->permissions, true)
            || in_array($permission, $this->permissions, true);
    }

    /**
     * @return array{key: string, name: string, description: string, permissions: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->permissions,
        ];
    }

    /**
     * @return array{key: string, name: string, description: string, permissions: array<int, string>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
