<?php

namespace Klxm\Quickbar\ExtensionPoint;

use Klxm\Quickbar\Item;
use Klxm\Quickbar\Query;
use Redaxo\Core\ExtensionPoint\ExtensionPoint;

/**
 * Befehle der Palette („>“) – AddOns ergänzen eigene, nach den Rechten von `$query->user`:
 *
 *     Extension::register(QuickbarCommands::class, static function (QuickbarCommands $ep): array {
 *         $commands = $ep->subject;
 *         if ($ep->query->user->admin) {
 *             $commands[] = new Item('Sitemap neu erzeugen', Url::backendPage('seo/sitemap', ['func' => 'generate']), icon: 'rex-icon fa-sitemap');
 *         }
 *         return $commands;
 *     });
 *
 * Gefiltert und sortiert wird danach nach der Eingabe ({@see Query::score()} über Titel und zweite Zeile).
 *
 * @extends ExtensionPoint<list<Item>>
 */
final class QuickbarCommands extends ExtensionPoint
{
    /** @param list<Item> $commands */
    public function __construct(
        array $commands,
        public readonly Query $query,
    ) {
        parent::__construct(self::class, $commands);
    }
}
