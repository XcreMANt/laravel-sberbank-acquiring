<?php

namespace Avlyalin\SberbankAcquiring\Providers;

use Avlyalin\SberbankAcquiring\Client\ApiClient;
use Avlyalin\SberbankAcquiring\Client\ApiClientInterface;
use Avlyalin\SberbankAcquiring\Client\Client;
use Avlyalin\SberbankAcquiring\Client\Curl\Curl;
use Avlyalin\SberbankAcquiring\Client\Curl\CurlInterface;
use Avlyalin\SberbankAcquiring\Client\HttpClient;
use Avlyalin\SberbankAcquiring\Client\HttpClientInterface;
use Avlyalin\SberbankAcquiring\Commands\UpdateStatusCommand;
use Avlyalin\SberbankAcquiring\Factories\PaymentsFactory;
use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\AcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Repositories\AcquiringPaymentRepository;
use Avlyalin\SberbankAcquiring\Repositories\AcquiringPaymentStatusRepository;
use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class AcquiringServiceProvider extends ServiceProvider
{
	use HasConfig;

	/**
     * Migration list
     *
     * @var array
     */
	public $migrations = [
		[
			'table' => 'payment_operation_types',
			'file' => 'create_acquiring_payment_operation_types_table.php.stub'
		],
		[
			'table' => 'payment_statuses',
			'file' => 'create_acquiring_payment_statuses_table.php.stub'
		],
		[
			'table' => 'payment_systems',
			'file' => 'create_acquiring_payment_systems_table.php.stub'
		],
		[
			'table' => 'payments',
			'file' => 'create_acquiring_payments_table.php.stub'
		],
		[
			'table' => 'payment_operations',
			'file' => 'create_acquiring_payment_operations_table.php.stub'
		],
		[
			'table' => 'sberbank_payments',
			'file' => 'create_acquiring_sberbank_payments_table.php.stub'
		],
		[
			'table' => 'apple_pay_payments',
			'file' => 'create_acquiring_apple_pay_payments_table.php.stub'
		],
		[
			'table' => 'samsung_pay_payments',
			'file' => 'create_acquiring_samsung_pay_payments_table.php.stub'
		],
		[
			'table' => 'google_pay_payments',
			'file' => 'create_acquiring_google_pay_payments_table.php.stub'
		],
	];

    /**
     * Register services.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/sberbank-acquiring.php',
            'sberbank-acquiring'
        );

        $this->app->register(EventServiceProvider::class);

        $this->registerBindings();

        $this->registerCommands();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/sberbank-acquiring.php' => config_path('sberbank-acquiring.php'),
        ], 'config');

		$date = date('Y_m_d_His', time());
		foreach ($this->migrations as $index => $migration) {
			$tableName = $this->getTableName($migration['table']);
			$timestamp = substr($date, 0, -1).$index;
			$this->publishes([
				__DIR__.'/../../database/migrations/'.$migration['file'] => database_path("/migrations/{$timestamp}_create_{$tableName}_table.php"),
			], 'migrations');
		}
    }

    /**
     * Регистрация биндингов
     */
    private function registerBindings()
    {
        $this->app->bind(CurlInterface::class, Curl::class);
        $this->app->bind(HttpClientInterface::class, HttpClient::class);
        $this->app->bind(ApiClientInterface::class, function ($app) {
            $httpClient = $app->make(HttpClientInterface::class);
            return new ApiClient(['httpClient' => $httpClient]);
        });
        $this->app->singleton(PaymentsFactory::class, function ($app) {
            return new PaymentsFactory();
        });
        $this->app->singleton(AcquiringPaymentRepository::class, function ($app) {
            return new AcquiringPaymentRepository(new AcquiringPayment());
        });
        $this->app->singleton(AcquiringPaymentStatusRepository::class, function ($app) {
            return new AcquiringPaymentStatusRepository(new AcquiringPaymentStatus());
        });
        $this->app->bind(Client::class, Client::class);
    }

    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateStatusCommand::class,
            ]);
        }
    }
}
