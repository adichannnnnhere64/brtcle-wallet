<?php

use Adichan\Product\Interfaces\ProductRepositoryInterface;

it('works burat', function () {

    $repo = app(ProductRepositoryInterface::class);

    expect(true)->toBe(true);

});
