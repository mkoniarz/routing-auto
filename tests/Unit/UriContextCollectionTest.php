<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2017 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\RoutingAuto\Tests\Unit;

use Symfony\Cmf\Component\RoutingAuto\UriContextCollection;

class UriContextCollectionTest extends \PHPUnit_Framework_TestCase
{
    protected $uriContextCollection;

    public function setUp()
    {
        $this->subject = new \stdClass();

        for ($i = 1; $i <= 3; ++$i) {
            $this->{'autoRoute'.$i} = $this->prophesize('Symfony\Cmf\Component\RoutingAuto\Model\AutoRouteInterface');
            $this->{'uriContext'.$i} = $this->prophesize('Symfony\Cmf\Component\RoutingAuto\UriContext');
            $this->{'uriContext'.$i}->getAutoRoute()->willReturn($this->{'autoRoute'.$i});
        }

        $this->uriContextCollection = new UriContextCollection($this->subject);
    }

    public function testGetSubject()
    {
        $this->assertEquals($this->subject, $this->uriContextCollection->getSubject());
    }

    public function testCreateUriContext()
    {
        $res = $this->uriContextCollection->createUriContext(
            '/foo/{bar}/baz',
            [],
            [],
            [],
            'fr'
        );
        $this->assertInstanceOf('Symfony\Cmf\Component\RoutingAuto\UriContext', $res);
        $this->assertEquals('fr', $res->getLocale());
    }

    public function provideContainsAutoRoute()
    {
        return [
            [
                ['uriContext1', 'uriContext2', 'uriContext3'],
                'autoRoute1',
                true,
            ],
            [
                ['uriContext2', 'uriContext3'],
                'autoRoute1',
                false,
            ],
        ];
    }

    /**
     * @dataProvider provideContainsAutoRoute
     */
    public function testContainsAutoRoute($uriContextNames, $targetName, $expected)
    {
        foreach ($uriContextNames as $uriContextName) {
            $this->uriContextCollection->addUriContext($this->$uriContextName->reveal());
        }

        $res = $this->uriContextCollection->containsAutoRoute($this->$targetName->reveal());

        $this->assertEquals($expected, $res);
    }
}
