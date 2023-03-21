<?php

namespace App\Controller;

use App\Controller\Base\AbstractAppController;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractAppController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    #[Route('/products', name: 'app_products')]
    public function index(): Response
    {
        $products = $this->entityManager->getRepository(Product::class)->findAll();
        $categories = $this->entityManager->getRepository(Category::class)->findAll();

        return $this->render('product/products.html.twig', [
            'products' => $products,
            'categories' => $categories
        ]);
    }

    #[Route('/products/{slug}', name: 'app_product')]
    public function show(string $slug): Response
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBySlug($slug);

        if (!$product) {
            return $this->redirectToRoute('app_products');
        }

        return $this->render('product/product.html.twig', [
            'product' => $product
        ]);
    }
}
