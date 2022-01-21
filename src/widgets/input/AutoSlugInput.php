<?php

declare(strict_types=1);

namespace JCIT\widgets\input;

use kartik\builder\Form;
use yii\base\InvalidConfigException;
use yii\bootstrap4\InputWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class AutoSlugInput extends InputWidget
{
    public string $baseAttribute;

    protected function getInput(): string
    {
        return Form::widget([
            'formName' => $this->model->formName(),
            'attributes' => [
                $this->attribute => ArrayHelper::merge(array_filter([
                    'type' => Form::INPUT_TEXT,
                    'value' => $this->value,
                ]), $this->options),
            ]
        ]);
    }

    public function init(): void
    {
        if (!$this->hasModel()) {
            throw new InvalidConfigException('This widget only works with a model.');
        }

        parent::init();
    }

    public function run(): string
    {
        parent::run();

        $baseInputName = Html::getInputName($this->model, $this->baseAttribute);
        $slugInputName = Html::getInputName($this->model, $this->attribute);
        $this->view->registerJs(
            <<<JS
const baseInput = $('[name="{$baseInputName}"]');
const slugInput = $('[name="{$slugInputName}"]');
slugInput.data('slugChanged', false);

slugInput.on('input', function() {
  slugInput.data('slugChanged', true);
});

baseInput.on('input', function() {
  if (!slugInput.data('slugChanged')) {
    let result = baseInput.val();
    result = result.replace(/^\s+|\s+$/g, '');
    result = result.toLowerCase();
    
    const from = "àáãäâèéëêìíïîòóöôùúüûñç·/_,:;";
    const to = "aaaaaeeeeiiiioooouuuunc------";
    for (var i=0, l=from.length ; i<l ; i++) {
        result = result.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }
    
    result = result.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
        .replace(/\s+/g, '-') // collapse whitespace and replace by -
        .replace(/-+/g, '-'); // collapse dashes
    
    slugInput.val(result);
  }
});
JS
        );

        return $this->getInput();
    }
}
