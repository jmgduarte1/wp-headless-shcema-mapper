<?php

declare(strict_types=1);

namespace HeadlessAngular\Schema\Tests\Unit;

use HeadlessAngular\Schema\Domain\Schema\BlockStyle;
use HeadlessAngular\Schema\Domain\Schema\HeroAction;
use HeadlessAngular\Schema\Domain\Schema\HeroActionVariant;
use HeadlessAngular\Schema\Domain\Schema\HeroAlignment;
use HeadlessAngular\Schema\Domain\Schema\HeroBlockData;
use HeadlessAngular\Schema\Domain\Schema\HeroContentWidth;
use HeadlessAngular\Schema\Domain\Schema\HeroLayout;
use HeadlessAngular\Schema\Domain\Schema\HeroMedia;
use HeadlessAngular\Schema\Domain\Schema\HeroMediaPlacement;
use HeadlessAngular\Schema\Domain\Schema\Link\InternalLink;
use HeadlessAngular\Schema\Domain\Schema\MediaAsset;
use HeadlessAngular\Schema\Domain\Schema\PageBlock;
use HeadlessAngular\Schema\Domain\Schema\PageDefinition;
use HeadlessAngular\Schema\Domain\Schema\PageSchema;
use HeadlessAngular\Schema\Domain\Schema\PageStatus;
use HeadlessAngular\Schema\Serialization\V1PageSchemaSerializer;
use PHPUnit\Framework\TestCase;

final class V1PageSchemaSerializerTest extends TestCase
{
    public function testSerializesCanonicalHeroFixture(): void
    {
        $schema = new PageSchema(
            locale: 'en-CA',
            page: new PageDefinition(
                id: '42',
                slug: 'home',
                title: 'Home',
                status: PageStatus::Published,
                blocks: [
                    new PageBlock(
                        id: 'hero-main',
                        type: 'hero',
                        data: new HeroBlockData(
                            title: 'Software Engineer',
                            eyebrow: 'Welcome',
                            subtitle: 'Building maintainable digital experiences',
                            media: new HeroMedia(
                                image: new MediaAsset(
                                    src: 'https://cms.example.com/hero.webp',
                                    alt: 'Software engineer',
                                    width: 800,
                                    height: 800,
                                ),
                                placement: HeroMediaPlacement::End,
                            ),
                            actions: [
                                new HeroAction(
                                    id: 'view-work',
                                    label: 'View work',
                                    link: new InternalLink('/work'),
                                    variant: HeroActionVariant::Primary,
                                ),
                            ],
                            layout: new HeroLayout(
                                contentAlignment: HeroAlignment::Start,
                                verticalAlignment: HeroAlignment::Center,
                                contentWidth: HeroContentWidth::Wide,
                            ),
                        ),
                        style: new BlockStyle(
                            variant: 'primary',
                            properties: [
                                'minHeight' => '70vh',
                                'padding' => [
                                    'mobile' => '32px 20px',
                                    'desktop' => '80px 64px',
                                ],
                            ],
                        ),
                    ),
                ],
            ),
        );

        $actual = (new V1PageSchemaSerializer())->serialize($schema);
        $expected = json_decode(
            (string) file_get_contents(__DIR__ . '/../../fixtures/pageschema/v1/hero-canonical.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertSame($expected, $actual);
    }

    public function testOmitsAbsentOptionalHeroValues(): void
    {
        $schema = new PageSchema(
            locale: 'en-CA',
            page: new PageDefinition(
                id: '42',
                slug: 'home',
                title: 'Home',
                status: PageStatus::Published,
                blocks: [
                    new PageBlock(
                        id: 'hero-main',
                        type: 'hero',
                        data: new HeroBlockData(title: 'Hello'),
                    ),
                ],
            ),
        );

        $actual = (new V1PageSchemaSerializer())->serialize($schema);

        self::assertSame(['title' => 'Hello'], $actual['page']['blocks'][0]['data']);
        self::assertArrayNotHasKey('style', $actual['page']['blocks'][0]);
    }
}
