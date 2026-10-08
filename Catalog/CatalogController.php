<?php

namespace app\controllers;

use app\models\Category;
use app\models\search\ProductSearch;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Каталог с фильтрами, сортировкой и ЧПУ.
 */
class CatalogController extends Controller
{
    /**
     * /catalog/ — все товары.
     */
    public function actionIndex(): string
    {
        $search = new ProductSearch();
        $dataProvider = $search->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $search,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * /catalog/{slug}/ — товары категории.
     */
    public function actionCategory(string $slug): string
    {
        $category = Category::find()->where(['slug' => $slug, 'is_active' => true])->one();

        if (!$category) {
            throw new NotFoundHttpException('Категория не найдена');
        }

        $search = new ProductSearch();
        $search->category_id = $category->id;
        $dataProvider = $search->search(Yii::$app->request->queryParams);

        return $this->render('category', [
            'category' => $category,
            'searchModel' => $search,
            'dataProvider' => $dataProvider,
            'seo' => $this->seoForCategory($category),
        ]);
    }

    private function seoForCategory(Category $category): array
    {
        return [
            'title' => $category->seo_title ?: "{$category->name} — каталог",
            'description' => $category->seo_description ?: "Каталог товаров в категории {$category->name}",
            'h1' => $category->name,
        ];
    }
}
