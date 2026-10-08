<?php

namespace app\components\url;

use app\models\Category;
use yii\base\BaseObject;
use yii\web\UrlRuleInterface;
use yii\web\Request;

/**
 * ЧПУ-правило для категорий каталога.
 *
 * /catalog/{slug}/ → CatalogController::actionCategory($slug)
 */
class CatalogUrlRule extends BaseObject implements UrlRuleInterface
{
    public string $pattern = '/catalog/{slug}/';
    public string $route = 'catalog/category';

    public function parseRequest($manager, $request): array|false
    {
        if (!preg_match('#^catalog/([a-z0-9-]+)/?$#', $request->pathInfo, $m)) {
            return false;
        }

        $slug = $m[1];
        $category = Category::find()->where(['slug' => $slug, 'is_active' => true])->one();

        if (!$category) {
            return false;
        }

        return [$this->route, ['slug' => $slug]];
    }

    public function createUrl($manager, $route, $params): string|false
    {
        if ($route !== $this->route || empty($params['slug'])) {
            return false;
        }

        return "catalog/{$params['slug']}/";
    }
}
