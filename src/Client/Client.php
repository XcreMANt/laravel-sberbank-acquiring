<?php

declare(strict_types=1);

namespace Avlyalin\SberbankAcquiring\Client;

use Avlyalin\SberbankAcquiring\Traits\HasConfig;
use Avlyalin\SberbankAcquiring\Exceptions\ErrorResponseException;
use Avlyalin\SberbankAcquiring\Factories\PaymentsFactory;
use Avlyalin\SberbankAcquiring\Models\AcquiringPayment;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentOperationType;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentStatus;
use Avlyalin\SberbankAcquiring\Models\DictAcquiringPaymentSystem;
use Avlyalin\SberbankAcquiring\Repositories\AcquiringPaymentRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Client
{
    use HasConfig;

    /**
     * @var ApiClientInterface
     */
    private $apiClient;
    /**
     * @var PaymentsFactory
     */
    private $paymentsFactory;
    /**
     * @var AcquiringPaymentRepository
     */
    private $acquiringPaymentRepository;

    /**
     * @inheritDoc
     */
    public function __construct(
        ApiClientInterface $apiClient,
        PaymentsFactory $paymentsFactory,
        AcquiringPaymentRepository $acquiringPaymentRepository
    ) {
        $this->apiClient = $apiClient;
        $this->paymentsFactory = $paymentsFactory;
        $this->acquiringPaymentRepository = $acquiringPaymentRepository;
    }

    public function register(
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): AcquiringPayment {
        return $this->performRegister(
            DictAcquiringPaymentOperationType::REGISTER,
            $amount,
            $params,
            $method,
            $headers
        );
    }

    public function registerPreAuth(
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): AcquiringPayment {
        return $this->performRegister(
            DictAcquiringPaymentOperationType::REGISTER_PRE_AUTH,
            $amount,
            $params,
            $method,
            $headers
        );
    }

    public function deposit(
        int $acquiringPaymentId,
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): AcquiringPayment {
        // TODO
    }

    /**
     * @inheritDoc
     */
    public function reverse(
        $orderId,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement reverse() method.
    }

    /**
     * @inheritDoc
     */
    public function refund(
        $orderId,
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement refund() method.
    }

    /**
     * @inheritDoc
     */
    public function getOrderStatusExtended(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement getOrderStatusExtended() method.
    }

    /**
     * @inheritDoc
     */
    public function payWithApplePay(
        string $merchant,
        string $paymentToken,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement payWithApplePay() method.
    }

    /**
     * @inheritDoc
     */
    public function payWithSamsungPay(
        string $merchant,
        string $paymentToken,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement payWithSamsungPay() method.
    }

    /**
     * @inheritDoc
     */
    public function payWithGooglePay(
        string $merchant,
        string $paymentToken,
        int $amount,
        string $returnUrl,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement payWithGooglePay() method.
    }

    /**
     * @inheritDoc
     */
    public function getReceiptStatus(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement getReceiptStatus() method.
    }

    /**
     * @inheritDoc
     */
    public function bindCard(
        string $bindingId,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement bindCard() method.
    }

    /**
     * @inheritDoc
     */
    public function unBindCard(
        string $bindingId,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement unBindCard() method.
    }

    /**
     * @inheritDoc
     */
    public function getBindings(
        string $clientId,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement getBindings() method.
    }

    /**
     * @inheritDoc
     */
    public function getBindingsByCardOrId(
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement getBindingsByCardOrId() method.
    }

    /**
     * @inheritDoc
     */
    public function extendBinding(
        string $bindingId,
        int $newExpiry,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement extendBinding() method.
    }

    /**
     * @inheritDoc
     */
    public function verifyEnrollment(
        string $pan,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): array {
        // TODO: Implement verifyEnrollment() method.
    }

    /**
     * @param int $operationId
     * @param int $amount
     * @param array $params
     * @param string $method
     * @param array $headers
     *
     * @return AcquiringPayment
     * @throws \Avlyalin\SberbankAcquiring\Exceptions\JsonException
     * @throws \Exception
     */
    private function performRegister(
        int $operationId,
        int $amount,
        array $params = [],
        string $method = HttpClientInterface::METHOD_POST,
        array $headers = []
    ): AcquiringPayment {
        $returnUrl = $params['returnUrl'] ?? $this->getConfigParam('params.return_url');
        unset($params['returnUrl']);

        $params['failUrl'] = $params['failUrl'] ?? $this->getConfigParam('params.fail_url');

        $requestData = array_merge(['amount' => $amount, 'returnUrl' => $returnUrl], $params);

        $requestParams = $this->addAuthParams($params);

        $response = $this->apiClient->register(
            $amount,
            $returnUrl,
            $requestParams,
            $method,
            $headers
        );

        if ($response->isOk()) {
            $statusId = DictAcquiringPaymentStatus::REGISTERED;
        } else {
            $statusId = DictAcquiringPaymentStatus::ERROR;
        }

        $responseData = $response->getResponseArray();

        $acquiringPayment = $this->paymentsFactory->createAcquiringPayment([
            'system_id' => DictAcquiringPaymentSystem::SBERBANK,
            'bank_order_id' => $responseData['orderId'],
            'status_id' => $statusId,
        ]);

        $payment = $this->paymentsFactory->createSberbankPayment([
            'bank_form_url' => $responseData['formUrl'],
        ]);
        $payment->fillWithSberbankParams($requestData);

        $operation = $this->paymentsFactory->createPaymentOperation([
            'user_id' => Auth::id(),
            'type_id' => $operationId,
            'request_json' => $requestData,
            'response_json' => $responseData,
        ]);

        DB::transaction(function () use ($acquiringPayment, $payment, $operation) {
            $payment->saveOrFail();

            $acquiringPayment->payment()->associate($payment);
            $acquiringPayment->saveOrFail();

            $operation->payment()->associate($acquiringPayment);
            $operation->saveOrFail();
        });

        return $acquiringPayment;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws \Exception
     */
    private function addAuthParams(array $params = []): array
    {
        $authParams = [];
        $auth = $this->getConfigParam('auth');
        if (empty($auth['userName']) === false && empty($auth['password']) === false) {
            $authParams = [
                'userName' => $auth['userName'],
                'password' => $auth['password'],
            ];
        } elseif (empty($auth['token']) === false) {
            $authParams = ['token' => $auth['token']];
        }
        return array_merge($authParams, $params);
    }
}
