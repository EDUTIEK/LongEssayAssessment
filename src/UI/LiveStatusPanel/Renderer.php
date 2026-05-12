<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\UI\LiveStatusPanel;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\UI\Component\Component;
use LogicException;

class Renderer extends AbstractComponentRenderer
{
    protected function getComponentInterfaceName(): array
    {
        return [Panel::class];
    }

    public function render(Component $component, UIRenderer $default_renderer): string
    {
        return match (true) {
            $component instanceof Panel => $this->renderPanel($component, $default_renderer),
            default => throw new LogicException("Cannot render '" . get_class($component) . "'"),
        };
    }

    public function renderPanel(Panel $component, UIRenderer $default_renderer): string
    {
        function unpack(array $arr): array
        {
            return array_merge(...$arr);
        }

        function intersperse(array $arr, $separator): array
        {
            return array_reduce(
                $arr,
                function ($acc, $item) use ($separator) {
                    return $acc === [] ? [$item] : array_merge($acc, [$separator, $item]);
                },
                []
            );
        }

        $properties = array_map($this->property(...), $component->getProperties());
        $seperator = ["div", "|", false];

        $property_listing = $this->getUIFactory()->listing()->property()
          ->withItems(unpack(intersperse($properties, [$seperator])));

        $js = $this->javascript($component->getLiveDataUrl(), $component->getInterval());

        #$this->bindJavaScript($js);

        $panel = $this->getUIFactory()->panel()->standard($component->getTitle(), [$property_listing]);

        return $default_renderer->render([$panel, $js]);
    }

    private function property(Property $prop)
    {
        $glyph = $this->getUIFactory()->symbol()->glyph();
        $ret = [[$prop->getTitle(), $this->liveNumber($prop->getId(), $prop->getInitialValue()), true]];

        if ($prop->getFilterUrl() !== null) {
            $ret[] = ["link", $glyph->filter($prop->getFilterUrl()), false];
        }

        return $ret;

    }

    private function liveNumber(string $id, int $value): Component
    {
        return $this->getUIFactory()->legacy()->content("<span id='{$id}'>{$value}</span>");
    }

    private function javascript(string $url, int $interval = 5000): Component
    {
        $js_code = "$(function() {
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    fetch('{$url}', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        for (const [key, value] of Object.entries(data)) {
                            const element = document.getElementById(key);
                            if (element) {
                                element.innerHTML = value;
                            }
                        }
                    })
                    .catch(error => console.debug(error));
                }
            }, {$interval})
        });";
        return $this->getUIFactory()->legacy()->content("")->withOnLoadCode(fn ($id) => $js_code);
    }



    protected function getTemplatePath(string $name): string
    {
        return 'LiveStatusPanel/' . $name;
    }

}
