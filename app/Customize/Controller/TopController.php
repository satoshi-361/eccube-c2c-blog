<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller;
use Eccube\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\CMBlogPro\Form\Type\Admin\BlogType;

use Plugin\CMBlogPro\Repository\BlogRepository;
use Plugin\CMBlogPro\Repository\CategoryRepository;
use Plugin\CMBlogPro\Repository\ConfigRepository;

use Eccube\Repository\CustomerRepository;

class TopController extends AbstractController
{
    /**
     * @var BlogRepository
     */
    protected $blogRepository;
    
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * TopController constructor.
     *
     * @param BlogRepository $blogRepository
     * @param CategoryRepository $categoryRepository
     * @param ConfigRepository $configRepository
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        BlogRepository $blogRepository,
        CategoryRepository $categoryRepository,
        ConfigRepository $configRepository,
        CustomerRepository $customerRepository
    ) {
        $this->blogRepository = $blogRepository;
        $this->categoryRepository = $categoryRepository;
        $this->configRepository = $configRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @Route("/", name="homepage", methods={"GET"})
     * @Template("index.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->createForm(BlogType::class);
        $search = $request->query->all();
        $search["status"] = 1;
        $qb = $this->blogRepository->getQueryBuilderBySearchData($search);

        $config = $this->configRepository->get();

        $pagination = $paginator->paginate(
            $qb,
            !empty($search['pageno']) ? $search['pageno'] : 1,
            !empty($search['disp_number']) ? $search['disp_number'] : $config->getDisplayPage()
        );

        return [
            'form' => $form->createView(),
            'categories' => $this->categoryRepository->getFrontCategoryList(),
            'pagination' => $pagination,
            'monthArr' => $this->setMonthArchive($search)
        ];
    }
    
    /**
     * 月別アーカイブ表示のため取得
     *
     * @param 
     *
     * @return array 月別配列
     */
    private function setMonthArchive($search = []) {
        
        $releaseDate = $this->blogRepository->getReleaseDate($search);

        $monthArr = [];
        foreach($releaseDate as $val) {

            if(is_null($val['release_date'])) {
                continue;
            }
            $key = $val['release_date']->format('Y-m');
            if(isset($monthArr[$key])) {
                continue;
            }
            $monthArr[$key] = $val['release_date']->format('Y年m月');
            
        }
        return $monthArr;
    }
    
    /**
     * @Route("/customer/{id}/detail", name="customer_detail", methods={"GET"})
     * @Template("customer_detail.twig")
     */
    public function customerDetail(Request $request, $id = null)
    {
        $Customer = $this->customerRepository->find($id);

        return [
            'Customer' => $Customer,
            'blogs' => $Customer->getBlogs()
        ];
    }

}
