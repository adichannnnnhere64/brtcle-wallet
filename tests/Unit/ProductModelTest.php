<?php

use Adichan\Product\Interfaces\ProductRepositoryInterface;
use Adichan\Product\Models\Product;
use Adichan\Product\Models\ProductVariation;

beforeEach(function () {
    // Optional: keep if you want, or remove — migrations already run in TestCase
});

it('can create a product', function () {
    $product = Product::factory()->create([
        'name' => 'Test Product',
        'base_price' => 10.99,
        'type' => 'generic',
    ]);

    expect($product->name)->toBe('Test Product');
    expect($product->getPrice())->toBe(10.99);
});

it('can add variations to a product', function () {
    $product = Product::factory()->create();

    ProductVariation::factory()->create([
        'product_id' => $product->id,
        'attributes' => ['color' => 'red'], // ← ARRAY, NOT JSON STRING
        'price_override' => 15.99,
    ]);

    // Refresh product to get variations
    $product->load('variations');

    expect($product->variations)->toHaveCount(1);
    expect($product->getVariationPrice(['color' => 'red']))->toBe(15.99);
});

it('falls back to base price if no variation override', function () {
    $product = Product::factory()->create(['base_price' => 10.99]);

    ProductVariation::factory()->create([
        'product_id' => $product->id,
        'attributes' => ['size' => 'large'], // ← ARRAY
        'price_override' => null,
    ]);

    $product->load('variations');

    expect($product->getVariationPrice(['size' => 'large']))->toBe(10.99);
});

it('applies rules correctly for subclasses', function () {
    $repo = app(ProductRepositoryInterface::class);

    $coupon = Product::factory()->create(['type' => 'coupon', 'base_price' => 100.0]);
    $instance = $repo->find($coupon->id);
    expect($instance->applyRules(100.0))->toBe(90.0); // 10% off

    /* $vegetable = Product::factory()->create(['type' => 'vegetable', 'base_price' => 100.0]); */
    /* $instance = $repo->find($vegetable->id); */
    /* expect($instance->applyRules(90.0))->toBe(90.0); // 5% premium */
});
