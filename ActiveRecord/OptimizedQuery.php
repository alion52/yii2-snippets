<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Пример оптимизации запросов в Yii2.
 */
class OptimizedQuery extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%products}}';
    }

    /**
     * Eager loading связей для избежания N+1.
     */
    public static function withRelations(): ActiveQuery
    {
        return self::find()->with([
            'category',
            'brand',
            'images',
        ]);
    }

    /**
     * Кеширование результата.
     */
    public static function cachedList(int $categoryId): array
    {
        $cacheKey = "products:category:{$categoryId}";
        $cache = \Yii::$app->cache;

        $data = $cache->get($cacheKey);
        if ($data === false) {
            $data = self::find()
                ->where(['category_id' => $categoryId, 'is_active' => 1])
                ->with(['images', 'brand'])
                ->asArray()
                ->all();

            $cache->set($cacheKey, $data, 3600);
        }

        return $data;
    }

    /**
     * Пакетная обработка больших выборок.
     */
    public static function processInBatches(int $batchSize = 500): \Generator
    {
        $offset = 0;

        while (true) {
            $batch = self::find()
                ->limit($batchSize)
                ->offset($offset)
                ->all();

            if (empty($batch)) {
                break;
            }

            yield $batch;

            $offset += $batchSize;
        }
    }

    public function getCategory(): ActiveQuery
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getBrand(): ActiveQuery
    {
        return $this->hasOne(Brand::class, ['id' => 'brand_id']);
    }

    public function getImages(): ActiveQuery
    {
        return $this->hasMany(ProductImage::class, ['product_id' => 'id'])
            ->orderBy(['sort' => SORT_ASC]);
    }
}
