<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Plugin\CMBlogPro\Entity\Blog;

/**
  * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\CMBlogPro\Entity\Blog", mappedBy="Customer")
     */
    private $Blogs;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nick_name", type="string", length=255, nullable=true)
     */
    private $nick_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var string|null
     *
     * @ORM\Column(name="checkout_card", type="string", length=255, nullable=true)
     */
    private $checkout_card;

    /**
     * @var int
     *
     * @ORM\Column(name="affiliate", type="integer", options={"unsigned":true})
     */
    private $affiliate = 100;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;
    
    /**
     * @var string
     *
     * @ORM\Column(name="authorize_doc", type="string", length=255, nullable=true)
     */
    private $authorize_doc;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_poster", type="boolean", options={"default":false})
     */
    private $is_poster = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Blogs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add blog.
     *
     * @param Plugin\CMBlogPro\Entity\Blog $blog
     *
     * @return Customer
     */
    public function addBlog(Blog $blog)
    {
        $this->Blogs[] = $blog;

        return $this;
    }

    /**
     * Remove blog.
     *
     * @param Plugin\CMBlogPro\Entity\Blog $blog
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeOrder(Blog $blog)
    {
        return $this->Blogs->removeElement($blog);
    }

    /**
     * Get blogs.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBlogs()
    {
        return $this->Blogs;
    }    

    /**
     * Set image.
     *
     * @param string $image
     *
     * @return this
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set nick_name.
     *
     * @param string $nick_name
     *
     * @return this
     */
    public function setNickName($nick_name)
    {
        $this->nick_name = $nick_name;

        return $this;
    }

    /**
     * Get nick_name.
     *
     * @return string
     */
    public function getNickName()
    {
        return $this->nick_name;
    }

    /**
     * Set checkout_card.
     *
     * @param string $checkout_card
     *
     * @return this
     */
    public function setCheckoutCard($checkout_card)
    {
        $this->checkout_card = $checkout_card;

        return $this;
    }

    /**
     * Get checkout_card.
     *
     * @return string
     */
    public function getCheckoutCard()
    {
        return $this->checkout_card;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set affiliate.
     *
     * @param int $affiliate
     *
     * @return this
     */
    public function setAffiliate($affiliate)
    {
        $this->affiliate = $affiliate;

        return $this;
    }

    /**
     * Get affiliate.
     *
     * @return int
     */
    public function getAffiliate()
    {
        return $this->affiliate;
    }

    /**
     * Set authorize_doc.
     *
     * @param string $authorize_doc
     *
     * @return this
     */
    public function setAuthorizeDoc($authorize_doc)
    {
        $this->authorize_doc = $authorize_doc;

        return $this;
    }

    /**
     * Get authorize_doc.
     *
     * @return string
     */
    public function getAuthorizeDoc()
    {
        return $this->authorize_doc;
    }

    /**
     * Set is_poster.
     *
     * @param boolean $is_poster
     *
     * @return this
     */
    public function setIsPoster($is_poster)
    {
        $this->is_poster = $is_poster;

        return $this;
    }

    /**
     * Get is_poster.
     *
     * @return boolean
     */
    public function isPoster()
    {
        return $this->is_poster;
    }
}