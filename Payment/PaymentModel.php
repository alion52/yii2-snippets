<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Модель платежа с полным набором статусов.
 *
 * @property int $id
 * @property string $bank_order_id
 * @property int $order_id
 * @property int $amount
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class Payment extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_UNKNOWN = 'unknown';

    public static function tableName(): string
    {
        return '{{%payments}}';
    }

    public function rules(): array
    {
        return [
            [['bank_order_id', 'order_id', 'amount'], 'required'],
            [['order_id', 'amount'], 'integer'],
            [['bank_order_id', 'status'], 'string', 'max' => 255],
            ['status', 'in', 'range' => array_keys(self::statuses())],
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Ожидание',
            self::STATUS_PAID => 'Оплачен',
            self::STATUS_FAILED => 'Отказ',
            self::STATUS_REFUNDED => 'Возврат',
            self::STATUS_UNKNOWN => 'Неизвестно',
        ];
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_REFUNDED,
        ], true);
    }

    public function markPaid(): void
    {
        $this->status = self::STATUS_PAID;
        $this->save(false, ['status', 'updated_at']);
    }

    public function markFailed(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->save(false, ['status', 'updated_at']);
    }

    public function markRefunded(): void
    {
        $this->status = self::STATUS_REFUNDED;
        $this->save(false, ['status', 'updated_at']);
    }

    public function markUnknown(): void
    {
        $this->status = self::STATUS_UNKNOWN;
        $this->save(false, ['status', 'updated_at']);
    }
}
