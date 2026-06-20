<?php
declare(strict_types=1);

namespace Koonect\Controllers;

use Koonect\Core\{Request, Response, View};
use Koonect\Models\{Tag as TagModel, Article};
use Koonect\Helpers\{Paginator, Seo};

class TagController
{
    public function show(Request $request): void
    {
        $tagSlug = $request->param('slug');
        $tagModel = new TagModel();
        $tag = $tagModel->findBySlug($tagSlug);
        if (!$tag) Response::notFound();

        $page      = max(1, (int)$request->get('page', 1));
        $artModel  = new Article();
        $total     = $artModel->countPublished(null, (int)$tag['id']);
        $paginator = new Paginator($total, ARTICLES_PER_PAGE, $page);
        $articles  = $artModel->getPublished(ARTICLES_PER_PAGE, $paginator->offset, null, (int)$tag['id']);

        $seoTitle = $tag['name'] . ' — Articles et actualités — ' . APP_NAME;
        $seoDescription = 'Retrouvez tous les articles sur le thème « ' . $tag['name']
            . ' » sur ' . APP_NAME . '. ' . $total . ' article'
            . ($total > 1 ? 's' : '') . ' disponible' . ($total > 1 ? 's' : '') . '.';

        // BreadcrumbList JSON-LD
        $breadcrumbJson = Seo::schemaBreadcrumb([
            ['name' => 'Accueil', 'url' => APP_URL],
            ['name' => 'Tag : ' . $tag['name']],
        ]);

        // ItemList JSON-LD
        $itemListJson = !empty($articles)
            ? Seo::schemaItemList($articles, 'Articles — ' . $tag['name'])
            : '';

        View::render('tag/show', [
            'tag'        => $tag,
            'tagSlug'    => $tagSlug,
            'articles'   => $articles,
            'paginator'  => $paginator,
            'total'      => $total,
            'schemaJson' => $breadcrumbJson . "\n" . $itemListJson,
            'seo'        => [
                'seo_title'       => $seoTitle,
                'seo_description' => $seoDescription,
                'paginator'       => $paginator,
            ],
        ]);
    }
}
