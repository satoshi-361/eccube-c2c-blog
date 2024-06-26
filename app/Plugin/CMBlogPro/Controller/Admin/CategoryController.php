<?php

namespace Plugin\CMBlogPro\Controller\Admin;

use Eccube\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Eccube\Util\FormUtil;
use Plugin\CMBlogPro\Form\Type\Admin\CategoryType;
use Plugin\CMBlogPro\Form\Type\Admin\SearchCategoryType;
use Plugin\CMBlogPro\Repository\CategoryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Eccube\Entity\Category as DefaultCategory;
use Eccube\Repository\CategoryRepository as DefaultCategoryRepository;

class CategoryController extends AbstractController
{
    /**
     * @var CategoryRepository
     */
    protected $catRepository;

    /**
     * @var DefaultCategoryRepository
     */
    protected $defaultCategoryRepository;

    /**
     * CategoryController constructor.
     *
     * @param CategoryRepository $catRepository
     * @param DefaultCategoryRepository $defaultCategoryRepository
     */
    public function __construct(
        CategoryRepository $catRepository,
        DefaultCategoryRepository $defaultCategoryRepository
    )
    {
        $this->catRepository = $catRepository;
        $this->defaultCategoryRepository = $defaultCategoryRepository;
    }

    /**
     * データ保存
     * @param $form
     */
    private function save($form)
    {
        $Cat = $form->getData();
        $this->entityManager->persist($Cat);
        
        $Category = $this->defaultCategoryRepository->findOneBy([ 'name' => $Cat->getName() ]);
        if (is_null($Category)) $Category = new DefaultCategory();

        $Category->setName($Cat->getName());
        $Category->setParent(null);
        $Category->setHierarchy(1);
        $Category->setSortNo(1);

        $this->entityManager->persist($Category);
        $this->entityManager->flush();

        $this->addSuccess('登録しました。', 'admin');
        return $Cat;
    }

    /**
     * @Route("/%eccube_admin_route%/cm_blog/category", name="cm_blog_admin_cat")
     * @Template("@CMBlogPro/admin/category/index.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $form = $this->createForm(SearchCategoryType::class);
        $search = $request->query->all();

        // 検索項目設定する
        $searchData = array();
        if (isset($search['search_category'])) {
            $searchData = $search['search_category'];
        }

        $qb = $this->catRepository->getQueryBuilderBySearchData($searchData);

        $pagination = $paginator->paginate(
            $qb,
            !empty($search['page_no']) ? $search['page_no'] : 1,
            !empty($search['disp_number']) ? $search['disp_number'] : 10
        );

        return [
            'form'          => $form->createView(),
            'searchData'    => $searchData,
            'pagination'    => $pagination,
            'has_errors'    => false,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/cm_blog/category/new", name="cm_blog_admin_cat_create")
     * @Template("@CMBlogPro/admin/category/create.twig")
     */
    public function create(Request $request)
    {
        $form = $this->createForm(CategoryType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Cat = $this->save($form);
            return $this->redirectToRoute('cm_blog_admin_cat_edit', ['id' => $Cat->getId()]);
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/cm_blog/category/{id}/edit", name="cm_blog_admin_cat_edit")
     * @Template("@CMBlogPro/admin/category/edit.twig")
     */
    public function edit(Request $request, $id)
    {
        $Cat = $this->catRepository->get($id);
        // Validation
        if (!$Cat) {
            // show error and redirect
            return $this->redirectToRoute('cm_blog_admin_cat');
        }

        $form = $this->createForm(CategoryType::class, $Cat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Cat = $this->save($form);
            
            return $this->redirectToRoute('cm_blog_admin_cat_edit', ['id' => $Cat->getId()]);
        }

        return [
            'form' => $form->createView(),
            'cat'  => $Cat,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/cm_blog/category/{id}/delete", name="cm_blog_admin_cat_delete")
     */
    public function delete($id)
    {
        $Category = $this->catRepository->find($id);
        $DefaultCategory = $this->defaultCategoryRepository->findOneBy(['name' => $Category->getName()]);

        if (!$Category) {
            $this->deleteMessage();
            return $this->redirectToRoute('cm_blog_admin_cat');
        }

        try {
            $this->entityManager->remove($Category);
            $this->entityManager->flush($Category);

            if (!is_null($DefaultCategory)) {
                $this->entityManager->remove($DefaultCategory);
                $this->entityManager->flush($DefaultCategory);
            }

            $this->addSuccess('カテゴリを削除しました', 'admin');
        } catch (ForeignKeyConstraintViolationException $e) {
            $error_msg = 'カテゴリ削除失敗';
            log_error($error_msg, [$e], 'plugin');
            $this->addError($error_msg, 'admin');
        }

        return $this->redirectToRoute('cm_blog_admin_cat');
    }
}
