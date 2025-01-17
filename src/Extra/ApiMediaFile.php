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

namespace Sonata\MediaBundle\Extra;

use Symfony\Component\HttpFoundation\File\File;

/**
 * @final since sonata-project/media-bundle 3.21.0
 */
class ApiMediaFile extends File
{
    /**
     * @var string|null
     */
    protected $extension;

    /**
     * @var string|null
     */
    protected $mimetype;

    /**
     * @var resource
     */
    protected $resource;

    public function __construct($handle)
    {
        if (!\is_resource($handle)) {
            throw new \RuntimeException('handle is not a resource');
        }

        $this->resource = $handle;

        $meta = stream_get_meta_data($handle);

        parent::__construct($meta['uri']);
    }

    public function getExtension()
    {
        return $this->extension ?? parent::getExtension();
    }

    /**
     * @param string $extension
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    public function getMimeType()
    {
        return $this->mimetype ?? parent::getMimeType();
    }

    /**
     * @param string $mimetype
     */
    public function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;
    }

    public function guessExtension()
    {
        return $this->extension ?? parent::guessExtension();
    }
}
