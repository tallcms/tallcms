<?php

declare(strict_types=1);

namespace TallCms\Cms\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PluginMigrationRepository
{
    protected string $table = 'tallcms_plugin_migrations';

    /**
     * Get all migrations for a plugin
     */
    public function getRan(string $vendor, string $slug): Collection
    {
        return DB::table($this->table)
            ->where('vendor', $vendor)
            ->where('slug', $slug)
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('migration');
    }

    /**
     * Get the last batch number for a plugin
     */
    public function getLastBatchNumber(string $vendor, string $slug): int
    {
        return (int) DB::table($this->table)
            ->where('vendor', $vendor)
            ->where('slug', $slug)
            ->max('batch');
    }

    /**
     * Get the next batch number for a plugin
     */
    public function getNextBatchNumber(string $vendor, string $slug): int
    {
        return $this->getLastBatchNumber($vendor, $slug) + 1;
    }

    /**
     * Log that a migration was run
     */
    public function log(string $vendor, string $slug, string $migration, int $batch): void
    {
        DB::table($this->table)->insert([
            'vendor' => $vendor,
            'slug' => $slug,
            'migration' => $migration,
            'batch' => $batch,
            'ran_at' => now(),
        ]);
    }

    /**
     * Remove a migration from the log
     */
    public function delete(string $vendor, string $slug, string $migration): void
    {
        DB::table($this->table)
            ->where('vendor', $vendor)
            ->where('slug', $slug)
            ->where('migration', $migration)
            ->delete();
    }

    /**
     * Get migrations for a specific batch
     */
    public function getMigrationsByBatch(string $vendor, string $slug, int $batch): Collection
    {
        return DB::table($this->table)
            ->where('vendor', $vendor)
            ->where('slug', $slug)
            ->where('batch', $batch)
            ->orderBy('migration', 'desc')
            ->pluck('migration');
    }

    /**
     * Get the last batch of migrations for a plugin
     */
    public function getLast(string $vendor, string $slug): Collection
    {
        $lastBatch = $this->getLastBatchNumber($vendor, $slug);

        if ($lastBatch === 0) {
            return collect();
        }

        return $this->getMigrationsByBatch($vendor, $slug, $lastBatch);
    }

    /**
     * Delete all migration records for a plugin
     */
    public function deleteAll(string $vendor, string $slug): int
    {
        return DB::table($this->table)
            ->where('vendor', $vendor)
            ->where('slug', $slug)
            ->delete();
    }

    /**
     * Check if a migration has been run
     */
    public function hasRun(string $vendor, string $slug, string $migration): bool
    {
        return DB::table($this->table)
            ->where('vendor', $vendor)
            ->where('slug', $slug)
            ->where('migration', $migration)
            ->exists();
    }

    /**
     * Get the batch number for a specific migration
     */
    public function getBatch(string $vendor, string $slug, string $migration): ?int
    {
        $result = DB::table($this->table)
            ->where('vendor', $vendor)
            ->where('slug', $slug)
            ->where('migration', $migration)
            ->first();

        return $result ? $result->batch : null;
    }

    /**
     * Get migration status for a plugin
     */
    public function getStatus(string $vendor, string $slug): Collection
    {
        return DB::table($this->table)
            ->where('vendor', $vendor)
            ->where('slug', $slug)
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->get();
    }

    /**
     * Check if the repository table exists
     */
    public function repositoryExists(): bool
    {
        return DB::getSchemaBuilder()->hasTable($this->table);
    }

    /**
     * Get the table name
     */
    public function getTable(): string
    {
        return $this->table;
    }
}
