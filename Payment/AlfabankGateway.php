<?php

namespace app\components\payment;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

/**
 * Полный цикл эквайринга Альфа-Банка.
 *
 * Инициализация платежа → платёжная форма → вебхуки → возвраты → повторные платежи.
 */
class AlfabankGateway extends Component
{
    public string $username = '';
    public string $password = '';
    public string $gateway = 'https://pay.alfabank.ru/payment/rest/'\;
    public int $timeout = 30;

    private Client $client;

    public function init(): void
    {
        parent::init();
        $this->client = new Client([
            'baseUrl' => $this->gateway,
            'transport' => 'yii\httpclient\CurlTransport',
            'requestConfig' => ['format' => Client::FORMAT_URLENCODED],
            'responseConfig' => ['format' => Client::FORMAT_JSON],
        ]);
    }

    /**
     * Регистрация платежа. Возвращает formUrl для редиректа.
     */
    public function registerOrder(array $order): array
    {
        $response = $this->client->post('register.do', array_merge([
            'userName' => $this->username,
            'password' => $this->password,
        ], $order))->send();

        if (!$response->isOk) {
            throw new \RuntimeException('Bank gateway error: ' . $response->content);
        }

        $data = $response->data;

        if (!empty($data['errorCode'])) {
            throw new \RuntimeException('Bank error: ' . $data['errorMessage']);
        }

        return $data;
    }

    /**
     * Получение расширенного статуса заказа.
     */
    public function getOrderStatusExtended(string $bankOrderId): array
    {
        $response = $this->client->post('getOrderStatusExtended.do', [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $bankOrderId,
        ])->send();

        return $response->isOk ? $response->data : [];
    }

    /**
     * Возврат средств (полный или частичный).
     */
    public function refund(string $bankOrderId, int $amount): array
    {
        $response = $this->client->post('refund.do', [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $bankOrderId,
            'amount' => $amount,
        ])->send();

        return $response->isOk ? $response->data : [];
    }

    /**
     * Повторная оплата по сохранённому orderId (если банк поддерживает).
     */
    public function repeatPayment(string $bankOrderId): array
    {
        $response = $this->client->post('paymentOrder.do', [
            'userName' => $this->username,
            'password' => $this->password,
            'orderId' => $bankOrderId,
        ])->send();

        return $response->isOk ? $response->data : [];
    }
}
