<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use \Plugin\CMBlogPro\Entity\Blog;

/**
  * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var Blog
     *
     * @ORM\OneToOne(targetEntity="Plugin\CMBlogPro\Entity\Blog", mappedBy="Product")
     */
    private $Blog; 

    /**
     * Set Blog.
     *
     * @param Blog|null $Blog
     *
     * @return this
     */
    public function setBlog(Blog $Blog = null)
    {
        $this->Blog = $Blog;

        return $this;
    }

    /**
     * Get Blog.
     *
     * @return Blog|null
     */
    public function getBlog()
    {
        return $this->Blog;
    }

}