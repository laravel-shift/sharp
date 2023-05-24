<?php

namespace Code16\Sharp\EntityList\Traits\Utils;

use Code16\Sharp\EntityList\Commands\Command;
use Illuminate\Support\Collection;

trait CommonCommandUtils
{
    protected function appendCommandsToConfig(Collection $commandHandlers, array &$config, string $positionKey, $instanceId = null): void
    {
        $commandHandlers
            ->each(function (Command $handler) use (&$config, $instanceId, $positionKey) {
                $handler->buildCommandConfig();

                $config['commands'][$positionKey][$handler->groupIndex()][] = [
                    'key' => $handler->getCommandKey(),
                    'label' => $handler->label(),
                    'description' => $handler->getDescription(),
                    'type' => $handler->type(),
                    'instance_selection' => $handler->type() === 'entity' 
                        ? $handler->getInstanceSelectionMode()
                        : null,
                    'instance_selection_criteria' => $handler->type() === 'entity' && $handler->getInstanceSelectionMode() !== 'none'
                        ? $handler->getInstanceSelectionCriteria()
                        : null, 
                    'confirmation' => $handler->getConfirmationText() ?: null,
                    'modal_title' => $handler->getFormModalTitle() ?: null,
                    'modal_confirm_label' => $handler->getFormModalButtonLabel() ?: null,
                    'has_form' => count($handler->form()) > 0,
                    'authorization' => $instanceId
                        ? $handler->authorizeFor($instanceId)
                        : $handler->getGlobalAuthorization(),
                ];
            });

        // force JSON arrays when groupIndex starts at 1 (https://github.com/code16/sharp-dev/issues/33)
        if ($config['commands'] ?? null) {
            $config['commands'] = collect($config['commands'])
                ->map(fn ($group) => collect($group)->values())
                ->toArray();
        }
    }
}
