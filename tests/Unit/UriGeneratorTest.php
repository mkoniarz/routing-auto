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

use Prophecy\Argument;
use Symfony\Cmf\Component\RoutingAuto\UriGenerator;

class UriGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $serviceRegistry;
    protected $tokenProviders = [];
    protected $uriContext;

    public function setUp()
    {
        $this->serviceRegistry = $this->prophesize('Symfony\Cmf\Component\RoutingAuto\ServiceRegistry');
        $this->tokenProvider = $this->prophesize('Symfony\Cmf\Component\RoutingAuto\TokenProviderInterface');
        $this->uriContext = $this->prophesize('Symfony\Cmf\Component\RoutingAuto\UriContext');

        $this->uriGenerator = new UriGenerator(
            $this->serviceRegistry->reveal()
        );
    }

    public function provideGenerateUri()
    {
        return [
            // tokens should be substituted with values from the token providers
            [
                '/this/is/{token_the_first}/a/uri',
                '/this/is/foobar_value/a/uri',
                [
                    'token_the_first' => [
                        'name' => 'foobar_provider',
                        'value' => 'foobar_value',
                        'options' => [],
                    ],
                ],
            ],
            // tokens should be substituted with values from the token providers
            [
                '/{this}/{is}/{token_the_first}/a/uri',
                '/that/was/foobar_value/a/uri',
                [
                    'token_the_first' => [
                        'name' => 'foobar_provider',
                        'value' => 'foobar_value',
                        'options' => [],
                    ],
                    'this' => [
                        'name' => 'barfoo_provider',
                        'value' => 'that',
                        'options' => [],
                    ],
                    'is' => [
                        'name' => 'dobar_provider',
                        'value' => 'was',
                        'options' => [],
                    ],
                ],
            ],
            // an exception should be thrown if the token provider is not known
            [
                '/this/is/{unknown_token}/life',
                null,
                [],
                ['InvalidArgumentException', 'Unknown token "unknown_token"'],
            ],
            // an exception should be thrown if the generated URI is not absolute
            [
                'this/is/not/absolute',
                null,
                [],
                ['InvalidArgumentException', 'Generated non-absolute URI'],
            ],
            // no tokens need to be specified
            [
                '/this/is/has/no/tokens',
                '/this/is/has/no/tokens',
                [],
            ],
            // nothing should happen if allow_empty is true and the value is not empty
            [
                '/{parent}/title',
                '/foobar_value/title',
                [
                    'parent' => [
                        'name' => 'foobar_provider',
                        'value' => 'foobar_value',
                        'options' => [
                            'allow_empty' => true,
                        ],
                    ],
                ],
            ],
            // the empty token should be collapsed when allow_empty is true
            [
                '/{parent}/title',
                '/title',
                [
                    'parent' => [
                        'name' => 'foobar_provider',
                        'value' => '',
                        'options' => [
                            'allow_empty' => true,
                        ],
                    ],
                ],
            ],
            // if the token value is a single "/" then it should be treated as an empty value and
            // any trailing slash should be collapsed.
            [
                '{parent}/title',
                '/title',
                [
                    'parent' => [
                        'name' => 'foobar_provider',
                        'value' => '/',
                        'options' => [
                            'allow_empty' => true,
                        ],
                    ],
                ],
            ],
            // if the last segment is empty and allow empty is true, then remove the leading slash
            [
                '/{locale}/{parent}',
                '/de',
                [
                    'locale' => [
                        'name' => 'foobar_provider',
                        'value' => 'de',
                        'options' => [
                            'allow_empty' => true,
                        ],
                    ],
                    'parent' => [
                        'name' => 'barbar_provider',
                        'value' => '',
                        'options' => [
                            'allow_empty' => true,
                        ],
                    ],
                ],
            ],
            // if the last segment is empty and has a trailing slash then the trailing slash should be
            // preserved
            [
                '/{locale}/{parent}/',
                '/de/',
                [
                    'locale' => [
                        'name' => 'foobar_provider',
                        'value' => 'de',
                        'options' => [
                            'allow_empty' => true,
                        ],
                    ],
                    'parent' => [
                        'name' => 'barbar_provider',
                        'value' => '',
                        'options' => [
                            'allow_empty' => true,
                        ],
                    ],
                ],
            ],
            // an exception should be thrown if a token is empty and allow_empty is false
            [
                '/{parent}/title',
                '/title',
                [
                    'parent' => [
                        'name' => 'foobar_provider',
                        'value' => '',
                        'options' => [
                            'allow_empty' => false,
                        ],
                    ],
                ],
                [
                    'InvalidArgumentException', 'Token provider "foobar_provider" returned an empty value',
                ],
            ],
            // it should not throw a warning if the allow_empty option is absent and the value is empty.
            [
                '/{parent}/title',
                '/title',
                [
                    'parent' => [
                        'name' => 'foobar_provider',
                        'value' => '',
                        'options' => [],
                    ],
                ],
                [
                    'InvalidArgumentException', 'Token provider "foobar_provider" returned an empty value',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideGenerateUri
     */
    public function testGenerateUri($uriSchema, $expectedUri, $tokenProviderConfigs, $expectedException = null)
    {
        if ($expectedException) {
            list($exceptionType, $exceptionMessage) = $expectedException;
            $this->setExpectedException($exceptionType, $exceptionMessage);
        }

        $document = new \stdClass();
        $this->uriContext->getSubject()->willReturn($document);
        $this->uriContext->getUri()->willReturn($uriSchema);
        $this->uriContext->getTokenProviderConfigs()
            ->willReturn($tokenProviderConfigs);
        $this->uriContext->getUriSchema()
            ->willReturn($uriSchema);

        foreach ($tokenProviderConfigs as $tokenProviderConfig) {
            // set the defaults for the predictions
            $tokenProviderConfig['options'] = array_merge([
                'allow_empty' => false,
            ], $tokenProviderConfig['options']);

            $providerName = $tokenProviderConfig['name'];

            $this->tokenProviders[$providerName] = $this->prophesize(
                'Symfony\Cmf\Component\RoutingAuto\TokenProviderInterface'
            );

            $this->serviceRegistry->getTokenProvider($tokenProviderConfig['name'])
                ->willReturn($this->tokenProviders[$providerName]);

            $this->tokenProviders[$providerName]->provideValue($this->uriContext, $tokenProviderConfig['options'])
                ->willReturn($tokenProviderConfig['value']);
            $this->tokenProviders[$providerName]->configureOptions(Argument::type('Symfony\Component\OptionsResolver\OptionsResolver'))->shouldBeCalled();
        }

        $res = $this->uriGenerator->generateUri($this->uriContext->reveal());

        $this->assertEquals($expectedUri, $res);
    }
}
