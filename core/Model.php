<?php

declare(strict_types=1);

namespace Core;

/**
 * Base Model
 *
 * Provides ActiveRecord-style CRUD, a fluent query builder proxy, soft
 * deletes, automatic timestamps, and pagination for every model that
 * extends it.
 *
 * Usage example:
 *
 *   class User extends \Core\Model {
 *       protected string $table      = 'users';
 *       protected string $primaryKey = 'id';
 *       protected array  $fillable   = ['name', 'email', 'password'];
 *       protected bool   $timestamps = true;
 *       protected bool   $softDeletes = true;
 *   }
 *
 *   $user  = User::find(1);
 *   $users = User::where('active', 1)->orderBy('name')->paginate(20);
 */
abstract class Model
{
    // ------------------------------------------------------------------
    // Configuration (override in child classes)
    // ------------------------------------------------------------------

    /** Database table name. */
    protected string $table = '';

    /** Primary key column. */
    protected string $primaryKey = 'id';

    /** Mass-assignable columns. Empty = allow all. */
    protected array $fillable = [];

    /** Columns that are never mass-assigned. */
    protected array $guarded = ['id'];

    /** Whether to auto-manage created_at / updated_at columns. */
    protected bool $timestamps = true;

    /** Whether to use soft deletes (deleted_at column). */
    protected bool $softDeletes = false;

    /** Columns hidden from array / JSON serialisation. */
    protected array $hidden = [];

    /** Default column casts: column => type (int, float, bool, json, datetime). */
    protected array $casts = [];

    // ------------------------------------------------------------------
    // Instance state
    // ------------------------------------------------------------------

    /** The underlying data for this model instance. */
    protected array $attributes = [];

    /** Attributes as they were when loaded (for dirty checking). */
    protected array $original = [];

    /** Whether this model has been persisted. */
    protected bool $exists = false;

    // ------------------------------------------------------------------
    // Static query builder state (per-call, thread-safe is NOT a concern
    // for PHP's share-nothing architecture).
    // ------------------------------------------------------------------

    private static array $builderState = [];

    // ------------------------------------------------------------------
    // Constructor / factory
    // ------------------------------------------------------------------

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Create a new model instance and immediately persist it.
     */
    public static function create(array $attributes): static
    {
        $instance = new static();
        $instance->fill($attributes);
        $instance->save();

        return $instance;
    }

    // ------------------------------------------------------------------
    // CRUD: Find / All
    // ------------------------------------------------------------------

    /**
     * Find a record by its primary key.
     */
    public static function find(int|string $id): ?static
    {
        $instance = new static();
        $db       = Database::getInstance();

        $query = $db->table($instance->table)
            ->where($instance->primaryKey, $id);

        if ($instance->softDeletes) {
            $query->whereNull('deleted_at');
        }

        $row = $query->first();

        if ($row === false) {
            return null;
        }

        return $instance->hydrate($row);
    }

    /**
     * Find a record or throw a NotFoundException.
     */
    public static function findOrFail(int|string $id): static
    {
        $result = static::find($id);

        if ($result === null) {
            throw new \Core\Exceptions\NotFoundException(
                static::class . " with ID [{$id}] not found."
            );
        }

        return $result;
    }

    /**
     * Return all records (respects soft deletes).
     */
    public static function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $instance = new static();
        $db       = Database::getInstance();

        $query = $db->table($instance->table)->orderBy($orderBy, $direction);

        if ($instance->softDeletes) {
            $query->whereNull('deleted_at');
        }

        $rows = $query->get();

        return array_map(fn (array $row) => (new static())->hydrate($row), $rows);
    }

    // ------------------------------------------------------------------
    // CRUD: Save / Update / Delete
    // ------------------------------------------------------------------

    /**
     * Persist the model (INSERT or UPDATE depending on $this->exists).
     */
    public function save(): bool
    {
        $db   = Database::getInstance();
        $data = $this->prepareSaveData();

        if ($this->exists) {
            // UPDATE
            if ($this->timestamps) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $this->attributes['updated_at'] = $data['updated_at'];
            }

            $affected = $db->table($this->table)
                ->where($this->primaryKey, $this->getKey())
                ->update($data);

            $this->syncOriginal();

            return $affected > 0;
        }

        // INSERT
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $data['updated_at'] ?? $now;
            $this->attributes['created_at'] = $data['created_at'];
            $this->attributes['updated_at'] = $data['updated_at'];
        }

        $id = $db->table($this->table)->insert($data);

        $this->attributes[$this->primaryKey] = $id;
        $this->exists = true;
        $this->syncOriginal();

        return true;
    }

    /**
     * Mass-update attributes and save in one call.
     */
    public function updateAttributes(array $attributes): bool
    {
        $this->fill($attributes);

        return $this->save();
    }

    /**
     * Delete the record (or soft-delete if enabled).
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $db = Database::getInstance();

        if ($this->softDeletes) {
            $now = date('Y-m-d H:i:s');
            $this->attributes['deleted_at'] = $now;

            $db->table($this->table)
                ->where($this->primaryKey, $this->getKey())
                ->update(['deleted_at' => $now]);

            return true;
        }

        $db->table($this->table)
            ->where($this->primaryKey, $this->getKey())
            ->deleteRows();

        $this->exists = false;

        return true;
    }

    /**
     * Permanently delete a soft-deleted record.
     */
    public function forceDelete(): bool
    {
        $db = Database::getInstance();
        $db->table($this->table)
            ->where($this->primaryKey, $this->getKey())
            ->deleteRows();

        $this->exists = false;

        return true;
    }

    /**
     * Restore a soft-deleted record.
     */
    public function restore(): bool
    {
        if (!$this->softDeletes) {
            return false;
        }

        $db = Database::getInstance();
        $db->table($this->table)
            ->where($this->primaryKey, $this->getKey())
            ->update(['deleted_at' => null]);

        $this->attributes['deleted_at'] = null;

        return true;
    }

    // ------------------------------------------------------------------
    // Static query builder proxy
    // ------------------------------------------------------------------

    /**
     * Begin a fluent query on this model's table.
     *
     *   User::where('active', 1)->orderBy('name')->get();
     */
    public static function query(): ModelQueryBuilder
    {
        $instance = new static();

        return new ModelQueryBuilder($instance);
    }

    /**
     * Shorthand: User::where('col', 'val') without explicit ::query().
     */
    public static function where(string $column, mixed $operatorOrValue, mixed $value = null): ModelQueryBuilder
    {
        return static::query()->where($column, $operatorOrValue, $value);
    }

    /**
     * Shorthand for whereIn.
     */
    public static function whereIn(string $column, array $values): ModelQueryBuilder
    {
        return static::query()->whereIn($column, $values);
    }

    /**
     * Paginate model results.
     */
    public static function paginate(int $perPage = 15, ?int $page = null): array
    {
        return static::query()->paginate($perPage, $page);
    }

    // ------------------------------------------------------------------
    // Attribute access
    // ------------------------------------------------------------------

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function getAttribute(string $name): mixed
    {
        $value = $this->attributes[$name] ?? null;

        // Apply cast if defined.
        if (isset($this->casts[$name])) {
            $value = $this->castAttribute($name, $value);
        }

        // Check for an accessor method: getNameAttribute()
        $accessor = 'get' . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->{$accessor}($value);
        }

        return $value;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        // Check for a mutator method: setNameAttribute()
        $mutator = 'set' . str_replace('_', '', ucwords($name, '_')) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->{$mutator}($value);
            return;
        }

        $this->attributes[$name] = $value;
    }

    /**
     * Get the primary key value.
     */
    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Get the table name.
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the primary key column name.
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Whether the model uses soft deletes.
     */
    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes;
    }

    // ------------------------------------------------------------------
    // Serialisation
    // ------------------------------------------------------------------

    /**
     * Convert the model to an array, respecting $hidden.
     */
    public function toArray(): array
    {
        $data = $this->attributes;

        foreach ($this->hidden as $column) {
            unset($data[$column]);
        }

        // Apply casts.
        foreach ($this->casts as $column => $type) {
            if (array_key_exists($column, $data)) {
                $data[$column] = $this->castAttribute($column, $data[$column]);
            }
        }

        return $data;
    }

    /**
     * Convert the model to JSON.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_UNESCAPED_UNICODE);
    }

    // ------------------------------------------------------------------
    // Dirty checking
    // ------------------------------------------------------------------

    /**
     * Return attributes that have changed since the model was loaded.
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Whether any attribute has changed.
     */
    public function isDirty(?string $attribute = null): bool
    {
        if ($attribute !== null) {
            return array_key_exists($attribute, $this->getDirty());
        }

        return !empty($this->getDirty());
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Mass-assign attributes, respecting $fillable / $guarded.
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Whether a given attribute is mass-assignable.
     */
    private function isFillable(string $key): bool
    {
        if (in_array($key, $this->guarded, true)) {
            return false;
        }

        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable, true);
        }

        return true;
    }

    /**
     * Prepare the data array for INSERT / UPDATE, filtering out non-fillable
     * and primary-key columns.
     */
    private function prepareSaveData(): array
    {
        $data = [];

        foreach ($this->attributes as $key => $value) {
            if ($key === $this->primaryKey) {
                continue;
            }
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Hydrate a model instance from a database row.
     */
    private function hydrate(array $row): static
    {
        $this->attributes = $row;
        $this->exists     = true;
        $this->syncOriginal();

        return $this;
    }

    /**
     * Sync the original state with current attributes.
     */
    private function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    /**
     * Cast an attribute value to the configured type.
     */
    private function castAttribute(string $name, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this->casts[$name] ?? null) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string'          => (string) $value,
            'json', 'array'   => is_string($value) ? json_decode($value, true) : (array) $value,
            'datetime'        => $value instanceof \DateTimeInterface ? $value : new \DateTimeImmutable($value),
            default           => $value,
        };
    }
}


// ======================================================================
// ModelQueryBuilder - fluent query builder that returns Model instances
// ======================================================================

/**
 * Thin wrapper around Database's query builder that hydrates results into
 * Model instances.
 */
class ModelQueryBuilder
{
    private Database $db;
    private Model $model;
    private bool $withTrashed = false;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->db    = Database::getInstance();
        $this->db->table($model->getTable());

        // Auto-filter soft-deleted rows.
        if ($model->usesSoftDeletes()) {
            $this->db->whereNull('deleted_at');
        }
    }

    public function select(string ...$columns): static
    {
        $this->db->select(...$columns);
        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        $this->db->where($column, $operatorOrValue, $value);
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->db->whereIn($column, $values);
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->db->whereNull($column);
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->db->whereNotNull($column);
        return $this;
    }

    public function whereRaw(string $expression, array $binds = []): static
    {
        $this->db->whereRaw($expression, $binds);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->db->orderBy($column, $direction);
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->db->limit($limit);
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->db->offset($offset);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->db->join($table, $first, $operator, $second, $type);
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->db->leftJoin($table, $first, $operator, $second);
        return $this;
    }

    /**
     * Include soft-deleted rows in results.
     */
    public function withTrashed(): static
    {
        $this->withTrashed = true;
        // We need to re-initialise without the whereNull.
        $this->db->table($this->model->getTable());
        return $this;
    }

    /**
     * Only return soft-deleted rows.
     */
    public function onlyTrashed(): static
    {
        $this->db->table($this->model->getTable());
        $this->db->whereNotNull('deleted_at');
        return $this;
    }

    // ------------------------------------------------------------------
    // Terminals
    // ------------------------------------------------------------------

    /**
     * Get all matching rows as model instances.
     *
     * @return list<Model>
     */
    public function get(): array
    {
        $rows = $this->db->get();

        return array_map(
            fn (array $row) => $this->hydrateRow($row),
            $rows,
        );
    }

    /**
     * Get the first matching row as a model instance.
     */
    public function first(): ?Model
    {
        $row = $this->db->first();

        return $row !== false ? $this->hydrateRow($row) : null;
    }

    /**
     * Get a count of matching rows.
     */
    public function count(): int
    {
        return $this->db->count();
    }

    /**
     * Check existence.
     */
    public function exists(): bool
    {
        return $this->db->exists();
    }

    /**
     * Paginate and return hydrated models.
     */
    public function paginate(int $perPage = 15, ?int $page = null): array
    {
        $page   = $page ?? max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->db->paginate($perPage, $page);

        $result['data'] = array_map(
            fn (array $row) => $this->hydrateRow($row),
            $result['data'],
        );

        return $result;
    }

    /**
     * Delete matching rows (bulk).
     */
    public function delete(): int
    {
        if ($this->model->usesSoftDeletes() && !$this->withTrashed) {
            return $this->db->update(['deleted_at' => date('Y-m-d H:i:s')]);
        }

        return $this->db->deleteRows();
    }

    /**
     * Update matching rows (bulk).
     */
    public function update(array $data): int
    {
        return $this->db->update($data);
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    private function hydrateRow(array $row): Model
    {
        $model = new (get_class($this->model))();
        // Use reflection to call private hydrate.
        $ref = new \ReflectionMethod($model, 'hydrate');
        $ref->setAccessible(true);

        return $ref->invoke($model, $row);
    }
}
