<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The search handler with additional check for product visibility.
 */
class ProductVisibilityLimitedSearchHandler extends SearchHandler
{
    protected RequestStack $requestStack;
    protected ProductManager $productManager;
    protected FrontendHelper $frontendHelper;
    protected ProductSearchRepository $searchRepository;
    protected LocalizationHelper $localizationHelper;
    protected bool $allowConfigurableProducts = false;

    public function __construct(
        string $entityName,
        RequestStack $requestStack,
        ProductManager $productManager,
        ProductSearchRepository $searchRepository,
        LocalizationHelper $localizationHelper,
        FrontendHelper $frontendHelper
    ) {
        parent::__construct($entityName, ['sku', 'defaultName.string']);
        $this->requestStack = $requestStack;
        $this->productManager = $productManager;
        $this->searchRepository = $searchRepository;
        $this->localizationHelper = $localizationHelper;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function convertItem($item)
    {
        $result = [];

        if ($this->idFieldName) {
            $result[$this->idFieldName] = $this->getPropertyValue($this->idFieldName, $item);
        }

        if (\is_object($item) && method_exists($item, 'getSelectedData')) {
            $selectedData = $item->getSelectedData();
            if (isset($selectedData['sku'], $selectedData['name'])) {
                $result += [
                    'sku'                => $selectedData['sku'],
                    'defaultName.string' => $selectedData['name']
                ];
            }
        } elseif ($item instanceof Product) {
            $result += [
                'sku'                => $item->getSku(),
                'defaultName.string' => (string)$this->localizationHelper->getLocalizedValue($item->getNames())
            ];
        } else {
            throw new InvalidArgumentException('Given item could not be converted');
        }

        return $result;
    }

    /**
     * Enables configurable products selection.
     * In most forms configurable products require additional option selection which is not implemented yet, thus they
     * are disabled by default, but can be enabled in forms where no additional functionality for selection is needed.
     */
    public function enableConfigurableProducts(): void
    {
        $this->allowConfigurableProducts = true;
    }

    /**
     * {@inheritDoc}
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->entityRepository || !$this->idFieldName) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && !$this->frontendHelper->isFrontendUrl($request->getPathInfo())) {
            $params = (array)$request->get(ProductSelectType::DATA_PARAMETERS);
            $queryBuilder = $this->getOrmSearchQuery($search, $firstResult, $maxResults, $params);

            return $this->aclHelper->apply($queryBuilder)->getResult();
        }

        return $this->filterSearchResult(
            $this->getSearchQuery($search, $firstResult, $maxResults)->getResult()->getElements()
        );
    }

    protected function getOrmSearchQuery(
        string $search,
        int $firstResult,
        int $maxResults,
        array $params
    ): QueryBuilder {
        $queryBuilder = $this->getProductRepository()->getSearchQueryBuilder($search, $firstResult, $maxResults);
        $this->productManager->restrictQueryBuilder($queryBuilder, $params);

        if (!$this->allowConfigurableProducts) {
            $queryBuilder->andWhere($queryBuilder->expr()->neq('p.type', ':configurable_type'))
                ->setParameter('configurable_type', Product::TYPE_CONFIGURABLE);
        }

        return $queryBuilder;
    }

    protected function getSearchQuery(string $search, int $firstResult, int $maxResults): SearchQueryInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $skus = null !== $request ? (array)$request->request->get('sku') : [];
        if ($skus) {
            $searchQuery = $this->searchRepository->getFilterSkuQuery($skus);
        } else {
            $searchQuery = $this->searchRepository->getSearchQueryBySkuOrName($search, $firstResult, $maxResults);
        }

        if (!$this->allowConfigurableProducts) {
            $searchQuery->addWhere(
                Criteria::expr()->neq('type', Product::TYPE_CONFIGURABLE)
            );
        }

        // Add marker `autocomplete_record_id` to be able to determine query context in listeners
        $searchQuery->addSelect('integer.system_entity_id as autocomplete_record_id');

        return $searchQuery;
    }

    /**
     * @param SearchResultItem[] $items
     *
     * @return SearchResultItem[]
     */
    protected function filterSearchResult(array $items): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request && \count($items) > 1 && \count((array)$request->request->get('sku')) === 1) {
            $productName = $request->request->get('productName');
            if ($productName) {
                $matchedItems = [];
                foreach ($items as $item) {
                    $itemData = $item->getSelectedData();
                    if ($itemData['name'] === $productName) {
                        $matchedItems[] = $item;
                    }
                }
                if ($matchedItems) {
                    $items = $matchedItems;
                }
            }
        }

        return $items;
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->entityRepository;
    }
}
