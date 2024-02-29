<?php

namespace Console\Commands\Database\Adapters;

/**
 * Base Factory Class
 * -----------------
 * Provides methods to run factories in other structures. (Requires faker)
 */
abstract class Factory
{
    /**
     * Faker class instance
     * @var \Faker\Generator
     */
    public $faker;

    /**
     * Laravel Str Facade
     * @var \Illuminate\Support\Str
     */
    public $str;

    /**
     * Generated factory data
     */
    protected $data;

    /**
     * The name of the factory's corresponding model.
     * @var string
     */
    public $model = null;

    public function __construct()
    {
        if (class_exists(\Faker\Factory::class)) {
            $this->faker = \Faker\Factory::create();
        }

        if (class_exists(\Illuminate\Support\Str::class)) {
            $this->str = \Illuminate\Support\Str::class;
        }
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [];
    }

    /**
     * Create a number of records based on definition
     *
     * @param int $number The number of records to create
     * @return self
     */
    public function create(int $number): Factory
    {
        $data = [];

        for ($i = 0; $i < $number; $i++) {
            $data[] = $this->definition();
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Create a relationship with another factory
     *
     * @param \Leaf\Factory $factory The instance of the factory to tie to
     * @param array|string $primaryKey The primary key for that factory's table
     * @throws \Exception
     * @throws \Throwable
     */
    public function has(Factory $factory, $primaryKey = null): Factory
    {
        if (count($this->data) === 0) {
            $this->data[] = $this->definition();
        }

        $dataToOverride = [];
        $model = $this->model ?? $this->getModelName();

        if (!$primaryKey) {
            $primaryKey = strtolower($this->getModelName() . '_id');
            $primaryKey = str_replace('\app\models\\', '', $primaryKey);
        }

        if (is_array($primaryKey)) {
            $dataToOverride = $primaryKey;
        } else {
            $key = explode('_', $primaryKey);
            if (count($key) > 1) {
                unset($key[0]);
            }
            $key = implode($key);

            $primaryKeyData = $this->data[\rand(0, count($this->data) - 1)][$key] ?? null;
            $primaryKeyData = $primaryKeyData ?? $model::all()[\rand(0, count($model::all()) - 1)][$key];

            $dataToOverride[$primaryKey] = $primaryKeyData;
        }

        $factory->save($dataToOverride);

        return $this;
    }

    /**
     * Save created records in db
     *
     * @param array $override Override data to save
     *
     * @return true
     * @throws \Exception
     */
    public function save(array $override = []): bool
    {
        $model = $this->model ?? $this->getModelName();

        if (count($this->data) === 0) {
            $this->data[] = $this->definition();
        }

        foreach ($this->data as $item) {
            $item = array_merge($item, $override);

            $model = new $model;
            foreach ($item as $key => $value) {
                $model->{$key} = $value;
            }
            $model->save();
        }

        return true;
    }

    /**
     * Return created records
     *
     * @param array|null $override Override data to save
     *
     * @return array
     */
    public function get(array $override = null): array
    {
        if (count($this->data) === 0) {
            $this->data[] = $this->definition();
        }

        if ($override) {
            foreach ($this->data as $item) {
                $item = array_merge($item, $override);
            }
        }

        return $this->data;
    }

    /**
     * Get the default model name
     * @throws \Exception
     */
    public function getModelName(): string
    {
        $class = get_class($this);
        $modelClass = '\App\Models' .
            $this->str::studly(str_replace(['App\Database\Factories', 'Factory'], '', $class));

        if (!class_exists($modelClass)) {
            throw new \Exception('Couldn\'t retrieve model for '
                . $class . '. Add a \$model attribute to fix this.');
        }

        return $modelClass;
    }
}
