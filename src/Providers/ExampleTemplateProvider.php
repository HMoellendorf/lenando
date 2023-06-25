<?php

namespace LenandoCatalogExport\Providers;

use LenandoCatalogExport\Callbacks\ExampleSkuCallback;
use LenandoCatalogExport\Converters\CSVResultConverter;
use LenandoCatalogExport\Mutators\ExamplePostMutator;
use Plenty\Modules\Catalog\Containers\CatalogTemplateFieldContainer;
use Plenty\Modules\Catalog\Containers\Filters\CatalogFilterBuilderContainer;
use Plenty\Modules\Catalog\Containers\TemplateGroupContainer;
use Plenty\Modules\Catalog\Contracts\CatalogMutatorContract;
use Plenty\Modules\Catalog\Models\CombinedTemplateField;
use Plenty\Modules\Catalog\Models\ComplexTemplateField;
use Plenty\Modules\Catalog\Models\SimpleTemplateField;
use Plenty\Modules\Catalog\Models\TemplateGroup;
use Plenty\Modules\Catalog\Templates\Providers\AbstractGroupedTemplateProvider;
use Plenty\Modules\Pim\Catalog\Variation\Filters\FilterBuilderFactory;
use Plenty\Modules\Catalog\Contracts\CatalogDynamicConfigContract;
use LenandoCatalogExport\DynamicConfig\ExampleDynamicConfig;
use Plenty\Modules\Catalog\Services\Converter\Containers\ResultConverterContainer;

/**
 * Class ExampleTemplateProvider
 * @package LenandoCatalogExport\Providers
 */
class ExampleTemplateProvider extends AbstractGroupedTemplateProvider
{
    public function getTemplateGroupContainer(): TemplateGroupContainer
    {
        /** @var TemplateGroupContainer $templateGroupContainer */
        $templateGroupContainer = pluginApp(TemplateGroupContainer::class);

        // Simple fields

        /** @var TemplateGroup $simpleGroup */
        $simpleGroup = pluginApp(TemplateGroup::class,
            [
                "identifier" => "groupOne",
                "label" => "Simple fields" // In a productive plugin this should be translated
            ]);

        /** @var SimpleTemplateField $name */
        $name = pluginApp(SimpleTemplateField::class, [
            'name',
            'name',
            'Produktname', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'itemText-name1',
                    'id' => null,
                    'isCombined' => false,
                    'key' => "name1",
                    'type' => "text",
                    'lang' => "de",
                    'value' => null
                ]
            ]
        ]);
        
        /** @var SimpleTemplateField $name */
        $description = pluginApp(SimpleTemplateField::class, [
            'description',
            'description',
            'Beschreibung', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'itemText-description',
                    'id' => null,
                    'isCombined' => false,
                    'key' => "description",
                    'type' => "text",
                    'lang' => "de",
                    'value' => null
                ]
            ]
        ]);
        

        /** @var SimpleTemplateField $price */
       $price = pluginApp(SimpleTemplateField::class, [
           'price',
           'price',
           'Preis', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'salesPrice-1',
                    'id' => 1,
                    'isCombined' => false,
                    'key' => "price",
                    'type' => "sales-price",
                    'value' => null
                ]
            ]
        ]);
        
        /** @var SimpleTemplateField $price */
       $image = pluginApp(SimpleTemplateField::class, [
           'produktUrl',
           'produktUrl',
           'Bildlink', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'image-variationImages-single',
                    'id' => "variationImages",
                    'isCombined' => false,
                    'key' => "single",
                    'type' => "variation-images",
                    'value' => null,
                    'imageEntity' => "url",
                    'imagePosition' => "0"
                ]
            ]
        ]);
        
        /** @var SimpleTemplateField $price */
       $manufacturer = pluginApp(SimpleTemplateField::class, [
           'manufactuerer',
           'manufactuerer',
           'Hersteller', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'item-manufacturerName',
                    'id' => null,
                    'isCombined' => false,
                    'key' => "manufacturer.name",
                    'type' => "item",
                    'value' => null
                ]
            ]
        ]);
        
        /** @var SimpleTemplateField $link */
        $link = pluginApp(SimpleTemplateField::class, [
            'link',
            'link',
            'Produktlink', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'item-manufacturerName',
                    'id' => null,
                    'isCombined' => false,
                    'key' => "manufacturer.name",
                    'type' => "item",
                    'value' => null
                ]
            ]
        ]);
        
        /** @var SimpleTemplateField $sku */
        $ean = pluginApp(SimpleTemplateField::class, [
            'barcode',
            'barcode',
            'EAN', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'barcode-1',
                    'id' => 1,
                    'isCombined' => false,
                    'key' => "code",
                    'type' => "barcode-code",
                    'value' => null
                ]
            ]
        ]);
        
        /** @var SimpleTemplateField $sku */
        $shipping = pluginApp(SimpleTemplateField::class, [
            'shipping',
            'shipping',
            'Versandkosten', // In a productive plugin this should be translated
            true
        ]);
        
        /** @var SimpleTemplateField $sku */
        $baseprice = pluginApp(SimpleTemplateField::class, [
            'baseprice',
            'baseprice',
            'Grundpreis', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'variation-mayShowUnitPrice',
                    'id' => null,
                    'isCombined' => false,
                    'key' => "mayShowUnitPrice",
                    'type' => "variation",
                    'value' => null
                ]
            ]
        ]);
    
        /** @var SimpleTemplateField $stock */
        $stock = pluginApp(SimpleTemplateField::class, [
            'stock',
            'stock',
            'Bestand', // In a productive plugin this should be translated
            true,
            false,
            false,
            [],
            [
                [
                    'fieldId' => 'stock-0',
                    'id' => 0,
                    'isCombined' => false,
                    'key' => null,
                    'type' => "stock",
                    'value' => null
                ]
            ]
        ]);

        $simpleGroup->addGroupField($name);
        $simpleGroup->addGroupField($description);
        $simpleGroup->addGroupField($price);
        $simpleGroup->addGroupField($image);
        $simpleGroup->addGroupField($manufacturer);
        $simpleGroup->addGroupField($link);
        $simpleGroup->addGroupField($ean);
        $simpleGroup->addGroupField($shipping);
        $simpleGroup->addGroupField($baseprice);
        $simpleGroup->addGroupField($shipping);
        $simpleGroup->addGroupField($stock);

        $templateGroupContainer->addGroup($simpleGroup);

        // Complex field

        /** @var TemplateGroup $complexGroup */
        $complexGroup = pluginApp(TemplateGroup::class,
            [
                "identifier" => "groupTwo",
                "label" => "Complex fields" // In a productive plugin this should be translated
            ]);

        /** @var ComplexTemplateField $name */
        $category = pluginApp(ComplexTemplateField::class, [
            'category',
            'category',
            'Category', // In a productive plugin this should be translated
            pluginApp(ExampleCategoryMappingValueProvider::class),
            true
        ]);

        $complexGroup->addGroupField($category);
        $templateGroupContainer->addGroup($complexGroup);

        // Combined field

        /** @var TemplateGroup $combinedGroup */
        $combinedGroup = pluginApp(TemplateGroup::class,
            [
                "identifier" => "groupThree",
                "label" => "Combined fields" // In a productive plugin this should be translated
            ]);

        /** @var CatalogTemplateFieldContainer $simpleContainer */
        $simpleContainer = pluginApp(CatalogTemplateFieldContainer::class);

        /** @var SimpleTemplateField $name */
        $barcode = pluginApp(SimpleTemplateField::class, [
            'barcode',
            'barcode',
            'Barcode',
            true
        ]);

        $simpleContainer->addField($barcode);

        /** @var CombinedTemplateField $name */
        $barcodeType = pluginApp(CombinedTemplateField::class, [
            'barcodeType',
            'barcodeType',
            'Barcode type', // In a productive plugin this should be translated
            pluginApp(ExampleBarcodeTypeMappingValueProvider::class),
            $simpleContainer
        ]);

        $combinedGroup->addGroupField($barcodeType);
        $templateGroupContainer->addGroup($combinedGroup);

        return $templateGroupContainer;
    }

    public function getFilterContainer(): CatalogFilterBuilderContainer
    {
        /** @var CatalogFilterBuilderContainer $container */
        $container = pluginApp(CatalogFilterBuilderContainer::class);
        /** @var FilterBuilderFactory $filterBuilderFactory */
        $filterBuilderFactory = pluginApp(FilterBuilderFactory::class);

        $variationIsActiveFilter = $filterBuilderFactory->variationIsActive();
        $variationIsActiveFilter->setShouldBeActive(true);
        $container->addFilterBuilder($variationIsActiveFilter);

        return $container;
    }

    public function getCustomFilterContainer(): CatalogFilterBuilderContainer
    {
        /** @var CatalogFilterBuilderContainer $container */
        $container = pluginApp(CatalogFilterBuilderContainer::class);
        /** @var FilterBuilderFactory $filterBuilderFactory */
        $filterBuilderFactory = pluginApp(FilterBuilderFactory::class);

        $itemHasIdsFilter = $filterBuilderFactory->itemHasAtLeastOneId();
        $container->addFilterBuilder($itemHasIdsFilter);

        return $container;
    }

    public function isPreviewable(): bool
    {
        // If you are not sure what this does please check the guide for DynamicConfig before setting this to true
        // In your productive plugin
        return true;
    }

    public function getPostMutator(): CatalogMutatorContract
    {
        return pluginApp(ExamplePostMutator::class);
    }

    public function DefaultResultConverterContainer(): ResultConverterContainer
    {
        /** @var ResultConverterContainer $container */
        $container = pluginApp(ResultConverterContainer::class);
        /** @var CSVResultConverter $csvConverter */
        $csvConverter = pluginApp(CSVResultConverter::class);
        $container->addResultConverter($csvConverter);
        return $container;
    }
}
