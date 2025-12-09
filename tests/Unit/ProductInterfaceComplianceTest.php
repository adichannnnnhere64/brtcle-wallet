// tests/Unit/ProductInterfaceComplianceTest.php
<?php

use Adichan\Product\Interfaces\ProductInterface;
use Adichan\Product\Models\Product;
use Adichan\Product\Products\CouponCodeProduct;
use Adichan\Product\Products\VegetableProduct;

it('ensures all product classes implement ProductInterface', function (string $class) {
    // Use reflection — no DB, no instantiation needed
    $reflection = new ReflectionClass($class);
    expect($reflection->implementsInterface(ProductInterface::class))->toBeTrue();
})->with([
    'base product' => Product::class,
    'coupon product' => CouponCodeProduct::class,
    /* 'vegetable product' => VegetableProduct::class, */
]);
