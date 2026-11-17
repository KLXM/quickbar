<?php

namespace Klxm\Quickbar;

use Override;
use Redaxo\Core\ApiFunction\ApiFunction;
use Redaxo\Core\ApiFunction\AsApiFunction;
use Redaxo\Core\ApiFunction\Result;
use Redaxo\Core\Core;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Http\Response;

use function mb_substr;

/**
 * Live-Suche der Befehlspalette (nur lesend): ?rex-api-call=quickbar&q=….
 *
 * Die Quellen prüfen die Rechte des angemeldeten Benutzers selbst ({@see Provider}).
 */
#[AsApiFunction('quickbar')]
final class SearchApi extends ApiFunction
{
    #[Override]
    public function execute(): Result
    {
        $user = Core::requireUser();
        $result = Quickbar::search(mb_substr(Request::get('q', 'string', ''), 0, 200), $user);

        Response::cleanOutputBuffers();
        Response::setHeader('Cache-Control', 'no-store');
        Response::sendJson($result);
        exit;
    }
}
