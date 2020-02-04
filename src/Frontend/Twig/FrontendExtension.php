<?php

namespace Studio24\Frontend\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig custom functions and filters
 *
 * To use add this to your services.yaml:

    # Register Frontend Twig helpers
    Studio24\Frontend\Twig\FrontendExtension:
      tags: ['twig.extension']

 * @package Studio24\Frontend\Twig
 */
class FrontendExtension extends AbstractExtension
{

    public function getFunctions()
    {
        return array(
            new TwigFunction('slugify', [$this, 'slugify']),
            new TwigFunction('fix_url', [$this, 'fixUrl']),
        );
    }

    public function getFilters()
    {
        return [
            new TwigFunction('excerpt', [$this, 'excerpt']),
            new TwigFilter('build_version', [$this, 'buildVersion']),
        ];
    }


    /**
     * Generate a URL safe slug from a string
     *
     * Usage:
     * {{ slugify('My name is Earl') }}
     *
     * Returns:
     * my-name-is-earl
     *
     * @param $string
     * @return string
     */
    public function slugify($string): string
    {
        // Filter
        $string = mb_strtolower($string, 'UTF-8');
        $string = strip_tags($string);
        $string = preg_replace('/\s/', '-', $string);
        $string = preg_replace('/[-]+/', '-', $string);

        // Sanitise
        $string = filter_var($string, FILTER_SANITIZE_URL);

        // Replace anything that isn't a unicode letter, number or dash -
        $string = preg_replace('/[^\p{L}\p{N}-]+/', '', $string);

        return $string;
    }

    /**
     * Return URL as a HTTP/S compliant URL for use in hyperlinks
     *
     * If a host is detected the scheme is auto-added if it does not exist (defaults to http)
     *
     * @todo 0.7 Convert this into a Twig filter since it transforms content, it does not generate new content
     * @param string $url URL to fix
     * @param string $scheme The default scheme to use (defaults to http)
     * @return string
     */
    public function fixUrl(string $url, $scheme = 'http'): string
    {
        $parts = parse_url($url);
        $url = '';

        // Default to $scheme if scheme not set
        if (isset($parts['host']) && !isset($parts['scheme'])) {
            $parts['scheme'] = $scheme;
        }

        // A URL without a scheme looks like a relative URL and not a host (e.g. domain.com/path)
        if (!isset($parts['host']) && isset($parts['path'])) {
            $possibleHost = explode('/', $parts['path']);
            $possibleHost = $possibleHost[0];

            if (filter_var($possibleHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false) {
                $parts['scheme'] = $scheme;
                $parts['host'] = $possibleHost;
                $parts['path'] = str_replace($possibleHost, '', $parts['path']);
            }
        }

        if (isset($parts['host'])) {
            $url .= $parts['scheme'] . '://';
            if (isset($parts['user']) || isset($parts['pass'])) {
                $url .= $parts['user'] . ':' . $parts['pass'] . '@';
            }
            $url .= $parts['host'];
            if (isset($parts['port'])) {
                $url .= ':' . $parts['port'];
            }
        }
        if (isset($parts['path'])) {
            $url .= $parts['path'];
        }
        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    /**
     * Cut a string to a set length, but cut on the nearest word (so words are not split)
     *
     * Usage:
     * {{ 'Mary had a little lamb, Its fleece was white as snow' | excerpt(30) }}
     *
     * Returns:
     * Mary had a little lamb, Its…
     *
     * @param string $string
     * @param int $length
     * @param string $more If string is cut, display horizontal ellipsis (or different passed string)
     * @return string
     */
    public function excerpt(string $string, int $length = 50, string $more = '…'): string
    {
        if ($length >= strlen($string)) {
            return $string;
        }
        $lines = explode("\n", wordwrap($string, $length));
        return (string) $lines[0] . $more;
    }

    /**
     * Add build version to src file in HTML
     *
     * Usage:
     * {{ '/assets/styles.css' | build_version }}
     *
     * Returns:
     * /assets/styles.css?v=8b7973c7
     *
     * @param string $src
     * @return string
     */
    public function buildVersion(string $src): string
    {
        // Choose a fast, short hashing algorithm
        static $algorithm;
        if (empty($algorithm)) {
            if (in_array('adler32', hash_algos())) {
                $algorithm = 'adler32';
            } else {
                $algorithm = 'crc32';
            }
        }

        // If file src path is not relative, try to find it relative to website root
        $hash = '';
        if (file_exists($src)) {
            $hash = hash($algorithm, file_get_contents($src));
        } else {
            if (isset($_SERVER['DOCUMENT_ROOT'])) {
                $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($src, '/');
                if (file_exists($path)) {
                    $hash = hash($algorithm, file_get_contents($path));
                }
            }
        }

        // Cannot generate hash
        if (empty($hash)) {
            return $src;
        }

        return $src . '?v=' . $hash;
    }
}
