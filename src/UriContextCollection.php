<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\RoutingAuto;

use Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface;

class UriContextCollection
{
    /**
     * @var object
     */
    protected $subject;
    protected $uriContexts = [];

    /**
     * @param object $subject Subject for URL generation
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Set the subject for URL generation.
     *
     * @param object $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Return the "subject" of this URL context, i.e. the object
     * for which an auto route is required.
     *
     * @return object
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Create a URL context.
     *
     * @param string $locale Locale for given URL
     *
     * @return UriContext
     */
    public function createUriContext(
        $uriSchema,
        array $defaults,
        array $tokenProviderConfigs,
        array $conflictResolverConfigs,
        $locale
    ) {
        $uriContext = new UriContext(
            $this->getSubject(),
            $uriSchema,
            $defaults,
            $tokenProviderConfigs,
            $conflictResolverConfigs,
            $locale
        );

        return $uriContext;
    }

    /**
     * Push a URL context onto the stack.
     *
     * @param UriContext $uriContext
     */
    public function addUriContext(UriContext $uriContext)
    {
        $this->uriContexts[] = $uriContext;
    }

    public function getUriContexts()
    {
        return $this->uriContexts;
    }

    /**
     * Check if any of the UriContexts in the stack contain
     * the given auto route.
     *
     * @param AutoRouteInterface $autoRoute
     *
     * @return bool
     */
    public function containsAutoRoute(AutoRouteInterface $autoRoute)
    {
        foreach ($this->uriContexts as $uriContext) {
            if ($autoRoute === $uriContext->getAutoRoute()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an auto route by its tag (e.g. the locale).
     *
     * @param string $locale
     *
     * @return AutoRouteInterface|null
     */
    public function getAutoRouteByLocale($locale)
    {
        foreach ($this->uriContexts as $uriContext) {
            $autoRoute = $uriContext->getAutoRoute();
            if ($locale === $autoRoute->getLocale()) {
                return $autoRoute;
            }
        }

        return null;
    }
}
