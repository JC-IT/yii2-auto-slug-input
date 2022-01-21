<?php

declare(strict_types=1);

namespace JCIT\validators;

use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\BaseYii;
use yii\db\ActiveQuery;
use yii\validators\FilterValidator;
use yii\validators\RegularExpressionValidator;
use yii\validators\RequiredValidator;
use yii\validators\StringValidator;
use yii\validators\UniqueValidator;

class SlugValidator extends UniqueValidator
{
    /** @var array<int, int|string> */
    public array $idsToExclude = [];
    public int|null $max = null;
    public int|null $min = 8;
    public string $regexPattern = '/^[a-z0-9\-]+$/';
    public string $regexMessage;

    public function init(): void
    {
        $this->regexMessage = $this->regexMessage ?? BaseYii::t('JCIT', 'Can only contain letters, numbers and -.');
        if (is_array($this->targetAttribute)) {
            throw new InvalidConfigException('Target attribute can only be one attribute, set as string.');
        }
        $this->targetAttribute = strlen($this->targetAttribute) == 0 ? 'slug' : $this->targetAttribute;

        if (
            /** @phpstan-ignore-next-line */
            !isset($this->filter)
            && count($this->idsToExclude) > 0
        ) {
            $this->filter = function (ActiveQuery $query) {
                $query->andFilterWhere(['not', ['id' => $this->idsToExclude]]);
            };
        }

        parent::init();
    }

    /**
     * @phpstan-ignore-next-line
     * @param Model $model
     * @param string $attribute
     */
    public function validateAttribute(
        $model,
        $attribute
    ): void {
        $trimValidator = \Yii::createObject(FilterValidator::class, [['filter' => 'trim']]);
        $trimValidator->validateAttribute($model, $attribute);

        $strtolowerValidator = \Yii::createObject(FilterValidator::class, [['filter' => 'strtolower']]);
        $strtolowerValidator->validateAttribute($model, $attribute);

        $requiredValidator = \Yii::createObject(RequiredValidator::class);
        $requiredValidator->validateAttribute($model, $attribute);

        if (isset($this->min) || isset($this->max)) {
            $stringValidator = \Yii::createObject(StringValidator::class, [array_filter(['max' => $this->max, 'min' => $this->min])]);
            $stringValidator->validateAttribute($model, $attribute);
        }

        $regexValidator = \Yii::createObject(RegularExpressionValidator::class, [['pattern' => $this->regexPattern, 'message' => $this->regexMessage]]);
        $regexValidator->validateAttribute($model, $attribute);

        parent::validateAttribute($model, $attribute);
    }
}
