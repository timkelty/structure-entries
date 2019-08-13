<?php

namespace timkelty\craftcms\structureentries\fields;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\ElementHelper;
use craft\helpers\ArrayHelper;
use timkelty\craftcms\structureentries\Plugin;
use timkelty\craftcms\structureentries\web\assets\field\FieldAssets;

abstract class BaseStructureRelationField extends \craft\fields\BaseRelationField
{
    // Properties
    // =========================================================================

    /**
     * @var int|null Branch limit
     */
    public $branchLimit;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->allowMultipleSources = false;
        $this->sortable = false;
        $this->allowLimit = false;
        $this->inputJsClass = 'Craft.StructureSelectInput';
        $this->inputTemplate = 'structure-entries/_includes/forms/elementSelect';
        $this->settingsTemplate = 'structure-entries/_components/fieldtypes/structureelementfieldsettings';
    }

    /**
     * @inheritdoc
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'branchLimit';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (is_array($value)) {
            /** @var ElementInterface[] $elements */
            $elements = static::elementType()::find()
                ->siteId($this->targetSiteId($element))
                ->id(array_values(array_filter($value)))
                ->anyStatus()
                ->all();

            // Fill in any gaps
            $structuresService = Plugin::getInstance()->structures;
            $structuresService->fillGapsInElements($elements);

            // Enforce the branch limit
            if ($this->branchLimit) {
                $structuresService->applyBranchLimitToElements($elements, $this->branchLimit);
            }

            $value = ArrayHelper::getColumn($elements, 'id');
        }

        return parent::normalizeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    protected function inputTemplateVariables($value = null, ElementInterface $element = null): array
    {
        $variables = parent::inputTemplateVariables($value, $element);
        $variables['branchLimit'] = $this->branchLimit;
        $variables['structure'] = true;

        return $variables;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Make sure the field is set to a valid element source
        if ($this->source) {
            $source = ElementHelper::findSource(static::elementType(), $this->source, 'field');
        }

        if (empty($source)) {
            return '<p class="error">' . Craft::t('app', 'This field is not set to a valid source.') . '</p>';
        }

        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(FieldAssets::class);

        return parent::getInputHtml($value, $element);
    }
}