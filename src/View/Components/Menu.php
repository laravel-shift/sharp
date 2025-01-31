<?php

namespace Code16\Sharp\View\Components;

use Code16\Sharp\Utils\Menu\SharpMenuItem;
use Code16\Sharp\Utils\Menu\SharpMenuItemLink;
use Code16\Sharp\Utils\Menu\SharpMenuItemSection;
use Code16\Sharp\Utils\Menu\SharpMenuItemSeparator;
use Code16\Sharp\View\Components\Menu\MenuSection;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Menu extends Component
{
    public string $title;
    public ?string $username;
    public ?string $currentEntityKey;
    public ?SharpMenuItemLink $currentEntityItem;
    public bool $hasGlobalFilters;

    public function __construct()
    {
        $this->title = config('sharp.name', 'Sharp');
        $this->username = sharp_user()->{config('sharp.auth.display_attribute', 'name')};
        $this->currentEntityKey = currentSharpRequest()->breadcrumb()->first()->key ?? null;
        $this->currentEntityItem = $this->currentEntityKey
            ? $this->getEntityMenuItem($this->currentEntityKey)
            : null;
        $this->hasGlobalFilters = sizeof(config('sharp.global_filters') ?? []) > 0;
    }

    public function render()
    {
        return view('sharp::components.menu', [
            'self' => $this,
        ]);
    }

    public function getItems(): Collection
    {
        $sharpMenu = config('sharp.menu', []) ?? [];

        $items = is_array($sharpMenu)
            ? $this->getItemFromLegacyConfig($sharpMenu)
            : app($sharpMenu)->build()->items();

        return $items
            ->filter(fn (SharpMenuItem $item) => $item->isSection()
                ? count((new MenuSection($item))->getItems()) > 0
                : $item->isAllowed()
            );
    }

    public function getEntityMenuItem(string $entityKey): ?SharpMenuItemLink
    {
        return $this->getFlattenedItems()
            ->first(fn (SharpMenuItem $item) => $item->isEntity() && $item->getEntityKey() === $entityKey
            );
    }

    public function getFlattenedItems(): Collection
    {
        return $this->getItems()
            ->map(function (SharpMenuItem $item) {
                if ($item->isSection()) {
                    return (new MenuSection($item))->getItems();
                }

                return $item;
            })
            ->flatten();
    }

    private function getItemFromLegacyConfig(array $sharpMenuConfig): Collection
    {
        // Sanitize legacy Sharp 6 config format to new Sharp 7 format
        return collect($sharpMenuConfig)
            ->map(function (array $itemConfig) {
                if ($itemConfig['entities'] ?? false) {
                    return tap(
                        new SharpMenuItemSection($itemConfig['label'] ?? null),
                        function (SharpMenuItemSection $section) use ($itemConfig) {
                            collect($itemConfig['entities'])
                                ->each(function (array $entityConfig) use (&$section) {
                                    if ($entityConfig['separator'] ?? false) {
                                        $section->addSeparator($entityConfig['label']);
                                    } elseif ($entityConfig['url'] ?? false) {
                                        $section->addExternalLink(
                                            $entityConfig['url'],
                                            $entityConfig['label'] ?? null,
                                            $entityConfig['icon'] ?? null,
                                        );
                                    } else {
                                        $section->addEntityLink(
                                            $entityConfig['entity'] ?? ($entityConfig['dashboard'] ?? null),
                                            $entityConfig['label'] ?? null,
                                            $entityConfig['icon'] ?? null,
                                        );
                                    }
                                });
                        },
                    );
                }

                if ($itemConfig['separator'] ?? false) {
                    return new SharpMenuItemSeparator($itemConfig['label']);
                }

                $item = new SharpMenuItemLink(
                    $itemConfig['label'] ?? null,
                    $itemConfig['icon'] ?? null,
                );
                if ($itemConfig['url'] ?? false) {
                    $item->setUrl($itemConfig['url']);
                } else {
                    $item->setEntity($itemConfig['entity'] ?? ($itemConfig['dashboard'] ?? null));
                }

                return $item;
            });
    }
}
