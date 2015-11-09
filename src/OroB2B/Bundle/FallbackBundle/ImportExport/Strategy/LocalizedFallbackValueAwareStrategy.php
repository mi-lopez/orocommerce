<?php

namespace OroB2B\Bundle\FallbackBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

class LocalizedFallbackValueAwareStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var string */
    protected $localizedFallbackValueClass;

    /**
     * @param string $localizedFallbackValueClass
     */
    public function setLocalizedFallbackValueClass($localizedFallbackValueClass)
    {
        $this->localizedFallbackValueClass = $localizedFallbackValueClass;
    }

    /** {@inheritdoc} */
    protected function beforeProcessEntity($entity)
    {
        $existingEntity = $this->findExistingEntity($entity);
        if (!$existingEntity) {
            return parent::beforeProcessEntity($entity);
        }

        $fields = $this->fieldHelper->getFields(ClassUtils::getClass($existingEntity), true);
        foreach ($fields as $field) {
            if ($this->isLocalizedFallbackValue($field)) {
                $fieldName = $field['name'];
                $this->mapCollections(
                    $this->fieldHelper->getObjectValue($entity, $fieldName),
                    $this->fieldHelper->getObjectValue($existingEntity, $fieldName)
                );
            }
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterProcessEntity($entity)
    {
        $fields = $this->fieldHelper->getFields(ClassUtils::getClass($entity), true);
        foreach ($fields as $field) {
            if ($this->isLocalizedFallbackValue($field)) {
                $this->setLocaleKeys($entity, $field);
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param $field
     * @return bool
     */
    protected function isLocalizedFallbackValue($field)
    {
        return $this->fieldHelper->isRelation($field)
            && is_a($field['related_entity_name'], $this->localizedFallbackValueClass, true);
    }

    /**
     * @param object $entity
     * @param array $field
     * @throws \Exception
     */
    protected function setLocaleKeys($entity, array $field)
    {
        /** @var Collection|LocalizedFallbackValue[] $localizedFallbackValues */
        $localizedFallbackValues = $this->fieldHelper->getObjectValue($entity, $field['name']);

        foreach ($localizedFallbackValues as $value) {
            $code = $value->getLocale() ? $value->getLocale()->getCode() : 'default';
            $localizedFallbackValues->removeElement($value);
            $localizedFallbackValues->set($code, $value);
        }
    }

    /**
     * @param Collection $importedCollection
     * @param Collection $sourceCollection
     */
    protected function mapCollections(Collection $importedCollection, Collection $sourceCollection)
    {
        if ($importedCollection->isEmpty()) {
            return;
        }

        if ($sourceCollection->isEmpty()) {
            return;
        }

        $importedCollection
            ->map(
                function (LocalizedFallbackValue $importedValue) use ($sourceCollection) {
                    $sourceValues = $sourceCollection
                        ->filter(
                            function (LocalizedFallbackValue $sourceValue) use ($importedValue) {
                                if ($sourceValue->getLocale() === $importedValue->getLocale()) {
                                    return true;
                                }

                                return $sourceValue->getLocale()
                                    && $importedValue->getLocale()
                                    && $sourceValue->getLocale()->getCode() === $importedValue->getLocale()->getCode();
                            }
                        );

                    if (!$sourceValues->isEmpty()) {
                        /** @var LocalizedFallbackValue $sourceValue */
                        $sourceValue = $sourceValues->first();

                        $this->fieldHelper->setObjectValue($importedValue, 'id', $sourceValue->getId());
                    }
                }
            );
    }

    /**
     * {@inheritdoc}
     *
     * No need to search LocalizedFallbackValue by identity fields, consider entities without ids as new
     */
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        if (is_a($entityName, $this->localizedFallbackValueClass, true)) {
            return null;
        }

        return parent::findEntityByIdentityValues($entityName, $identityValues);
    }
}
