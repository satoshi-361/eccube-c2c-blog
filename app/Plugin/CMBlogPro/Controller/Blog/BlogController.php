<?php

namespace Plugin\CMBlogPro\Controller\Blog;


use Eccube\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\CMBlogPro\Entity\Blog;
use Plugin\CMBlogPro\Entity\BlogStatus;
use Plugin\CMBlogPro\Form\Type\Admin\BlogType;
use Plugin\CMBlogPro\Repository\BlogRepository;
use Plugin\CMBlogPro\Repository\CategoryRepository;
use Plugin\CMBlogPro\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Repository\PageRepository;
use Plugin\CMBlogPro\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;

use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Form\Type\AddCartType;

use Plugin\CMBlogPro\Entity\BlogImage;
use Plugin\CMBlogPro\Entity\BlogCategory;
use Plugin\CMBlogPro\Form\Type\Admin\SearchBlogType;
use Plugin\CMBlogPro\Repository\BlogStatusRepository;
use Plugin\CMBlogPro\Repository\BlogImageRepository;

use Eccube\Repository\Master\PageMaxRepository;

use Eccube\Util\CacheUtil;
use Eccube\Util\FormUtil;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogController extends AbstractController
{
    const USERDATAPATH = 'html/user_data/';

    /**
     * @var BlogStatusRepository
     */
    protected $blogStatusRepository;

    /**
     * @var BlogImageRepository
     */
    protected $blogImageRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var BlogRepository
     */
    protected $blogRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * BlogController constructor.
     *
     * @param BlogStatusRepository $blogStatusRepository
     * @param BlogImageRepository $blogImageRepository
     * @param BlogRepository $blogRepository
     * @param CategoryRepository $categoryRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param ProductRepository $productRepository
     * @param ConfigRepository $configRepository
     * @param PageRepository $pageRepository
     */
    public function __construct(
        BlogStatusRepository $blogStatusRepository,
        BlogImageRepository $blogImageRepository,
        PageMaxRepository $pageMaxRepository,
        BlogRepository $blogRepository,
        CategoryRepository $categoryRepository,
        PageRepository $pageRepository,
        ProductRepository $productRepository,
        ConfigRepository $configRepository)
    {
        $this->blogRepository = $blogRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->configRepository = $configRepository;
        $this->pageRepository = $pageRepository;
        $this->blogStatusRepository = $blogStatusRepository;
        $this->blogImageRepository = $blogImageRepository;
        $this->pageMaxRepository = $pageMaxRepository;
    }

    /**
     * @Route("/blog/", name="cm_blog_pro_page_list")
     * @Template("blog/list.twig")
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
     * @Route("/blog/{id}/detail", name="cm_blog_pro_page_detail")
     * @Template("blog/detail.twig")
     */
    public function detail(Request $request, $id)
    {
        //postgresql→int の最大値：2147483647だから、最大値を超えるとSlug として判断
        if(is_numeric($id) && $id <= 2147483647) {

            $blog = $this->blogRepository->get($id);
        }

        if (!$blog || !$this->checkVisibility($blog)) {
            $this->addError('ブログを見つかりませんでした。');
            return $this->redirectToRoute('cm_blog_pro_page_list');
        }

        $config = $this->configRepository->get();

        // $form = $this->createForm(BlogType::class, $blog);

        $cmPage = $this->pageRepository->findOneBy(['url'  => 'cm_blog_pro_page_detail']);
    
        if($blog->getAuthor() != Null){
            $Page["author"] = $blog->getAuthor();
        }else $Page["author"] = $cmPage->getAuthor();
            

        if($blog->getDescription() != Null){
            $Page["description"] = $blog->getDescription();
        }else $Page["description"] = $cmPage->getDescription();
        
        if($blog->getKeyword() != Null){
            $Page["keyword"] = $blog->getKeyword();
        }else $Page["keyword"] = $cmPage->getKeyword();
        

        if($blog->getRobot() != Null){
            $Page["meta_robots"] = $blog->getRobot();
        }else $Page["meta_robots"] = $cmPage->getMetaRobots();
        

        if($blog->getMetatag() != Null){
            $Page["meta_tags"] = $blog->getMetatag();
        }else $Page["meta_tags"] = $cmPage->getMetaTags();

        $tagArr = [];
        if($blog->getTag() != Null){
            $tagArr = preg_split('/[;,　、]+/u', $blog->getTag());
        }

        $Product = $blog->getProduct();

        $builder = $this->formFactory->createNamedBuilder(
            '',
            AddCartType::class,
            null,
            [
                'product' => $Product,
                'id_add_product_id' => false,
            ]
        );

        return [
            'form' => $builder->getForm()->createView(),
            'Product' => $Product,
            'blog' => $blog,
            'Page' => $Page,
            'subtitle' => $blog->getTitle(),
            'tags' => $tagArr,
            'monthArr' => $this->setMonthArchive()
        ];
    }

    /**
     * 閲覧可能なブログかどうかを判定
     *
     * @param Blog $blog
     *
     * @return boolean 閲覧可能な場合はtrue
     */
    protected function checkVisibility(Blog $blog)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            if ($blog->getStatus()->getId() !== BlogStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
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
     * @Route("/blog/image/add", name="cm_blog_pro_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
 
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('CMBlogPro_admin_blog');

        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $files = [];
        if (count($images) > 0) {
            foreach ($images as $img) {
                foreach ($img as $image) {
                    //ファイルフォーマット検証
                    $mimeType = $image->getMimeType();
                    if (0 !== strpos($mimeType, 'image')) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    // 拡張子
                    $extension = $image->getClientOriginalExtension();
                    if (!in_array(strtolower($extension), $allowExtensions)) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    $filename = date('mdHis').uniqid('_').'.'.$extension;
                    //$fullNamePath =  $this->eccubeConfig['eccube_user_data_route'] .'/'. $filename;

                    $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
                    //$image->move($this->eccubeConfig['eccube_temp_image_dir'], $fullNamePath);
                    $files[] = $filename;
                }
            }
        }

        // $event = new EventArgs(
        //     [
        //         'images' => $images,
        //         'files' => $files,
        //     ],
        //     $request
        // );
        // $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_ADD_IMAGE_COMPLETE, $event);
        //$files = $event->getArgument('files');

        return $this->json(['files' => $files], 200);
    }

    /**
     * @Route("/blog/upload", name="cm_blog_pro_upload", methods={"POST"})
     */
    public function uploadBlogImage(Request $request)
    {
        try {
            // File Route.
            $fileRoute = $this->eccubeConfig['eccube_save_image_dir'];

            reset ($_FILES);
            $temp = current($_FILES);

            if (is_uploaded_file($temp["tmp_name"])){

                if (preg_match("/([^wsd\-_~,;:[]().])|([.]{2,})/", $temp["name"])) {
                    throw new UnsupportedMediaTypeHttpException();
                }

                $extension = pathinfo($temp["name"], PATHINFO_EXTENSION);
                $fileName  = date('mdHis').uniqid('_').'.'.$extension;
                $fullNamePath = $fileRoute .'/'. $fileName;
                
                if (!in_array(strtolower($extension), array('gif', 'jpg', 'jpeg', 'png'))) {
                    throw new UnsupportedMediaTypeHttpException();
                }
                move_uploaded_file($temp["tmp_name"], $fullNamePath);

                // Check server protocol and load resources accordingly.
                if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") {
                    $protocol = "https://";
                } else {
                    $protocol = "http://";
                }
                $currentPath = $_SERVER['PHP_SELF']; 
                $pathInfo = pathinfo($currentPath); 
                
                return $this->json([
                    'uploaded' => 1,
                    'filename' => $fileName,
                    // 'location' => '/html/upload/save_image/' . $fileName,
                    'location' => $protocol.$_SERVER["HTTP_HOST"].$pathInfo['dirname'].'/html/upload/save_image/' . $fileName,
                ]);
            } else {
                throw new \Exception("ファイルアップロードに失敗しました");
            }
        } catch (\Exception $exception) {

            return $this->json([
                'uploaded' => 0,
                'error' => array(
                    'message' => $exception->getMessage(),
                )
            ]);
        }
    }
        
    /**
     * ブログ編集
     *
     * @Route("/blog/new", name="cm_blog_pro_create")
     * @Route("/blog/{id}/edit", requirements={"id" = "\d+"}, name="cm_blog_pro_edit")
     * @Template("blog/edit.twig")
     */
    public function edit(Request $request, $id = null, RouterInterface $router, CacheUtil $cacheUtil)
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('mypage_login');
        }

        $Customer = $this->getUser();

        if (is_null($id)) {
            $Blog = new Blog();
            $BlogStatus = $this->blogStatusRepository->find(BlogStatus::DISPLAY_HIDE);
            $Blog
                ->setStatus($BlogStatus)
                ->setStockUnlimited(true)
                ->setCustomer($Customer);
        } else {
            $Blog = $this->blogRepository->find($id);
            if (!$Blog) {
                throw new NotFoundHttpException();
            }

            if ( $Blog->getCustomer()->getId() != $Customer->getId() )
                return $this->redirectToRoute('mypage_login');
        }

        $builder = $this->formFactory->createBuilder(BlogType::class, $Blog);

        // $event = new EventArgs(
        //     [
        //         'builder' => $builder,
        //         'Blog' => $Blog,
        //     ],
        //     $request
        // );

        // $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_EDIT_INITIALIZE, $event);

        $form = $builder->getForm();

        // ファイルの登録
        $images = [];
        $BlogImages = $Blog->getBlogImage();
        foreach ($BlogImages as $BlogImage) {
            $images[] = $BlogImage->getFileName();
        }
        $form['images']->setData($images);

        $categories = [];
        $BlogCategories = $Blog->getBlogCategories();
        foreach ($BlogCategories as $BlogCategory) {
            /* @var $BlogCategory \Plugin\CMBlogPro\Entity\BlogCategory */
            $categories[] = $BlogCategory->getCategory();
        }
        $form['Category']->setData($categories);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            
            if ($form->isSubmitted() && $form->isValid()) {
                log_info('ブログ記事登録開始', [$id]);

                $body = $form['body']->getData();
            
                $body = str_replace('＜', '<', $body);
                $body = str_replace('＞', '>', $body);
                $body = str_replace('＆', '&', $body);

                $Blog = $form->getData();
                $Blog->setBody($body);

                // カテゴリの登録
                // 一度クリア
                /* @var $Blog \Plugin\CMBlogPro\Entity\Blog */
                foreach ($Blog->getBlogCategories() as $BlogCategory) {
                    $Blog->removeBlogCategory($BlogCategory);
                    $this->entityManager->remove($BlogCategory);
                }
                // if (array_key_exists('is_for_restaurant', $request->request->get('CMBlogPro_pro')))
                //     $Blog->setIsForRestaurant($request->request->get('CMBlogPro_pro')['is_for_restaurant']);
                // else
                //     $Blog->setIsForRestaurant(0);

                $this->entityManager->persist($Blog);
                $this->entityManager->flush();

                $count = 1;
                $Categories = $form->get('Category')->getData();
                $categoriesIdList = [];
                foreach ($Categories as $Category) {
                    // Only 1 level categories right now
                    // foreach ($Category->getPath() as $ParentCategory) {
                    //     if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                    //         $BlogCategory = $this->createBlogCategory($Blog, $ParentCategory, $count);
                    //         $this->entityManager->persist($BlogCategory);
                    //         $count++;
                    //         /* @var $Blog \Plugin\CMBlogPro\Entity\Blog */
                    //         $Blog->addBlogCategory($BlogCategory);
                    //         $categoriesIdList[$ParentCategory->getId()] = true;
                    //     }
                    // }
                    if (!isset($categoriesIdList[$Category->getId()])) {
                        $BlogCategory = $this->createBlogCategory($Blog, $Category, $count);
                        $this->entityManager->persist($BlogCategory);
                        $count++;
                        /* @var $Blog \Plugin\CMBlogPro\Entity\Blog */
                        $Blog->addBlogCategory($BlogCategory);
                        //$categoriesIdList[$ParentCategory->getId()] = true;
                    }
                }

                // 画像の登録
                $add_images = $form->get('add_images')->getData();
                foreach ($add_images as $add_image) {
                    $BlogImage = new \Plugin\CMBlogPro\Entity\BlogImage();
                    $BlogImage
                        ->setFileName($add_image)
                        ->setBlog($Blog)
                        ->setSortNo(1);
                    $Blog->addBlogImage($BlogImage);
                    $this->entityManager->persist($BlogImage);

                    // 移動
                    $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'. $add_image);
                    $file->move($this->eccubeConfig['eccube_save_image_dir']);
                    //$file->move($this->eccubeConfig['eccube_user_data_route'].'/cm_blog/images/');
                }

                // 画像の削除
                $delete_images = $form->get('delete_images')->getData();
                foreach ($delete_images as $delete_image) {

                    $BlogImage = $this->blogImageRepository
                        ->findOneBy(['file_name' => $delete_image]);

                    // 追加してすぐに削除した画像は、Entityに追加されない
                    if ($BlogImage instanceof BlogImage) {
                        $Blog->removeBlogImage($BlogImage);
                        $this->entityManager->remove($BlogImage);
                    }
                    $this->entityManager->persist($Blog);

                    // 削除
                    $fs = new Filesystem();
                    $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$delete_image);
                    //$fs->remove( $this->eccubeConfig['eccube_user_data_route'].'/cm_blog/images/'.$delete_image);
                }
                $Blog->setUpdateDate(new \DateTime());
                $Blog->setCustomer($Customer);

                $this->entityManager->persist($Blog);
                $this->entityManager->flush();

                $sortNos = $request->get('sort_no_images');
                if ($sortNos) {
                    foreach ($sortNos as $sortNo) {
                        list($filename, $sortNo_val) = explode('//', $sortNo);
                        $BlogImage = $this->blogImageRepository
                            ->findOneBy([
                                'file_name' => $filename,
                                'Blog' => $Blog,
                            ]);
                        $BlogImage->setSortNo($sortNo_val);
                        $this->entityManager->persist($BlogImage);
                    }
                }
                $this->entityManager->flush();

                log_info('商品登録完了', [$id]);

                // $event = new EventArgs(
                //     [
                //         'form' => $form,
                //         'Blog' => $Blog,
                //     ],
                //     $request
                // );
                // $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_EDIT_COMPLETE, $event);

                $this->addSuccess('admin.common.save_complete');

                if ($returnLink = $form->get('return_link')->getData()) {
                    try {
                        // $returnLinkはpathの形式で渡される. pathが存在するかをルータでチェックする.
                        $pattern = '/^'.preg_quote($request->getBasePath(), '/').'/';
                        $returnLink = preg_replace($pattern, '', $returnLink);
                        $result = $router->match($returnLink);
                        // パラメータのみ抽出
                        $params = array_filter($result, function ($key) {
                            return 0 !== \strpos($key, '_');
                        }, ARRAY_FILTER_USE_KEY);

                        // pathからurlを再構築してリダイレクト.
                        return $this->redirectToRoute($result['_route'], $params);
                    } catch (\Exception $e) {
                        // マッチしない場合はログ出力してスキップ.
                        log_warning('URLの形式が不正です。');
                    }
                }

                $cacheUtil->clearDoctrineCache();

                return $this->redirectToRoute('cm_blog_pro_edit', ['id' => $Blog->getId()]);
            }
        }

        // 検索結果の保持
        $builder = $this->formFactory
            ->createBuilder(SearchBlogType::class);

        // $event = new EventArgs(
        //     [
        //         'builder' => $builder,
        //         'Blog' => $Blog,
        //     ],
        //     $request
        // );
        // $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_EDIT_SEARCH, $event);

        $searchForm = $builder->getForm();

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
        }


        // ツリー表示のため、ルートからのカテゴリを取得
        $TopCategories = $this->categoryRepository->getList(null);

        // ツリー表示のため、ルートからの商品を取得
        $Products = $this->productRepository->getList(null);
        // $ChoicedCategoryIds = array_map(function ($Category) {
        //     return $Category->getId();
        // }, $form->get('Category')->getData());

        return [
            'Blog' => $Blog,
            'form' => $form->createView(),
            'searchForm' => $searchForm->createView(),
            'id' => $id,
            'TopCategories' => $TopCategories,
            'Products'      => $Products,
            //'ChoicedCategoryIds' => $ChoicedCategoryIds,
            'ChoicedCategoryIds' => $this->setChoicedCategoryIds($Blog),
            'RelatedProductIds'  => [],
        ];


        // return [
        //     'form' => $form->createView(),
        //     'Blog' => $Blog,
        //     'Categories' => $this->categoryRepository->getList(array()),
        //     'ChoicedCategoryIds' => $this->setChoicedCategoryIds($Blog),
        // ];
    }
        
    /**
     * ブログ削除
     *
     * @Route("/blog/{id}/delete", name="cm_blog_pro_delete")
     */
    public function delete($id)
    {
        $blog = $this->blogRepository->find($id);

        if (!$blog) {
            // 無い場合
            $this->deleteMessage();
            return $this->redirectToRoute('cm_blog_pro_page_list');
        }

        // TODO：TRANSACTIONを追加する
        try {
            // イメージをパスを取得する
            // データ削除
            $this->entityManager->remove($blog);
            $this->entityManager->flush($blog);
            // イメージを削除する
            $config = $this->configRepository->get();
            $this->addSuccess('ブログを削除しました');
        } catch (ForeignKeyConstraintViolationException $e) {
            $error_msg = 'カテゴリ削除失敗';
            log_error($error_msg, [$e], 'plugin');
            $this->addError($error_msg);
        }

        return $this->redirectToRoute('cm_blog_pro_page_list');
    }

    /**
     * @Route("/blog/{id}/copy", requirements={"id" = "\d+"}, name="cm_blog_pro_copy", methods={"POST"})
     */
    public function copy(Request $request, $id = null)
    {
        $this->isTokenValid();

        if (!is_null($id)) {
            $Blog = $this->blogRepository->find($id);

            if ($Blog instanceof Blog) {

                // Only categories, no classes or tags for now. Need to fix the images

                $CopyBlog = clone $Blog;
                $CopyBlog->copy();
                $BlogStatus = $this->blogStatusRepository->find(BlogStatus::DISPLAY_HIDE);
                $CopyBlog->setStatus($BlogStatus);

                $CopyBlogCategories = $CopyBlog->getBlogCategories();
                foreach ($CopyBlogCategories as $Category) {
                    $this->entityManager->persist($Category);
                }

                $Images = $CopyBlog->getBlogImage();
                foreach ($Images as $Image) {
                    // 画像ファイルを新規作成
                    $extension = pathinfo($Image->getFileName(), PATHINFO_EXTENSION);
                    $filename = date('mdHis').uniqid('_').'.'.$extension;
                    try {
                        $fs = new Filesystem();
                        $fs->copy($this->eccubeConfig['eccube_save_image_dir'].'/'.$Image->getFileName(), $this->eccubeConfig['eccube_save_image_dir'].'/'.$filename);
                    } catch (\Exception $e) {
                        // エラーが発生しても無視する
                    }
                    $Image->setFileName($filename);

                    $this->entityManager->persist($Image);
                }
               
                $this->entityManager->persist($CopyBlog);

                $this->entityManager->flush();

                // $event = new EventArgs(
                //     [
                //         'Blog' => $Blog,
                //         'CopyBlog' => $CopyBlog,
                //         'CopyBlogCategories' => $CopyBlogCategories,
                //         'CopyBlogClasses' => $CopyBlogClasses,
                //         'images' => $Images,
                //         'Tags' => $Tags,
                //     ],
                //     $request
                // );
                // $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_PRODUCT_COPY_COMPLETE, $event);

                $this->addSuccess('plg.CMBlogPro.admin.blog.copy_complete');

                return $this->redirectToRoute('cm_blog_pro_edit', ['id' => $CopyBlog->getId()]);
            } else {
                $this->addError('plg.CMBlogPro.admin.blog.copy_error');
            }
        } else {
            $this->addError('plg.CMBlogPro.admin.blog.copy_error');
        }

        return $this->redirectToRoute('cm_blog_pro_page_list');
    }
    

    /**
     * BlogCategory作成
     *
     * @param \Plugin\CMBlogPro\Entity\Blog $blog
     *
     * @return array
     */
    private function setChoicedCategoryIds($blog) {
        $ChoicedCategoryIds = [];
        $categories = $blog->getBlogCategories();

        if ($categories)
            foreach ($blog->getBlogCategories() as $category)
                array_push($ChoicedCategoryIds, $category->getCategoryId());

        return $ChoicedCategoryIds;
    }
    
    /**
     * BlogCategory作成
     *
     * @param \Plugin\CMBlogPro\Entity\Blog $blog
     * @param \Plugin\CMBlogPro\Entity\Category $category
     *
     * @return \Plugin\CMBlogPro\Entity\BlogCategory
     */
    private function createBlogCategory($blog, $category)
    {
        $blogCategory = new BlogCategory();
        $blogCategory->setBlog($blog);
        $blogCategory->setBlogId($blog->getId());
        $blogCategory->setCategory($category);
        $blogCategory->setCategoryId($category->getId());

        return $blogCategory;
    }
}
