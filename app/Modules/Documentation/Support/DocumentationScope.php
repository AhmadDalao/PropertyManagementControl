<?php

namespace App\Modules\Documentation\Support;

use App\Models\User;
use Illuminate\Support\Arr;

class DocumentationScope
{
    public function __construct(
        private readonly DocumentationAccess $access,
    ) {}

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function apply(User $actor, string $collection, array $item): array
    {
        $item = $this->workflowSteps($actor, $collection, $item);
        $item = $this->routes($actor, $item);

        return $this->stripPolicyMetadata($item);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function workflowSteps(User $actor, string $collection, array $item): array
    {
        if ($collection !== 'workflows' || ! is_array($item['steps'] ?? null)) {
            return $item;
        }

        $steps = [];

        foreach ($item['steps'] as $step) {
            if (! is_array($step)) {
                continue;
            }

            $step = $this->stringKeyed($step);

            if ($this->access->canSee($actor, $step)) {
                $steps[] = $step;
            }
        }

        $item['steps'] = $steps;

        return $item;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function routes(User $actor, array $item): array
    {
        if (! is_array($item['routes'] ?? null)) {
            return $item;
        }

        $routes = [];

        foreach ($item['routes'] as $route) {
            if (is_string($route) && $this->access->canSee($actor, ['route' => $route])) {
                $routes[] = $route;
            }
        }

        $item['routes'] = $routes;

        return $item;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function stripPolicyMetadata(array $item): array
    {
        $item = Arr::except($item, ['roles', 'module', 'tags']);

        if (is_array($item['steps'] ?? null)) {
            $steps = [];

            foreach ($item['steps'] as $step) {
                $steps[] = is_array($step)
                    ? Arr::except($this->stringKeyed($step), ['roles', 'module', 'tags'])
                    : $step;
            }

            $item['steps'] = $steps;
        }

        return $item;
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @return array<string, mixed>
     */
    private function stringKeyed(array $item): array
    {
        $normalized = [];

        foreach ($item as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
