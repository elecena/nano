<?php

use Nano\Template;

/**
 * Skin renderer
 *
 * Handles adding JS/CSS files and packages, setting page title, meta / link[rel] tags, adding global JS variables
 *
 * @property StaticAssets $staticAssets
 */
abstract class Skin
{
    const SUBTITLE_SEPARATOR = ' &raquo; ';

    protected $app;
    protected $skinName;
    protected $skinDirectory;

    // static assets handler
    protected $staticAssets;

    // template used to render the skin
    protected $template;

    // page title
    protected $pageTitle = '';

    // page title parts
    protected $subTitles = [];

    // JS/CSS files and packages requested to be fetched
    protected $assets = [
        'css' => [],
        'js' => [],
    ];

    protected $packages = [];

    // meta and link tags in head section
    protected $meta = [];
    protected $link = [];

    // global JS variables
    protected $jsVariables = [];

    // skin will not wrap the content
    protected $bodyOnly = false;

    /**
     * Return an instance of a given skin
     *
     * @param NanoApp $app
     * @param string $skinName
     * @return self
     * @throws Exception
     */
    public static function factory(NanoApp $app, string $skinName): Skin
    {
        $className = sprintf('Skin%s', ucfirst($skinName));

        if (!class_exists($className)) {
            throw new \Exception("Skin {$className} not found");
        }

        return new $className($app, $skinName);
    }

    /**
     * Use Skin::factory
     *
     * @param NanoApp $app
     * @param string $skinName
     */
    public function __construct(NanoApp $app, string $skinName)
    {
        $this->app = $app;
        $this->skinName = $skinName;

        $this->app->getDebug()->log("Skin: using '{$this->skinName}'");

        $this->skinDirectory = $app->getDirectory() . '/skins/' . strtolower($skinName);

        // setup objects
        $this->staticAssets = new StaticAssets($app);
        $this->template = new Template($this->skinDirectory . '/templates');
    }

    /**
     * Get name of the skin
     */
    public function getName()
    {
        return $this->skinName;
    }

    /**
     * The skin will not be used to wrap content
     */
    public function setBodyOnly($val = true)
    {
        $this->bodyOnly = $val;
    }

    /**
     * Sets page title (clears subtitles stack)
     */
    public function setPageTitle($title)
    {
        $this->pageTitle = $title;
        $this->subTitles = [];
    }

    /**
     * Add page subtitle to the stack
     *
     * Example:
     *  $skin->addPageSubtitle('search');
     *  $skin->addPageSubtitle('foo');
     */
    public function addPageSubtitle($subtitle)
    {
        $this->subTitles[] = htmlspecialchars($subtitle);
    }

    /**
     * Get page title
     */
    public function getPageTitle()
    {
        return !empty($this->subTitles) ? implode(self::SUBTITLE_SEPARATOR, array_reverse($this->subTitles)) : $this->pageTitle;
    }

    /**
     * Add <meta> tag entry
     */
    public function addMeta(string $name, ?string $value): void
    {
        $this->meta[] = [
            'name' => $name,
            'value' => $value,
        ];
    }

    /**
     * Add <meta> tag entry
     *
     * @param string $policy
     * @see https://support.google.com/webmasters/answer/93710?hl=pl
     */
    public function setRobotsPolicy($policy)
    {
        $this->meta[] = [
            'name' => 'robots',
            'content' => $policy,
        ];
    }

    /**
     * Add <meta> OpenGraph entry
     *
     * @see http://ogp.me/
     */
    public function addOpenGraph($name, $value)
    {
        $this->meta[] = [
            'property' => "og:{$name}",
            'content' => $value,
        ];
    }

    /**
     * Add <link> tag entry
     *
     * @param string $rel "rel" attribute value
     * @param string $value "value" attribute value
     * @param array $attrs optional additional attributes to set
     */
    public function addLink($rel, $value, array $attrs = [])
    {
        $this->link[] = array_merge([
            'rel' => $rel,
            'value' => $value,
        ], $attrs);
    }

    /**
     * Add RSS feed
     */
    public function addFeed($href, $title = false)
    {
        // <link rel="alternate" href="/feed/rss" type="application/rss+xml" title="RSS feed" />
        $this->link[] = [
            'rel' => 'alternate',
            'href' => $href,
            'type' => 'application/rss+xml',
            'title' => $title,
        ];
    }

    /**
     * Set the canonical URL of the page
     */
    public function setCanonicalUrl($url)
    {
        $this->addLink('canonical', $url);
    }

    /**
     * Add a given global JS variable
     */
    public function addJsVariable($name, $value)
    {
        $this->jsVariables[$name] = $value;
    }

    /**
     * Add a given asset
     */
    public function addAsset($name, $type = false)
    {
        if ($type === false) {
            $dot = strrpos($name, '.');
            $ext = ($dot !== false) ? substr($name, $dot +1) : false;

            switch ($ext) {
                case 'css':
                    $type = 'css';
                    break;

                case 'js':
                    $type = 'js';
                    break;
            }
        }

        if (isset($this->assets[$type])) {
            $this->assets[$type][] = $name;
        }
    }

    /**
     * Add a given assets package (and its dependencies)
     */
    public function addPackage($package)
    {
        $this->addPackages([$package]);
    }

    /**
     * Add a given assets package (and its dependencies)
     */
    public function addPackages(array $packages)
    {
        $this->packages = array_merge($this->packages, $packages);
    }

    /**
     * Return URL to a given local file
     */
    public function getUrlForAsset($asset)
    {
        return $this->staticAssets->getUrlForAsset($asset);
    }

    /**
     * Get skin data - variables available in skin template
     */
    protected function getSkinData()
    {
        return [
            // object instances
            'app' => $this->app,
            'router' => $this->app->getRouter(),
            'skin' => $this,
            'staticAssets' => $this->staticAssets,

            // additional data
            'skinPath' => $this->staticAssets->getUrlForFile($this->skinDirectory),
            'pageTitle' => $this->getPageTitle(),
        ];
    }

    /**
     * Returns list of URLs of a given type (CSS/JS) to be rendered by the skin
     */
    protected function getAssetsUrls($type)
    {
        $urls = [];

        // packages
        $packagesUrls = $this->staticAssets->getUrlsForPackages($this->packages, $type);

        if ($packagesUrls !== false) {
            $urls = array_merge($urls, $packagesUrls);
        }
        
        // single assets
        foreach ($this->assets[$type] as $asset) {
            $urls[] = $this->staticAssets->getUrlForAsset($asset);
        }

        return $urls;
    }

    /**
     * Renders set of <meta> elements to be used in page's head section
     */
    public function renderHead(string $sep = "\n"): string
    {
        // render <meta> elements
        $elements = [];

        foreach ($this->meta as $item) {
            $node = '<meta';

            foreach ($item as $name => $value) {
                $value = htmlspecialchars($value ?? '');
                $node .= " {$name}=\"{$value}\"";
            }

            $node .= '>';

            $elements[] = $node;
        }

        // render <link> elements
        foreach ($this->link as $item) {
            $node = '<link';

            foreach ($item as $name => $value) {
                $value = htmlspecialchars($value ?? '');
                $node .= " {$name}=\"{$value}\"";
            }

            $node .= '>';

            $elements[] = $node;
        }

        $this->app->getEvents()->fire('SkinBeforeJSVariables', [$this]);

        // render global JS variables (wrapped in nano object)
        if (!empty($this->jsVariables)) {
            $elements[] = '<script>nano = ' . json_encode($this->jsVariables) . '</script>';
        }

        $this->app->getEvents()->fire('SkinRenderHead', [&$elements]);

        return rtrim($sep . implode($sep, $elements));
    }

    /**
     * Renders set of <link> elements to be used to include CSS files
     * requested via $skin->addPackage and $skin->addAsset
     */
    public function renderCssInclude($sep = "\n")
    {
        $urls = $this->getAssetsUrls('css');

        // render <link> elements
        $elements = [];

        foreach ($urls as $url) {
            if ($url !== false) {
                $elements[] = '<link href="' . $url . '" rel="stylesheet">';
            }
        }

        $this->app->getEvents()->fire('SkinRenderCssInclude', [&$elements]);

        return rtrim($sep . implode($sep, $elements));
    }

    /**
     * Renders set of <link> elements to be used to include CSS files
     * requested via $skin->addPackage and $skin->addAsset
     */
    public function renderJsInclude($sep = "\n")
    {
        $urls = $this->getAssetsUrls('js');

        // render <link> elements
        $elements = [];

        foreach ($urls as $url) {
            if ($url !== false) {
                $elements[] = '<script src="' . $url . '"></script>';
            }
        }

        $this->app->getEvents()->fire('SkinRenderJsInclude', [&$elements]);

        return implode($sep, $elements);
    }

    /**
     * Returns HTML of rendered page
     */
    public function render($content)
    {
        // don't wrap the content
        if ($this->bodyOnly) {
            $this->app->getResponse()->setContent($content);
            return;
        }

        // set skin template's data
        $this->template->set($this->getSkinData());
        $this->template->set('content', $content);

        // render the head of the page
        echo $this->template->render('head');

        // flush the output
        #$this->app->getResponse()->flush();

        // render the rest of the page
        echo $this->template->render('main');
    }

    /**
     * @return StaticAssets
     */
    public function getStaticAssets()
    {
        return $this->staticAssets;
    }
}
