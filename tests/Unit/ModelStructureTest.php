<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Structure;
use App\Models\Warehouse;
use App\Models\immobilization;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ModelStructureTest extends TestCase
{
    public function test_all_models_resolve_with_the_exact_file_and_class_name(): void
    {
        foreach (glob(dirname(__DIR__, 2).'/app/Models/*.php') as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            $this->assertTrue(class_exists($class), $class);
            $reflection = new ReflectionClass($class);
            $this->assertSame(basename($file, '.php'), $reflection->getShortName());
            $this->assertInstanceOf(Model::class, new $class);
        }
    }

    public function test_textual_primary_keys_are_not_auto_incremented(): void
    {
        foreach ([Product::class => 'id', Warehouse::class => 'warehouse',
            Structure::class => 'id', immobilization::class => 'immob_code'] as $class => $key) {
            $model = new $class;
            $this->assertSame($key, $model->getKeyName());
            $this->assertSame('string', $model->getKeyType());
            $this->assertFalse($model->getIncrementing());
        }
    }
}
