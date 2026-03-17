<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class ShareMarkdownButtonPlugin extends Plugin
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
        ];
    }

    /**
     * Initialize plugin — skip admin panel entirely.
     */
    public function onPluginsInitialized(): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onTwigTemplatePaths'    => ['onTwigTemplatePaths', 0],
            'onPageContentProcessed' => ['onPageContentProcessed', 0],
            'onTwigSiteVariables'    => ['onTwigSiteVariables', 0],
        ]);
    }

    /**
     * Add plugin templates directory to Twig paths.
     */
    public function onTwigTemplatePaths(): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Inject assets (CSS + JS) when needed.
     */
    public function onTwigSiteVariables(): void
    {
        $page = $this->grav['page'];

        if (!$this->shouldShowButton($page)) {
            return;
        }

        $assets = $this->grav['assets'];
        $assets->addCss('plugin://share-markdown-button/assets/css/share-markdown-button.css');
        $assets->addJs('plugin://share-markdown-button/assets/js/share-markdown-button.js', ['defer' => true]);
    }

    /**
     * Append / prepend the button HTML to the rendered page content.
     */
    public function onPageContentProcessed(Event $event): void
    {
        $page = $event['page'];

        if (!$this->shouldShowButton($page)) {
            return;
        }

        $config   = $this->mergeConfig($page);
        $markdown = $this->buildMarkdown($page, $config);

        $twig       = $this->grav['twig'];
        $buttonHtml = $twig->processTemplate(
            'partials/share-markdown-button.html.twig',
            [
                'smb_markdown' => $markdown,
                'smb_config'   => $config,
            ]
        );

        $position = $config->get('button_position', 'bottom');
        $content  = $event['content'];

        switch ($position) {
            case 'top':
                $event['content'] = $buttonHtml . $content;
                break;
            case 'both':
                $event['content'] = $buttonHtml . $content . $buttonHtml;
                break;
            default: // bottom
                $event['content'] = $content . $buttonHtml;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Decide whether the button should appear on the given page.
     */
    private function shouldShowButton($page): bool
    {
        if (!$page || !$page->exists()) {
            return false;
        }

        $config    = $this->mergeConfig($page);
        $pageTypes = (array) $config->get('page_types', []);

        // Empty list means "all types".
        if (!empty($pageTypes) && !in_array($page->template(), $pageTypes, true)) {
            return false;
        }

        return true;
    }

    /**
     * Build the Markdown string that will be copied to the clipboard.
     *
     * Steps:
     *  1. Get raw Markdown from the page.
     *  2. If the page processes Twig, run the raw content through the Twig
     *     engine so tags are resolved — the result is still Markdown, not HTML.
     *  3. Optionally prepend the page title as an H1 heading.
     *  4. Optionally prepend the YAML frontmatter.
     */
    private function buildMarkdown($page, $config): string
    {
        $raw = $page->rawMarkdown();

        // Resolve Twig tags inside the Markdown body when the page uses them.
        $process = $page->process();
        if (!empty($process['twig'])) {
            $raw = $this->grav['twig']->processString($raw, ['page' => $page]);
        }

        // Prepend the page title as "# Title" so AI chats have full context.
        // The title lives in the frontmatter and is not part of rawMarkdown().
        if ($config->get('include_title', true)) {
            $title = $page->title();
            if ($title) {
                $raw = '# ' . $title . "\n\n" . $raw;
            }
        }

        // Optionally prepend the YAML frontmatter.
        if ($config->get('include_frontmatter', false)) {
            $header = $page->header();
            if ($header) {
                $frontmatter = \Symfony\Component\Yaml\Yaml::dump(
                    (array) $header,
                    4,
                    2
                );
                $raw = "---\n" . $frontmatter . "---\n\n" . $raw;
            }
        }

        return $raw;
    }
}
