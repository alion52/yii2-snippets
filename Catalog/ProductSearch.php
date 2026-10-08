<?php

namespace app\models\search;

use app\models\Product;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * Поиск товаров с фильтрами.
 */
class ProductSearch extends Product
{
    public ?int $category_id = null;
    public ?int $min_price = null;
    public ?int $max_price = null;
    public ?string $brand = null;
    public ?string $sort = null;

    public function rules(): array
    {
        return [
            [['category_id', 'min_price', 'max_price'], 'integer'],
            [['brand', 'sort'], 'string', 'max' => 100],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Product::find()->with(['category', 'brand']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 12],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $this->applyFilters($query);
        $this->applySort($query);

        return $dataProvider;
    }

    private function applyFilters(ActiveQuery $query): void
    {
        $query->andFilterWhere(['category_id' => $this->category_id]);
        $query->andFilterWhere(['brand_id' => $this->brand]);
        $query->andFilterWhere(['>=', 'price', $this->min_price]);
        $query->andFilterWhere(['<=', 'price', $this->max_price]);

        // Товары не в наличии — в конце
        $query->orderBy([
            new \yii\db\Expression('CASE WHEN stock > 0 THEN 0 ELSE 1 END') => SORT_ASC,
        ]);
    }

    private function applySort(ActiveQuery $query): void
    {
        switch ($this->sort) {
            case 'price_asc':
                $query->addOrderBy(['price' => SORT_ASC]);
                break;
            case 'price_desc':
                $query->addOrderBy(['price' => SORT_DESC]);
                break;
            case 'newest':
                $query->addOrderBy(['created_at' => SORT_DESC]);
                break;
            default:
                $query->addOrderBy(['id' => SORT_DESC]);
        }
    }
}
