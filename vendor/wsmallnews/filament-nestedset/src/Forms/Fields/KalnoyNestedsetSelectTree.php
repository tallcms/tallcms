<?php

namespace Wsmallnews\FilamentNestedset\Forms\Fields;

use Closure;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Illuminate\Support\Collection;

class KalnoyNestedsetSelectTree extends SelectTree
{
    protected int | Closure | null $level = null;

    protected function buildTree(): Collection
    {
        $nullParentQuery = $this->getQuery()->clone()->whereIsRoot();
        $nonNullParentQuery = $this->getQuery()->clone()->hasParent()->withDepth();

        if ($this->withTrashed) {
            $nullParentQuery->withTrashed($this->withTrashed);
            $nonNullParentQuery->withTrashed($this->withTrashed);
        }

        $nullParentResults = $nullParentQuery->get();
        $nonNullParentResults = collect([]);
        if ($this->hasChildren()) {
            $nonNullParentResults = $nonNullParentQuery->get();

            if (! is_null($this->getLevel())) {
                // 只保留需要的层级
                $nonNullParentResults = $nonNullParentResults->filter(function ($item) {
                    return $item->depth < $this->getLevel();
                });
            }
        }

        // Combine the results from both queries
        $combinedResults = $nullParentResults->concat($nonNullParentResults);

        // Store results for additional functionality
        if ($this->storeResults) {
            $this->results = $combinedResults;
        }

        return $this->buildTreeFromResults($combinedResults);
    }

    private function buildTreeFromResults($results, $parent = null): Collection
    {
        // Assign the parent's null value to the $parent variable if it's not null
        if ($parent == null || $parent == $this->getParentNullValue()) {
            $parent = $this->getParentNullValue() ?? $parent;
        }

        // Create a collection to store the tree
        $tree = collect();

        // Create a mapping of results by their parent IDs for faster lookup
        $resultMap = [];

        // Group results by their parent IDs
        foreach ($results as $result) {
            $parentId = $result->{$this->getParentAttribute()};
            if (! isset($resultMap[$parentId])) {
                $resultMap[$parentId] = [];
            }
            $resultMap[$parentId][] = $result;
        }

        // Define disabled options
        $disabledOptions = $this->getDisabledOptions();

        // Define hidden options
        $hiddenOptions = $this->getHiddenOptions();

        // Recursively build the tree starting from the root (null parent)
        $rootResults = $resultMap[$parent] ?? [];
        foreach ($rootResults as $result) {
            // Build a node and add it to the tree
            $node = $this->buildNode($result, $resultMap, $disabledOptions, $hiddenOptions);
            $tree->push($node);
        }

        return $tree;
    }

    private function buildNode($result, $resultMap, $disabledOptions, $hiddenOptions): array
    {
        $key = $this->getCustomKey($result);

        // Create a node with 'name' and 'value' attributes
        $node = [
            'name' => $result->{$this->getTitleAttribute()},
            'value' => $key,
            'parent' => (string) $result->{$this->getParentAttribute()},
            'disabled' => in_array($key, $disabledOptions),
            'hidden' => in_array($key, $hiddenOptions),
        ];

        // Check if the result has children
        if (isset($resultMap[$key])) {
            $children = collect();
            // Recursively build child nodes
            foreach ($resultMap[$key] as $child) {
                // don't add the hidden ones
                if (in_array($this->getCustomKey($child), $hiddenOptions)) {
                    continue;
                }
                $childNode = $this->buildNode($child, $resultMap, $disabledOptions, $hiddenOptions);
                $children->push($childNode);
            }
            // Add children to the node
            $node['children'] = $children->toArray();
        }

        return $node;
    }

    public function level(int | Closure | null $level = null): static
    {
        $this->level = $level;

        return $this;
    }

    public function getLevel()
    {
        return $this->evaluate($this->level);
    }

    protected function hasChildren()
    {
        $level = $this->getLevel();

        return is_null($level) || $level > 1;
    }
}
