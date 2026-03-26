<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

class CopyAsMarkdownButtonPlugin extends Plugin
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
            'onPageContentProcessed' => ['onPageContentProcessed', 0],
            'onTwigSiteVariables'    => ['onTwigSiteVariables', 0],
        ]);
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
        $assets->addCss('plugin://copy-as-markdown-button/assets/css/copy-as-markdown-button.css');
        $assets->addJs('plugin://copy-as-markdown-button/assets/js/copy-as-markdown-button.js', ['defer' => true]);
    }

    /**
     * Append / prepend the button HTML to the rendered page content.
     */
    public function onPageContentProcessed(Event $event): void
    {
        $page        = $event['page'];
        $currentPage = $this->grav['page'];

        // onPageContentProcessed fires for every page Grav processes
        // (collection items, modular sub-pages, etc.). Only inject on the
        // page that is actually being routed and displayed.
        if (!$page || !$currentPage || $page->route() !== $currentPage->route()) {
            return;
        }

        if (!$this->shouldShowButton($page)) {
            return;
        }

        $config     = $this->mergeConfig($page);
        $markdown   = $this->buildMarkdown($page, $config);
        $buttonHtml = $this->renderButton($markdown, $config);

        $position = $config->get('button_position', 'bottom');
        $content  = $page->getRawContent();

        switch ($position) {
            case 'top':
                $page->setRawContent($buttonHtml . $content);
                break;
            case 'both':
                $page->setRawContent($buttonHtml . $content . $buttonHtml);
                break;
            default: // bottom
                $page->setRawContent($content . $buttonHtml);
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

        // Skip modular sub-pages — they are rendered as part of a parent page.
        if ($page->modular()) {
            return false;
        }

        $config    = $this->mergeConfig($page);
        $pageTypes = (array) $config->get('page_types', []);

        // Flatten in case the admin stored nested arrays, keep only scalar values.
        $pageTypes = array_values(array_filter(array_map(
            fn($v) => is_array($v) ? (string) reset($v) : (string) $v,
            $pageTypes
        )));

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

    /**
     * Render the button HTML directly in PHP.
     *
     * Avoids calling processTemplate() during onPageContentProcessed, which
     * fires before Twig is fully initialised and causes silent rendering failures.
     * Translation keys are resolved via the Language service instead of |t.
     */
    private function renderButton(string $markdown, $config): string
    {
        $id       = 'smb-' . rand(100000, 999999);
        $lang     = $this->grav['language'];
        $showIcon = (bool) $config->get('show_icon', true);

        $buttonText = $config->get('button_text', 'PLUGIN_COPY_AS_MARKDOWN_BUTTON.BUTTON_TEXT');
        $copiedText = $config->get('copied_text', 'PLUGIN_COPY_AS_MARKDOWN_BUTTON.COPIED_TEXT');

        // Resolve translation keys; plain custom strings are returned as-is.
        $buttonText = $lang->translate([$buttonText]) ?: $buttonText;
        $copiedText = $lang->translate([$copiedText]) ?: $copiedText;

        $icon = '';
        if ($showIcon) {
            $icon = '<svg class="smb-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
                  . '<path d="M16 1H4C2.9 1 2 1.9 2 3v14h2V3h12V1zm3 4H8C6.9 5 6 5.9 6 7v14'
                  . 'c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>'
                  . '</svg>';
        }

        $eid         = htmlspecialchars($id, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $eMarkdown   = htmlspecialchars($markdown, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');
        $eButtonText = htmlspecialchars($buttonText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $eCopiedText = htmlspecialchars($copiedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<div class="smb-wrapper">'
             . '<textarea id="' . $eid . '" class="smb-source" readonly aria-hidden="true" tabindex="-1">' . $eMarkdown . '</textarea>'
             . '<button type="button" class="smb-button"'
             . ' data-smb-source="' . $eid . '"'
             . ' data-smb-copied-text="' . $eCopiedText . '"'
             . ' data-smb-original-text="' . $eButtonText . '"'
             . ' aria-label="' . $eButtonText . '">'
             . $icon
             . '<span class="smb-button-text">' . $eButtonText . '</span>'
             . '</button>'
             . '</div>';
    }
}
