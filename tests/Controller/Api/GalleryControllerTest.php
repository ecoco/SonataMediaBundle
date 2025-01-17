<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Tests\Controller\Api;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use PHPUnit\Framework\TestCase;
use Sonata\MediaBundle\Controller\Api\GalleryController;
use Sonata\MediaBundle\Model\GalleryHasMediaInterface;
use Sonata\MediaBundle\Model\GalleryInterface;
use Sonata\MediaBundle\Model\GalleryManagerInterface;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Model\MediaManagerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
class GalleryControllerTest extends TestCase
{
    public function testGetGalleriesAction(): void
    {
        $gManager = $this->createMock(GalleryManagerInterface::class);
        $mediaManager = $this->createMock(MediaManagerInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $gManager->expects(self::once())->method('getPager')->willReturn([]);

        $gController = new GalleryController($gManager, $mediaManager, $formFactory, 'test');

        $paramFetcher = $this->createMock(ParamFetcherInterface::class);
        $paramFetcher->expects(self::exactly(3))->method('get');
        $paramFetcher->expects(self::once())->method('all')->willReturn([]);

        self::assertSame([], $gController->getGalleriesAction($paramFetcher));
    }

    public function testGetGalleryAction(): void
    {
        $gManager = $this->createMock(GalleryManagerInterface::class);
        $mediaManager = $this->createMock(MediaManagerInterface::class);
        $gallery = $this->createMock(GalleryInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $gManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $gController = new GalleryController($gManager, $mediaManager, $formFactory, 'test');

        self::assertSame($gallery, $gController->getGalleryAction(1));
    }

    /**
     * @dataProvider getIdsForNotFound
     */
    public function testGetGalleryNotFoundAction($identifier, string $message): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage($message);

        $gManager = $this->createMock(GalleryManagerInterface::class);
        $mediaManager = $this->createMock(MediaManagerInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);

        $gManager->expects(self::once())->method('findOneBy');

        $gController = new GalleryController($gManager, $mediaManager, $formFactory, 'test');

        $gController->getGalleryAction($identifier);
    }

    /**
     * @phpstan-return list<array{mixed, string}>
     */
    public function getIdsForNotFound(): array
    {
        return [
            [42, 'Gallery not found for identifier 42.'],
            ['42', 'Gallery not found for identifier \'42\'.'],
            [null, 'Gallery not found for identifier NULL.'],
            ['', 'Gallery not found for identifier \'\'.'],
        ];
    }

    public function testGetGalleryGalleryhasmediasAction(): void
    {
        $gManager = $this->createMock(GalleryManagerInterface::class);
        $galleryHasMedia = $this->createMock(GalleryHasMediaInterface::class);
        $gallery = $this->createMock(GalleryInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $galleryHasMedias = new ArrayCollection([$galleryHasMedia]);

        $gallery->expects(self::once())->method('getGalleryHasMedias')->willReturn($galleryHasMedias);

        $gManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $mediaManager = $this->createMock(MediaManagerInterface::class);

        $gController = new GalleryController($gManager, $mediaManager, $formFactory, 'test');

        self::assertSame($galleryHasMedias, $gController->getGalleryGalleryhasmediasAction(1));
    }

    public function testGetGalleryMediaAction(): void
    {
        $media = $this->createMock(MediaInterface::class);
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $galleryHasMedia = $this->createMock(GalleryHasMediaInterface::class);
        $galleryHasMedia->expects(self::once())->method('getMedia')->willReturn($media);

        $gallery = $this->createMock(GalleryInterface::class);
        $gallery->expects(self::once())->method('getGalleryHasMedias')->willReturn(new ArrayCollection([$galleryHasMedia]));

        $gManager = $this->createMock(GalleryManagerInterface::class);
        $gManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $mediaManager = $this->createMock(MediaManagerInterface::class);

        $gController = new GalleryController($gManager, $mediaManager, $formFactory, 'test');

        self::assertSame([$media], $gController->getGalleryMediasAction(1));
    }

    /**
     * @group legacy
     */
    public function testPostGalleryMediaGalleryhasmediaAction(): void
    {
        $media = $this->createMock(MediaInterface::class);

        $media2 = $this->createMock(MediaInterface::class);
        $media2->method('getId')->willReturn(1);

        $galleryHasMedia = $this->createMock(GalleryHasMediaInterface::class);
        $galleryHasMedia->expects(self::once())->method('getMedia')->willReturn($media2);

        $gallery = $this->createMock(GalleryInterface::class);
        $gallery->expects(self::once())->method('getGalleryHasMedias')->willReturn(new ArrayCollection([$galleryHasMedia]));

        $galleryManager = $this->createMock(GalleryManagerInterface::class);
        $galleryManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $mediaManager = $this->createMock(MediaManagerInterface::class);
        $mediaManager->expects(self::once())->method('findOneBy')->willReturn($media);

        $form = $this->createMock(Form::class);
        $form->expects(self::once())->method('handleRequest');
        $form->expects(self::once())->method('isValid')->willReturn(true);
        $form->expects(self::once())->method('getData')->willReturn($galleryHasMedia);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(self::once())->method('createNamed')->willReturn($form);

        $galleryController = new GalleryController($galleryManager, $mediaManager, $formFactory, GalleryHasMediaInterface::class);
        $view = $galleryController->postGalleryMediaGalleryhasmediaAction(1, 2, new Request());

        self::assertInstanceOf(View::class, $view);
        self::assertSame(200, $view->getResponse()->getStatusCode(), 'Should return 200');
    }

    public function testPostGalleryMediaGalleryhasmediaInvalidAction(): void
    {
        $media = $this->createMock(MediaInterface::class);
        $media->method('getId')->willReturn(1);

        $galleryHasMedia = $this->createMock(GalleryHasMediaInterface::class);
        $galleryHasMedia->expects(self::once())->method('getMedia')->willReturn($media);

        $gallery = $this->createMock(GalleryInterface::class);
        $gallery->expects(self::once())->method('getGalleryHasMedias')->willReturn(new ArrayCollection([$galleryHasMedia]));

        $galleryManager = $this->createMock(GalleryManagerInterface::class);
        $galleryManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $mediaManager = $this->createMock(MediaManagerInterface::class);
        $mediaManager->expects(self::once())->method('findOneBy')->willReturn($media);

        $formFactory = $this->createMock(FormFactoryInterface::class);

        $galleryController = new GalleryController($galleryManager, $mediaManager, $formFactory, GalleryHasMediaInterface::class);
        $view = $galleryController->postGalleryMediaGalleryhasmediaAction(1, 1, new Request());

        self::assertInstanceOf(View::class, $view);
        self::assertSame(400, $view->getResponse()->getStatusCode(), 'Should return 400');
    }

    /**
     * @group legacy
     */
    public function testPutGalleryMediaGalleryhasmediaAction(): void
    {
        $media = $this->createMock(MediaInterface::class);
        $media->method('getId')->willReturn(1);

        $galleryHasMedia = $this->createMock(GalleryHasMediaInterface::class);
        $galleryHasMedia->expects(self::once())->method('getMedia')->willReturn($media);

        $gallery = $this->createMock(GalleryInterface::class);
        $gallery->expects(self::once())->method('getGalleryHasMedias')->willReturn(new ArrayCollection([$galleryHasMedia]));

        $galleryManager = $this->createMock(GalleryManagerInterface::class);
        $galleryManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $mediaManager = $this->createMock(MediaManagerInterface::class);
        $mediaManager->expects(self::once())->method('findOneBy')->willReturn($media);

        $form = $this->createMock(Form::class);
        $form->expects(self::once())->method('handleRequest');
        $form->expects(self::once())->method('isValid')->willReturn(true);
        $form->expects(self::once())->method('getData')->willReturn($galleryHasMedia);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(self::once())->method('createNamed')->willReturn($form);

        $galleryController = new GalleryController($galleryManager, $mediaManager, $formFactory, GalleryHasMediaInterface::class);
        $view = $galleryController->putGalleryMediaGalleryhasmediaAction(1, 1, new Request());

        self::assertInstanceOf(View::class, $view);
        self::assertSame(200, $view->getResponse()->getStatusCode(), 'Should return 200');
    }

    public function testPutGalleryMediaGalleryhasmediaInvalidAction(): void
    {
        $media = $this->createMock(MediaInterface::class);
        $media->method('getId')->willReturn(1);

        $galleryHasMedia = $this->createMock(GalleryHasMediaInterface::class);
        $galleryHasMedia->expects(self::once())->method('getMedia')->willReturn($media);

        $gallery = $this->createMock(GalleryInterface::class);
        $gallery->expects(self::once())->method('getGalleryHasMedias')->willReturn(new ArrayCollection([$galleryHasMedia]));

        $galleryManager = $this->createMock(GalleryManagerInterface::class);
        $galleryManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $mediaManager = $this->createMock(MediaManagerInterface::class);
        $mediaManager->expects(self::once())->method('findOneBy')->willReturn($media);

        $form = $this->createMock(Form::class);
        $form->expects(self::once())->method('handleRequest');
        $form->expects(self::once())->method('isValid')->willReturn(false);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(self::once())->method('createNamed')->willReturn($form);

        $galleryController = new GalleryController($galleryManager, $mediaManager, $formFactory, GalleryHasMediaInterface::class);
        $view = $galleryController->putGalleryMediaGalleryhasmediaAction(1, 1, new Request());

        self::assertInstanceOf(FormInterface::class, $view);
    }

    public function testDeleteGalleryMediaGalleryhasmediaAction(): void
    {
        $media = $this->createMock(MediaInterface::class);
        $media->method('getId')->willReturn(1);

        $galleryHasMedia = $this->createMock(GalleryHasMediaInterface::class);
        $galleryHasMedia->expects(self::once())->method('getMedia')->willReturn($media);

        $gallery = $this->createMock(GalleryInterface::class);
        $gallery->method('getGalleryHasMedias')->willReturn(new ArrayCollection([$galleryHasMedia]));

        $galleryManager = $this->createMock(GalleryManagerInterface::class);
        $galleryManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $mediaManager = $this->createMock(MediaManagerInterface::class);
        $mediaManager->expects(self::once())->method('findOneBy')->willReturn($media);

        $formFactory = $this->createMock(FormFactoryInterface::class);

        $galleryController = new GalleryController($galleryManager, $mediaManager, $formFactory, GalleryHasMediaInterface::class);
        $view = $galleryController->deleteGalleryMediaGalleryhasmediaAction(1, 1);

        self::assertSame(['deleted' => true], $view);
    }

    public function testDeleteGalleryMediaGalleryhasmediaInvalidAction(): void
    {
        $media = $this->createMock(MediaInterface::class);

        $media2 = $this->createMock(MediaInterface::class);
        $media2->method('getId')->willReturn(2);

        $galleryHasMedia = $this->createMock(GalleryHasMediaInterface::class);
        $galleryHasMedia->expects(self::once())->method('getMedia')->willReturn($media2);

        $gallery = $this->createMock(GalleryInterface::class);
        $gallery->method('getGalleryHasMedias')->willReturn(new ArrayCollection([$galleryHasMedia]));

        $galleryManager = $this->createMock(GalleryManagerInterface::class);
        $galleryManager->expects(self::once())->method('findOneBy')->willReturn($gallery);

        $mediaManager = $this->createMock(MediaManagerInterface::class);
        $mediaManager->expects(self::once())->method('findOneBy')->willReturn($media);

        $formFactory = $this->createMock(FormFactoryInterface::class);

        $galleryController = new GalleryController($galleryManager, $mediaManager, $formFactory, GalleryHasMediaInterface::class);
        $view = $galleryController->deleteGalleryMediaGalleryhasmediaAction(1, 1);

        self::assertInstanceOf(View::class, $view);
        self::assertSame(400, $view->getResponse()->getStatusCode(), 'Should return 400');
    }
}
