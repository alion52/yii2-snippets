<?php

namespace app\controllers;

use app\models\Payment;
use app\components\payment\AlfabankGateway;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Обработка вебхуков от Альфа-Банка.
 */
class PaymentWebhookController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Принимает callback от банка.
     */
    public function actionCallback(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $bankOrderId = $request->post('orderId');
        $status = (int) $request->post('status');

        // 1. Проверка подписи
        if (!$this->verifySignature($request->getRawBody(), $request->getHeaders()->get('X-Signature'))) {
            throw new BadRequestHttpException('Invalid signature');
        }

        // 2. Поиск платежа
        $payment = Payment::findOne(['bank_order_id' => $bankOrderId]);
        if (!$payment) {
            throw new NotFoundHttpException('Payment not found');
        }

        // 3. Защита от повторной обработки
        if ($payment->isFinalized()) {
            return $this->asJson(['status' => 'already_processed']);
        }

        // 4. Обработка статуса
        switch ($status) {
            case 1: $payment->markPaid(); break;
            case 2: $payment->markFailed(); break;
            case 3: $payment->markRefunded(); break;
            default: $payment->markUnknown();
        }

        // 5. Логирование
        Yii::info([
            'payment_id' => $payment->id,
            'status' => $status,
            'bank_order_id' => $bankOrderId,
        ], 'payment');

        return $this->asJson(['status' => 'ok']);
    }

    /**
     * Проверка HMAC-подписи банка.
     */
    private function verifySignature(string $body, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }
        $secret = Yii::$app->params['alfabank.secret'] ?? '';
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }
}
