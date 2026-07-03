<?php

declare(strict_types=1);

namespace LPWork\DebugDump;

use UnitEnum;

/**
 * Represents the debug dump inspector framework component.
 */
final readonly class DebugDumpInspector
{
    /**
     * Creates a new DebugDumpInspector instance.
     */
    public function __construct(
        private int $maxDepth = 6,
        private int $maxItems = 80,
        private int $maxStringLength = 500,
    ) {}

    /**
     * Performs the inspect operation.
     */
    public function inspect(mixed $value): DebugDumpNode
    {
        return $this->node($value, 0, []);
    }

    /**
     * @param non-empty-list<mixed> $values
     */
    public function inspectMany(array $values): DebugDumpNode
    {
        if (count($values) === 1) {
            return $this->inspect($values[0]);
        }

        $children = [];

        foreach ($values as $index => $value) {
            $children[] = $this->node($value, 1, [], (string) $index);
        }

        return new DebugDumpNode(
            type: 'values',
            summary: count($values) . ' values',
            meta: ['items' => (string) count($values)],
            children: $children,
        );
    }

    /**
     * @param array<int, true> $seenObjects
     */
    private function node(mixed $value, int $depth, array $seenObjects, ?string $name = null): DebugDumpNode
    {
        if ($value === null) {
            return new DebugDumpNode('null', 'null', name: $name);
        }

        if (is_bool($value)) {
            return new DebugDumpNode('bool', $value ? 'true' : 'false', name: $name);
        }

        if (is_int($value)) {
            return new DebugDumpNode('int', (string) $value, name: $name);
        }

        if (is_float($value)) {
            return new DebugDumpNode('float', (string) $value, name: $name);
        }

        if (is_string($value)) {
            return $this->stringNode($value, $name);
        }

        if (is_array($value)) {
            return $this->arrayNode($value, $depth, $seenObjects, $name);
        }

        if (is_object($value)) {
            return $this->objectNode($value, $depth, $seenObjects, $name);
        }

        if (is_resource($value)) {
            return new DebugDumpNode('resource', get_resource_type($value), name: $name);
        }

        return new DebugDumpNode(get_debug_type($value), get_debug_type($value), name: $name);
    }

    private function stringNode(string $value, ?string $name): DebugDumpNode
    {
        $length = strlen($value);
        $summary = $length > $this->maxStringLength
            ? substr($value, 0, $this->maxStringLength) . '...'
            : $value;

        return new DebugDumpNode('string', '"' . $summary . '"', ['length' => (string) $length], name: $name);
    }

    /**
     * @param array<array-key, mixed> $value
     * @param array<int, true> $seenObjects
     */
    private function arrayNode(array $value, int $depth, array $seenObjects, ?string $name): DebugDumpNode
    {
        $count = count($value);

        if ($depth >= $this->maxDepth) {
            return new DebugDumpNode('array', 'array(' . $count . ')', ['depth' => 'max'], name: $name);
        }

        $children = [];
        $index = 0;

        foreach ($value as $key => $item) {
            if ($index >= $this->maxItems) {
                $children[] = new DebugDumpNode('more', '...' . ($count - $index) . ' more item(s)');
                break;
            }

            $children[] = $this->node($item, $depth + 1, $seenObjects, (string) $key);
            $index++;
        }

        return new DebugDumpNode('array', 'array(' . $count . ')', ['items' => (string) $count], $children, $name);
    }

    /**
     * @param array<int, true> $seenObjects
     */
    private function objectNode(object $value, int $depth, array $seenObjects, ?string $nodeName): DebugDumpNode
    {
        if ($value instanceof UnitEnum) {
            return new DebugDumpNode('enum', $value::class . '::' . $value->name, name: $nodeName);
        }

        $id = spl_object_id($value);
        $class = $value::class;

        if (isset($seenObjects[$id])) {
            return new DebugDumpNode('object', $class, ['recursive' => 'true'], name: $nodeName);
        }

        if ($depth >= $this->maxDepth) {
            return new DebugDumpNode('object', $class, ['depth' => 'max'], name: $nodeName);
        }

        $seenObjects[$id] = true;
        $properties = get_mangled_object_vars($value);
        $children = [];
        $index = 0;

        foreach ($properties as $propertyName => $propertyValue) {
            if ($index >= $this->maxItems) {
                $children[] = new DebugDumpNode('more', '...' . (count($properties) - $index) . ' more property/properties');
                break;
            }

            if (!is_string($propertyName)) {
                $propertyName = (string) $propertyName;
            }

            $children[] = $this->node(
                $propertyValue,
                $depth + 1,
                $seenObjects,
                $this->propertyLabel($propertyName),
            );
            $index++;
        }

        return new DebugDumpNode('object', $class, ['properties' => (string) count($properties)], $children, $nodeName);
    }

    private function propertyLabel(string $name): string
    {
        $visibility = $this->propertyVisibility($name);
        $property = $this->propertyName($name);

        if ($visibility === 'public') {
            return $property;
        }

        return $property . ' (' . $visibility . ')';
    }

    private function propertyName(string $name): string
    {
        if (!str_contains($name, "\0")) {
            return $name;
        }

        $parts = explode("\0", $name);
        $last = array_key_last($parts);

        return $parts[$last];
    }

    private function propertyVisibility(string $name): string
    {
        if (!str_contains($name, "\0")) {
            return 'public';
        }

        if (str_starts_with($name, "\0*\0")) {
            return 'protected';
        }

        return 'private';
    }
}
