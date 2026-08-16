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

namespace ILIAS\Plugin\LongEssayAssessment\UI\Container;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer;
use ILIAS\UI\Component\Component;
use LogicException;

class ContainerRenderer extends AbstractComponentRenderer
{
    public function render(Component $component, Renderer $default_renderer): string
    {
        return match (true) {
            $component instanceof Bindable => $this->renderBindable($component, $default_renderer),
            default => throw new LogicException("Cannot render '" . get_class($component) . "'"),
        };
    }

    public function renderBindable(Bindable $component, Renderer $default_renderer): string
    {
        $cid = $this->bindJavaScript($component);

        $tpl = $this->getTemplate("tpl.bindable.html", true, true);
        $tpl->setVariable('ID', $cid);
        $tpl->setVariable('COMPONENTS', $default_renderer->render($component->getComponents()));
        if ($component->getHidden()) {
            $tpl->setVariable('HIDDEN', 'visibility: hidden;');
        }
        return $tpl->get();
    }

    protected function getTemplatePath(string $name): string
    {
        return 'Container/' . $name;
    }

}
