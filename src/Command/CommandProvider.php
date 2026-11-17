<?php

namespace Klxm\Quickbar\Command;

use Klxm\Quickbar\AsProvider;
use Klxm\Quickbar\ExtensionPoint\QuickbarCommands;
use Klxm\Quickbar\Item;
use Klxm\Quickbar\Media\MediaFinder;
use Klxm\Quickbar\Provider;
use Klxm\Quickbar\Query;
use Klxm\Quickbar\YForm\YFormTables;
use Override;
use Redaxo\Core\Content\Category;
use Redaxo\Core\Content\StructurePermission;
use Redaxo\Core\ExtensionPoint\Extension;
use Redaxo\Core\Filesystem\Url;
use Redaxo\Core\Http\Request;
use Redaxo\Core\Language\Language;
use Redaxo\Core\Security\CsrfToken;
use Redaxo\Core\Translation\I18n;

/**
 * Befehle: Neues anlegen und Aufgaben für Admins – mit „>“ alle, sonst nur passende in der normalen Suche.
 * Weitere Befehle per {@see QuickbarCommands}.
 */
#[AsProvider('commands', priority: 95, prefix: '>')]
final class CommandProvider implements Provider
{
    #[Override]
    public function label(?Query $query = null): string
    {
        return I18n::msg('quickbar_group_commands');
    }

    #[Override]
    public function search(Query $query): iterable
    {
        if ($query->isEmpty() && !$query->scoped) {
            return [];
        }
        $commands = Extension::dispatch(new QuickbarCommands($this->commands($query), $query));

        $list = [];
        foreach ($commands as $item) {
            if (!$item instanceof Item) {
                continue;
            }
            $score = $query->score($item->title, (string) $item->meta);
            if ($score > 0) {
                $item->score = $score;
                $list[] = $item;
            }
        }

        return $list;
    }

    /** @return list<Item> */
    private function commands(Query $query): array
    {
        $user = $query->user;
        $msg = I18n::msg(...);

        // die Seite, auf der die Palette geöffnet wurde, als Favorit merken (erledigt das Skript)
        $list = [new Item($msg('quickbar_cmd_favorite'), '#qn-favorite-page', $msg('quickbar_group_favorites'), 'rex-icon fa-solid fa-star')];

        // Struktur: in der Kategorie, in der man gerade ist (sonst Hauptebene)
        $perm = $user->getComplexPerm('structure');
        $languageId = Request::get('qn_language', 'int', 0) ?: Language::getStartId();
        $categoryId = Request::get('qn_category', 'int', 0);
        $category = $categoryId > 0 ? Category::get($categoryId, $languageId) : null;
        $categoryId = null !== $category ? $category->id : 0;
        $where = $category ? '»' . $category->name . '«' : $msg('quickbar_root');
        $mayHere = $user->admin || ($perm instanceof StructurePermission && $perm->hasCategoryPerm($categoryId ?: null));
        if ($mayHere && $user->hasPerm('addArticle[]')) {
            $list[] = new Item($msg('quickbar_cmd_article', $where), Url::backendPage('structure', ['category_id' => $categoryId, 'language' => $languageId, 'function' => 'add_art']), $msg('quickbar_cmd_new'), 'rex-icon rex-icon-add-article');
        }
        if ($mayHere && $user->hasPerm('addCategory[]')) {
            $list[] = new Item($msg('quickbar_cmd_category', $where), Url::backendPage('structure', ['category_id' => $categoryId, 'language' => $languageId, 'function' => 'add_cat']), $msg('quickbar_cmd_new'), 'rex-icon rex-icon-add-category');
        }

        // Medienpool
        if (MediaFinder::allowed($user)) {
            $list[] = new Item($msg('quickbar_cmd_upload'), Url::backendPage('mediapool/upload'), $msg('quickbar_group_media'), 'rex-icon rex-icon-add-media');
        }

        // YForm: neuer Datensatz je Tabelle mit Bearbeitungsrecht, für Admins der Designer
        foreach (YFormTables::visible($user) as $table) {
            if (YFormTables::canEdit($table, $user)) {
                $list[] = new Item($msg('quickbar_cmd_record', $table->label()), YFormTables::url($table, ['func' => 'add']), $msg('quickbar_group_yform'), YFormTables::icon($table));
            }
            if ($user->admin) {
                $list[] = new Item($msg('quickbar_cmd_designer', $table->label()), Url::backendPage('yform/designer', ['table' => $table->name]), 'YForm', 'rex-icon fa-th-large');
            }
        }

        // Admins
        if ($user->admin) {
            $list[] = new Item($msg('quickbar_cmd_user'), Url::backendPage('users/users', ['func' => 'add']), $msg('quickbar_group_users'), 'rex-icon rex-icon-add-user');
            $list[] = new Item($msg('quickbar_cmd_cache'), Url::backendPage('system/settings', ['func' => 'generate'] + CsrfToken::factory('system')->getUrlParams()), $msg('quickbar_cmd_cache_meta'), 'rex-icon rex-icon-delete');
        }

        return $list;
    }
}
