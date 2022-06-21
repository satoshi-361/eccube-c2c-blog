<?php

namespace Plugin\CMBlogPro\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

if (!class_exists('\Plugin\CMBlogPro\Entity\Blog')) {
    /**
     * Blog
     *
     * @ORM\Table(name="plg_blog_data2")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Plugin\CMBlogPro\Repository\BlogRepository")
     */
    class Blog extends \Eccube\Entity\AbstractEntity
    {

        /**
         * @return string
         */
        public function __toString()
        {
            return (string) $this->getName();
        }

        /**
         * Is Enable
         *
         * @return bool
         *
         * @deprecated
         */
        public function isEnable()
        {
            return $this->getStatus()->getId() === \Plugin\CMBlogPro\Entity\BlogStatus::DISPLAY_SHOW ? true : false;
        }

        public function getMainListImage()
        {
            $BlogImages = $this->getBlogImage();

            return empty($BlogImages) ? null : $BlogImages[0];
        }

        public function getMainFileName()
        {
            if (count($this->BlogImage) > 0) {
                return $this->BlogImage[0];
            } else {
                return null;
            }
        }
        

        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var string
         *
         * @ORM\Column(name="title", type="string", length=200)
         */
        private $title;

        /**
         * @var \Doctrine\Common\Collections\Collection
         *
         * @ORM\OneToMany(targetEntity="Plugin\CMBlogPro\Entity\BlogCategory", mappedBy="Blog", cascade={"persist","remove"})
         */
        private $BlogCategories;

        /**
         * @var \Doctrine\Common\Collections\Collection
         *
         * @ORM\OneToMany(targetEntity="Plugin\CMBlogPro\Entity\BlogImage", mappedBy="Blog", cascade={"remove"})
         * @ORM\OrderBy({
         *     "sort_no"="ASC"
         * })
         */
        private $BlogImage;

        /**
         * @var text|null
         *
         * @ORM\Column(name="body", type="text", nullable=true)
         */
        private $body;

        /**
         * @var string|null
         *
         * @ORM\Column(name="author", type="string", length=255, nullable=true)
         */
        private $author;

        /**
         * @var string|null
         *
         * @ORM\Column(name="description", type="string", length=255, nullable=true)
         */
        private $description;

        /**
         * @var string|null
         *
         * @ORM\Column(name="keyword", type="string", length=255, nullable=true)
         */
        private $keyword;

        /**
         * @var string|null
         *
         * @ORM\Column(name="meta_robots", type="string", length=255, nullable=true)
         */
        private $robot;

        /**
         * @var string|null
         *
         * @ORM\Column(name="meta_tags", type="text", nullable=true)
         */
        private $metatag;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="release_date", type="datetime", nullable=true)
         */
        private $release_date;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetime")
         */
        private $create_date;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="update_date", type="datetime")
         */
        private $update_date;

        /**
         * @var string|null
         *
         * @ORM\Column(name="stock", type="decimal", precision=10, scale=0, nullable=true)
         */
        private $stock;

        /**
         * @var boolean
         *
         * @ORM\Column(name="stock_unlimited", type="boolean", options={"default":false})
         */
        private $stock_unlimited = false;

        /**
         * @var string|null
         *
         * @ORM\Column(name="sale_limit", type="decimal", precision=10, scale=0, nullable=true, options={"unsigned":true})
         */
        private $sale_limit;

        /**
         * @var string|null
         *
         * @ORM\Column(name="price01", type="decimal", precision=12, scale=2, nullable=true)
         */
        private $price01;

        /**
         * @var string
         *
         * @ORM\Column(name="price02", type="decimal", precision=12, scale=2)
         */
        private $price02;

        /**
         * @var \Eccube\Entity\Member
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
         * })
         */
        private $Creator;

        /**
         * @var \Plugin\CMBlogPro\Entity\BlogStatus
         *
         * @ORM\ManyToOne(targetEntity="Plugin\CMBlogPro\Entity\BlogStatus")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="blog_status_id", referencedColumnName="id")
         * })
         */
        private $Status;

        /**
         * @var tag|null
         *
         * @ORM\Column(name="tag", type="text", nullable=true)
         */
        private $tag;

        /**
         * @var \Eccube\Entity\Customer
         *
         * @ORM\ManyToOne(targetEntity="\Eccube\Entity\Customer", inversedBy="Blogs")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
         * })
         */
        private $Customer;

        /**
         * @var \Eccube\Entity\Product
         *
         * @ORM\OneToOne(targetEntity="\Eccube\Entity\Product", inversedBy="Blog")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
         * })
         */
        private $Product;

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->BlogCategories = new \Doctrine\Common\Collections\ArrayCollection();
            $this->BlogImage = new \Doctrine\Common\Collections\ArrayCollection();
        }

        public function __clone()
        {
            $this->id = null;
        }

        public function copy()
        {
            // コピー対象外

            $Categories = $this->getBlogCategories();
            $this->BlogCategories = new ArrayCollection();
            foreach ($Categories as $Category) {
                $CopyCategory = clone $Category;
                $this->addBlogCategory($CopyCategory);
                $CopyCategory->setBlog($this);
            }

            $Images = $this->getBlogImage();
            $this->BlogImage = new ArrayCollection();
            foreach ($Images as $Image) {
                $CloneImage = clone $Image;
                $this->addBlogImage($CloneImage);
                $CloneImage->setBlog($this);
            }

            return $this;
        }

        /**
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * @return string
         */
        public function getTitle()
        {
            return $this->title;
        }

        /**
         * @param string $title
         *
         * @return Blog;
         */
        public function setTitle($title)
        {
            $this->title = $title;

            return $this;
        }

        /**
         * Add blogCategory.
         *
         * @param \Plugin\CMBlogPro\Entity\BlogCategory $blogCategory
         *
         * @return Blog
         */
        public function addBlogCategory(\Plugin\CMBlogPro\Entity\BlogCategory $blogCategory)
        {
            $this->BlogCategories[] = $blogCategory;

            return $this;
        }

        /**
         * Remove blogCategory.
         *
         * @param \Plugin\CMBlogPro\Entity\BlogCategory $blogCategory
         *
         * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
         */
        public function removeBlogCategory(\Plugin\CMBlogPro\Entity\BlogCategory $blogCategory)
        {
            return $this->BlogCategories->removeElement($blogCategory);
        }

        /**
         * Get blogCategories.
         *
         * @return \Doctrine\Common\Collections\Collection
         */
        public function getBlogCategories()
        {
            return $this->BlogCategories;
        }

         /**
         * Add blogImage.
         *
         * @param \Plugin\CMBlogPro\Entity\BlogImage $blogImage
         *
         * @return Blog
         */
        public function addBlogImage(\Plugin\CMBlogPro\Entity\BlogImage $blogImage)
        {
            $this->BlogImage[] = $blogImage;

            return $this;
        }

        /**
         * Remove blogImage.
         *
         * @param \Plugin\CMBlogPro\Entity\BlogImage $blogImage
         *
         * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
         */
        public function removeBlogImage(\Plugin\CMBlogPro\Entity\BlogImage $blogImage)
        {
            return $this->BlogImage->removeElement($blogImage);
        }

        /**
         * Get blogImage.
         *
         * @return \Doctrine\Common\Collections\Collection
         */
        public function getBlogImage()
        {
            return $this->BlogImage;
        }

        /**
         * @return string
         */
        public function getBody()
        {
            return $this->body;
        }

        /**
         * @param string $body
         *
         * @return Blog;
         */
        public function setBody($body)
        {
            $this->body = $body;

            return $this;
        }

        /**
         * @return string
         */
        public function getAuthor()
        {
            return $this->author;
        }

        /**
         * @param string $author
         *
         * @return Blog;
         */
        public function setAuthor($author)
        {
            $this->author = $author;

            return $this;
        }

        /**
         * @return string
         */
        public function getDescription()
        {
            return $this->description;
        }

        /**
         * @param string $description
         *
         * @return Blog;
         */
        public function setDescription($description)
        {
            $this->description = $description;

            return $this;
        }

        /**
         * @return string
         */
        public function getKeyword()
        {
            return $this->keyword;
        }

        /**
         * @param string $keyword
         *
         * @return Blog;
         */
        public function setKeyword($keyword)
        {
            $this->keyword = $keyword;

            return $this;
        }

        /**
         * @return string
         */
        public function getRobot()
        {
            return $this->robot;
        }

        /**
         * @param string $robot
         *
         * @return Blog;
         */
        public function setRobot($robot)
        {
            $this->robot = $robot;

            return $this;
        }

        /**
         * @return string
         */
        public function getMetatag()
        {
            return $this->metatag;
        }

        /**
         * @param string $metatag
         *
         * @return Blog;
         */
        public function setMetatag($metatag)
        {
            $this->metatag = $metatag;

            return $this;
        }

        /**
         * Set releaseDate.
         *
         * @param \DateTime $releaseDate
         *
         * @return Blog
         */
        public function setReleaseDate($releaseDate)
        {
            $this->release_date = $releaseDate;

            return $this;
        }

        /**
         * Get releaseDate.
         *
         * @return \DateTime
         */
        public function getReleaseDate()
        {
            return $this->release_date;
        }

        /**
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return Blog
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;

            return $this;
        }

        /**
         * Get createDate.
         *
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * Set updateDate.
         *
         * @param \DateTime $updateDate
         *
         * @return Blog
         */
        public function setUpdateDate($updateDate)
        {
            $this->update_date = $updateDate;

            return $this;
        }

        /**
         * Get updateDate.
         *
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }

        /**
         * Set creator.
         *
         * @param \Eccube\Entity\Member|null $creator
         *
         * @return Blog
         */
        public function setCreator(\Eccube\Entity\Member $creator = null)
        {
            $this->Creator = $creator;

            return $this;
        }

        /**
         * Get creator.
         *
         * @return \Eccube\Entity\Member|null
         */
        public function getCreator()
        {
            return $this->Creator;
        }

        /**
         * Set status.
         *
         * @param \Plugin\CMBlogPro\Entity\BlogStatus|null $status
         *
         * @return Blog
         */
        public function setStatus(\Plugin\CMBlogPro\Entity\BlogStatus $status = null)
        {
            $this->Status = $status;

            return $this;
        }

        /**
         * Get status.
         *
         * @return \Plugin\CMBlogPro\Entity\BlogStatus|null
         */
        public function getStatus()
        {
            return $this->Status;
        }

        /**
         * @return string
         */
        public function getTag()
        {
            return $this->tag;
        }

        /**
         * @param string $tag
         *
         * @return Blog;
         */
        public function setTag($tag)
        {
            $this->tag = $tag;

            return $this;
        }

        /**
         * Set customer.
         *
         * @param \Eccube\Entity\Customer|null $customer
         *
         * @return Blog
         */
        public function setCustomer(\Eccube\Entity\Customer $customer = null)
        {
            $this->Customer = $customer;

            return $this;
        }

        /**
         * Get customer.
         *
         * @return \Eccube\Entity\Customer|null
         */
        public function getCustomer()
        {
            return $this->Customer;
        }

        /**
         * Set Product.
         *
         * @param \Eccube\Entity\Product|null $Product
         *
         * @return Blog
         */
        public function setProduct(\Eccube\Entity\Product $Product = null)
        {
            $this->Product = $Product;

            return $this;
        }

        /**
         * Get Product.
         *
         * @return \Eccube\Entity\Product|null
         */
        public function getProduct()
        {
            return $this->Product;
        }
        
        /**
         * Get StockFind
         *
         * @return bool
         */
        public function getStockFind()
        {
            if ($this->getStock() > 0 || $this->isStockUnlimited()) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Set stock.
         *
         * @param string|null $stock
         *
         * @return Blog
         */
        public function setStock($stock = null)
        {
            $this->stock = $stock;

            return $this;
        }

        /**
         * Get stock.
         *
         * @return string|null
         */
        public function getStock()
        {
            return $this->stock;
        }

        /**
         * Set stockUnlimited.
         *
         * @param boolean $stockUnlimited
         *
         * @return Blog
         */
        public function setStockUnlimited($stockUnlimited)
        {
            $this->stock_unlimited = $stockUnlimited;

            return $this;
        }

        /**
         * Get stockUnlimited.
         *
         * @return boolean
         */
        public function isStockUnlimited()
        {
            return $this->stock_unlimited;
        }
        
        /**
         * Set saleLimit.
         *
         * @param string|null $saleLimit
         *
         * @return Blog
         */
        public function setSaleLimit($saleLimit = null)
        {
            $this->sale_limit = $saleLimit;

            return $this;
        }

        /**
         * Get saleLimit.
         *
         * @return string|null
         */
        public function getSaleLimit()
        {
            return $this->sale_limit;
        }

        /**
         * Set price01.
         *
         * @param string|null $price01
         *
         * @return Blog
         */
        public function setPrice01($price01 = null)
        {
            $this->price01 = $price01;

            return $this;
        }

        /**
         * Get price01.
         *
         * @return string|null
         */
        public function getPrice01()
        {
            return $this->price01;
        }

        /**
         * Set price02.
         *
         * @param string $price02
         *
         * @return Blog
         */
        public function setPrice02($price02)
        {
            $this->price02 = $price02;

            return $this;
        }

        /**
         * Get price02.
         *
         * @return string
         */
        public function getPrice02()
        {
            return $this->price02;
        }
    }

}