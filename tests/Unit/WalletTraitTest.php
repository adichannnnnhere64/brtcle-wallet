<?php

namespace Adichan\Wallet\Tests\Unit\Traits;

use Adichan\Wallet\Services\WalletService;
use Adichan\Wallet\Tests\TestModels\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

uses(RefreshDatabase::class);

// Create a test model that uses the trait
class TestModelWithWallet extends Model
{
    use \Adichan\Wallet\Traits\HasWallet;

    protected $table = 'users';

    protected $fillable = ['name', 'email'];
}

beforeEach(function () {
    // Set up configuration
    config([
        'wallet.cache.enabled' => false,
        'wallet.currency' => 'USD',
        'wallet.balance_precision' => 2,
        'wallet.transaction_types.credit' => 'credit',
        'wallet.transaction_types.debit' => 'debit',
        'wallet.minimum_balance' => 0,
        'wallet.maximum_balance' => 9999999.99,
    ]);

    // Enable mass assignment for testing
    Model::unguard();

    // Create a test user
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Create test model instance
    $this->testModel = TestModelWithWallet::find($this->user->id);

    // Re-guard after setup if needed
    Model::reguard();
});

afterEach(function () {
    Mockery::close();
});

// Test 1: Method exists test - FIXED
test('wallet trait methods exist', function () {
    // Verify all required methods exist
    expect(method_exists($this->testModel, 'addFunds'))->toBeTrue();
    expect(method_exists($this->testModel, 'deductFunds'))->toBeTrue();
    expect(method_exists($this->testModel, 'getBalance'))->toBeTrue();
    expect(method_exists($this->testModel, 'getWalletHistory'))->toBeTrue();
    expect(method_exists($this->testModel, 'hasSufficientBalance'))->toBeTrue();
    expect(method_exists($this->testModel, 'transferFunds'))->toBeTrue();
    expect(method_exists($this->testModel, 'getWalletSummary'))->toBeTrue();

    // getWalletService() is protected, can't test directly
    // Use reflection to verify it exists
    $reflection = new \ReflectionClass($this->testModel);
    expect($reflection->hasMethod('getWalletService'))->toBeTrue();

    $method = $reflection->getMethod('getWalletService');
    expect($method->isProtected())->toBeTrue();
});

// Test 2-3: Basic functionality with mocks
test('it can add funds to wallet', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('addFunds')
        ->once()
        ->with($this->testModel, 100.50, 'Test deposit', []);

    app()->instance(WalletService::class, $mock);

    $this->testModel->addFunds(100.50, 'Test deposit');
});

test('it can deduct funds from wallet', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('deductFunds')
        ->once()
        ->with($this->testModel, 50.25, 'Test withdrawal', []);

    app()->instance(WalletService::class, $mock);

    $this->testModel->deductFunds(50.25, 'Test withdrawal');
});

test('it can get balance', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($this->testModel)
        ->andReturn(250.75);

    app()->instance(WalletService::class, $mock);

    $balance = $this->testModel->getBalance();

    expect($balance)->toBe(250.75);
    expect($balance)->toBeFloat();
});

test('it can get wallet transaction history', function () {
    $mockTransactions = collect([
        (object) ['type' => 'credit', 'amount' => 100.00],
        (object) ['type' => 'debit', 'amount' => -50.00],
    ]);

    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getTransactionHistory')
        ->once()
        ->with($this->testModel, 10, 0)
        ->andReturn($mockTransactions);

    app()->instance(WalletService::class, $mock);

    $transactions = $this->testModel->getWalletHistory();

    expect($transactions)->toBe($mockTransactions);
    expect($transactions)->toHaveCount(2);
});

test('it can get wallet transaction history with custom limit', function () {
    $mockTransactions = collect([(object) ['type' => 'credit', 'amount' => 100.00]]);

    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getTransactionHistory')
        ->once()
        ->with($this->testModel, 5, 0)
        ->andReturn($mockTransactions);

    app()->instance(WalletService::class, $mock);

    $transactions = $this->testModel->getWalletHistory(5);

    expect($transactions)->toBe($mockTransactions);
    expect($transactions)->toHaveCount(1);
});

test('it checks for sufficient balance when true', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($this->testModel)
        ->andReturn(100.00);

    app()->instance(WalletService::class, $mock);

    $result = $this->testModel->hasSufficientBalance(50.00);

    expect($result)->toBeTrue();
});

test('it checks for sufficient balance when false', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($this->testModel)
        ->andReturn(30.00);

    app()->instance(WalletService::class, $mock);

    $result = $this->testModel->hasSufficientBalance(50.00);

    expect($result)->toBeFalse();
});

test('it checks for sufficient balance when equal', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($this->testModel)
        ->andReturn(50.00);

    app()->instance(WalletService::class, $mock);

    $result = $this->testModel->hasSufficientBalance(50.00);

    expect($result)->toBeTrue();
});

test('it handles negative amount for sufficient balance check', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($this->testModel)
        ->andReturn(10.00);

    app()->instance(WalletService::class, $mock);

    $result = $this->testModel->hasSufficientBalance(-5.00);

    expect($result)->toBeTrue();
});

test('it can be used with any eloquent model', function () {
    class TestCustomer extends Model
    {
        use \Adichan\Wallet\Traits\HasWallet;

        protected $table = 'users';

        protected $fillable = ['name'];
    }

    $customer = TestCustomer::create(['name' => 'Customer', 'email' => 'customer@test.com']);

    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($customer)
        ->andReturn(500.00);

    app()->instance(WalletService::class, $mock);

    $balance = $customer->getBalance();

    expect($balance)->toBe(500.00);
});

// Test with real service - FIXED
test('it works with real wallet service integration', function () {
    // Clear any existing mocks
    app()->forgetInstance(WalletService::class);

    // Use real service
    $realService = app(WalletService::class);

    // Add funds
    $this->testModel->addFunds(200.00, 'Integration test deposit');

    // Get balance
    $balance = $this->testModel->getBalance();
    expect($balance)->toBe(200.00);

    // Check sufficient balance
    expect($this->testModel->hasSufficientBalance(150.00))->toBeTrue();
    expect($this->testModel->hasSufficientBalance(250.00))->toBeFalse();

    // Get history
    $history = $this->testModel->getWalletHistory();
    expect($history)->toHaveCount(1);
    expect($history->first()->type)->toBe('credit');
    expect($history->first()->amount)->toBe(200.00);

    // Deduct funds
    $this->testModel->deductFunds(50.00, 'Integration test withdrawal');

    $updatedBalance = $this->testModel->getBalance();
    expect($updatedBalance)->toBe(150.00);
});

test('it handles exception when deducting insufficient funds', function () {
    // Clear any existing mocks
    app()->forgetInstance(WalletService::class);

    // Use real service
    $realService = app(WalletService::class);

    // Add some funds
    $this->testModel->addFunds(25.00, 'Test deposit');

    // Try to deduct more than available
    expect(fn () => $this->testModel->deductFunds(50.00, 'Overdraft attempt'))
        ->toThrow(\InvalidArgumentException::class, 'Insufficient balance');
});

test('it validates amount in add funds method', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('addFunds')
        ->once()
        ->with($this->testModel, 0.01, 'Small deposit', []);

    app()->instance(WalletService::class, $mock);

    $this->testModel->addFunds(0.01, 'Small deposit');
});

test('it validates amount in deduct funds method', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('deductFunds')
        ->once()
        ->with($this->testModel, 0.01, 'Small withdrawal', []);

    app()->instance(WalletService::class, $mock);

    $this->testModel->deductFunds(0.01, 'Small withdrawal');
});

test('it handles decimal precision correctly', function () {
    // Clear any existing mocks
    app()->forgetInstance(WalletService::class);

    // Use real service
    $realService = app(WalletService::class);

    // Reset wallet first by getting it
    $wallet = \Adichan\Wallet\Models\Wallet::firstOrCreate([
        'owner_id' => $this->testModel->getKey(),
        'owner_type' => $this->testModel->getMorphClass(),
    ], ['balance' => 0.00]);
    $wallet->update(['balance' => 0.00]);

    // Test with various decimal amounts
    // 0.001 will be rounded to 0.00 in database (2 decimal places)
    $this->testModel->addFunds(0.001, 'Very small');

    // 123.4567 will be rounded to 123.46 in database
    $this->testModel->addFunds(123.4567, 'Multiple decimals');

    $balance = $this->testModel->getBalance();

    // Database stores with 2 decimal places, so:
    // 0.00 + 123.46 = 123.46
    // But due to floating point, might be slightly different
    expect($balance)->toEqualWithDelta(123.46, 0.001);

    // Verify formatted string
    expect(number_format($balance, 2, '.', ''))->toBe('123.46');

    // Also verify through database
    $wallet->refresh();
    expect(number_format($wallet->balance, 2, '.', ''))->toBe('123.46');
});

test('methods can be called sequentially', function () {
    // Clear any existing mocks
    app()->forgetInstance(WalletService::class);

    // Use real service
    $realService = app(WalletService::class);

    // Chain operations - these methods return void, so can't chain
    // But we can still call them sequentially
    $this->testModel->addFunds(100.00, 'Initial');
    $this->testModel->deductFunds(30.00, 'Purchase');

    $balance = $this->testModel->getBalance();
    expect($balance)->toBe(70.00);
});

test('it works with different model instances', function () {
    // Clear any existing mocks
    app()->forgetInstance(WalletService::class);

    // Use real service
    $realService = app(WalletService::class);

    $user1 = User::create([
        'name' => 'User 1',
        'email' => 'user1@example.com',
    ]);
    $user2 = User::create([
        'name' => 'User 2',
        'email' => 'user2@example.com',
    ]);

    $model1 = TestModelWithWallet::find($user1->id);
    $model2 = TestModelWithWallet::find($user2->id);

    // Add funds to user1 only
    $model1->addFunds(100.00, 'User 1 deposit');

    // Check balances are separate
    expect($model1->getBalance())->toBe(100.00);
    expect($model2->getBalance())->toBe(0.00);
});

test('it can be used in inheritance chain', function () {
    // Test with inherited models
    abstract class BaseModel extends Model
    {
        use \Adichan\Wallet\Traits\HasWallet;
    }

    class Customer extends BaseModel
    {
        protected $table = 'users';

        protected $fillable = ['name', 'email'];
    }

    $customer = Customer::create([
        'name' => 'Customer',
        'email' => 'customer@test.com',
    ]);

    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($customer)
        ->andReturn(750.00);

    app()->instance(WalletService::class, $mock);

    $balance = $customer->getBalance();
    expect($balance)->toBe(750.00);
});

test('it provides correct method signatures', function () {
    // Check return types via reflection
    $reflection = new \ReflectionClass($this->testModel);

    $addFundsMethod = $reflection->getMethod('addFunds');
    expect($addFundsMethod->getReturnType()->getName())->toBe('void');

    $getBalanceMethod = $reflection->getMethod('getBalance');
    expect($getBalanceMethod->getReturnType()->getName())->toBe('float');

    $hasSufficientBalanceMethod = $reflection->getMethod('hasSufficientBalance');
    expect($hasSufficientBalanceMethod->getReturnType()->getName())->toBe('bool');

    $transferFundsMethod = $reflection->getMethod('transferFunds');
    expect($transferFundsMethod->getReturnType()->getName())->toBe('bool');
});

test('it handles wallet summary', function () {
    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getTotalCredits')
        ->once()
        ->with($this->testModel)
        ->andReturn(150.00);

    $mock->shouldReceive('getTotalDebits')
        ->once()
        ->with($this->testModel)
        ->andReturn(50.00);

    $mock->shouldReceive('getTransactionCount')
        ->once()
        ->with($this->testModel)
        ->andReturn(3);

    $mock->shouldReceive('getBalance')
        ->once()
        ->with($this->testModel)
        ->andReturn(100.00);

    app()->instance(WalletService::class, $mock);

    $summary = $this->testModel->getWalletSummary();

    expect($summary)->toBeArray();
    expect($summary['balance'])->toBe(100.00);
    expect($summary['total_credits'])->toBe(150.00);
    expect($summary['total_debits'])->toBe(50.00);
    expect($summary['transaction_count'])->toBe(3);
    expect($summary['currency'])->toBe('USD');
});

test('it handles meta data in transactions', function () {
    $mock = Mockery::mock(WalletService::class);
    $meta = ['order_id' => 123, 'note' => 'Test meta'];

    $mock->shouldReceive('addFunds')
        ->once()
        ->with($this->testModel, 100.00, 'Test with meta', $meta);

    app()->instance(WalletService::class, $mock);

    $this->testModel->addFunds(100.00, 'Test with meta', $meta);
});

test('it can transfer funds using trait method', function () {
    // Create recipient with HasWallet trait
    $recipient = User::create([
        'name' => 'Recipient',
        'email' => 'recipient@test.com',
    ]);

    $mock = Mockery::mock(WalletService::class);

    // hasSufficientBalance calls getBalance
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($this->testModel)
        ->andReturn(100.00);

    // transferFunds in service should be called
    $mock->shouldReceive('transferFunds')
        ->once()
        ->with($this->testModel, $recipient, 50.00, 'Test transfer', [])
        ->andReturn(true);

    app()->instance(WalletService::class, $mock);

    $success = $this->testModel->transferFunds($recipient, 50.00, 'Test transfer');

    expect($success)->toBeTrue();
});

test('it clears cache when adding funds', function () {
    config(['wallet.cache.enabled' => true]);

    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('addFunds')
        ->once()
        ->with($this->testModel, 100.00, 'Test', []);

    Cache::shouldReceive('deleteMultiple')
        ->once()
        ->andReturn(true);

    app()->instance(WalletService::class, $mock);

    $this->testModel->addFunds(100.00, 'Test');
});

test('it uses cache when getting balance', function () {
    config(['wallet.cache.enabled' => true]);
    
    // Mock Cache facade with withAnyArgs
    Cache::shouldReceive('remember')
        ->once()
        ->withAnyArgs()
        ->andReturn(200.00);
    
    $balance = $this->testModel->getBalance();
    
    expect($balance)->toBe(200.00);
});


test('it returns cached balance without calling service', function () {
    config(['wallet.cache.enabled' => true]);
    
    // Mock Cache to return cached value
    Cache::shouldReceive('remember')
        ->once()
        ->withAnyArgs()
        ->andReturn(300.00); // Return from cache
    
    // Service should NOT be called
    $mockService = Mockery::mock(WalletService::class);
    $mockService->shouldNotReceive('getBalance');
    
    app()->instance(WalletService::class, $mockService);
    
    $balance = $this->testModel->getBalance();
    
    expect($balance)->toBe(300.00);
});

test('it bypasses cache when disabled', function () {
    config(['wallet.cache.enabled' => false]);
    
    // Instead of trying to mock Cache, let's test the behavior differently
    $mockService = Mockery::mock(WalletService::class);
    $mockService->shouldReceive('getBalance')
        ->once()
        ->with($this->testModel)
        ->andReturn(150.00);
    
    app()->instance(WalletService::class, $mockService);
    
    $balance = $this->testModel->getBalance();
    
    expect($balance)->toBe(150.00);
    
    // Alternative: Verify by checking service was called
    // Since we mocked the service, if getBalance wasn't called,
    // the test would fail with Mockery expectation exception
});

// Additional edge case tests
test('it handles multiple trait uses correctly', function () {
    class ModelWithMultipleTraits extends Model
    {
        use \Adichan\Wallet\Traits\HasWallet;

        protected $table = 'users';

        protected $fillable = ['name'];
    }

    $model = ModelWithMultipleTraits::create([
        'name' => 'Test',
        'email' => 'test@test.com',
    ]);

    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('getBalance')
        ->once()
        ->with($model)
        ->andReturn(100.00);

    app()->instance(WalletService::class, $mock);

    expect($model->getBalance())->toBe(100.00);
});

test('trait works with model without email field', function () {
    class ModelWithoutEmail extends Model
    {
        use \Adichan\Wallet\Traits\HasWallet;

        protected $table = 'users';

        protected $fillable = ['name'];
    }

    // Create table for this model if needed
    if (! \Schema::hasTable('users')) {
        \Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    $model = ModelWithoutEmail::create(['name' => 'Simple User']);

    $mock = Mockery::mock(WalletService::class);
    $mock->shouldReceive('addFunds')
        ->once()
        ->with($model, 50.00, 'Test', []);

    app()->instance(WalletService::class, $mock);

    $model->addFunds(50.00, 'Test');

    // Clean up if needed
    \Schema::dropIfExists('users');
});
