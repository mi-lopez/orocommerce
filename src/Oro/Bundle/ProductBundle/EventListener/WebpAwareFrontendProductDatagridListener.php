<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Adds imageWebp column.
 */
class WebpAwareFrontendProductDatagridListener
{
    private const COLUMN_IMAGE_WEBP = 'imageWebp';

    private DataGridThemeHelper $themeHelper;
    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;
    private WebpConfiguration $webpConfiguration;

    public function __construct(
        DataGridThemeHelper $themeHelper,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        WebpConfiguration $webpConfiguration
    ) {
        $this->themeHelper = $themeHelper;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->webpConfiguration = $webpConfiguration;
    }

    public function onPreBuild(PreBuild $event): void
    {
        if (!$this->webpConfiguration->isEnabledIfSupported()) {
            return;
        }

        $config = $event->getConfig();

        $columns[self::COLUMN_IMAGE_WEBP] = [
            'label' => 'oro.product.webp_image.label'
        ];

        $config->offsetAddToArrayByPath('[columns]', $columns);
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        if (!$this->webpConfiguration->isEnabledIfSupported()) {
            return;
        }

        $this->addProductImages($event, $event->getRecords());
    }

    /**
     * @param SearchResultAfter $event
     * @param ResultRecord[] $records
     */
    private function addProductImages(SearchResultAfter $event, array $records): void
    {
        $gridName = $event->getDatagrid()->getName();
        $theme = $this->themeHelper->getTheme($gridName);
        switch ($theme) {
            case DataGridThemeHelper::VIEW_GRID:
                $imageFilter = 'product_large';
                break;
            case DataGridThemeHelper::VIEW_LIST:
            case DataGridThemeHelper::VIEW_TILES:
                $imageFilter = 'product_medium';
                break;
            default:
                return;
        }

        $noImagePath = false;
        foreach ($records as $record) {
            $productImageUrl = $record->getValue('image_' . $imageFilter . '_webp');
            if (!$productImageUrl) {
                if (false === $noImagePath) {
                    $noImagePath = $this->imagePlaceholderProvider->getPath($imageFilter, 'webp');
                }
                $productImageUrl = $noImagePath;
            }

            $imageData[self::COLUMN_IMAGE_WEBP] = $productImageUrl;

            $record->addData($imageData);
        }
    }
}
