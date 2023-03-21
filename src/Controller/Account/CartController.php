<?php

namespace App\Controller\Account;

use App\Controller\Base\AbstractAppController;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractAppController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    #[Route('/account/cart', name: 'app_cart')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $cart = $user->getCart();
        $cart->checkProducts($this->entityManager);
        $user->setCart($cart);

        $this->entityManager->flush();

        return $this->render('account/cart/cart.html.twig', [
            'cart' => $cart->withEntityManager($this->entityManager)
        ]);
    }

    #[Route('/api/cart/add', name: 'app_add_to_cart', methods: 'POST')]
    public function addToCart(Request $request): Response
    {
        $data = $this->editCart(true, $request);

        return new JsonResponse($data);
    }

    #[Route('/api/cart/remove', name: 'app_remove_from_cart', methods: 'POST')]
    public function removeFromCart(Request $request): Response
    {
        $data = $this->editCart(false, $request);

        return new JsonResponse($data);
    }

    #[Route('/api/cart/clear', name: 'app_clear_cart', methods: 'POST')]
    public function clearCart(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $data = [
                'state' => 'error',
                'message' => 'Vous devez être connecté pour vider le panier.'
            ];

            return new JsonResponse($data);
        }

        $cart = $user->getCart();

        $cart->clear();

        $user->setCart($cart);
        $this->entityManager->flush();

        $data = [
            'state' => 'success',
            'message' => 'Panier vidé.',
            'cart' => $cart
        ];

        return new JsonResponse($data);
    }

    /**
     * @param bool $add true to add and false to remove
     * @param Request $request request to edit
     * @return void
     */
    private function editCart(bool $add, Request $request): array
    {
        $productId = $request->request->get('id');
        $quantity = $request->request->get('quantity');

        $product = $this->entityManager->getRepository(Product::class)->find($productId);

        if (!$product) {
            $data = [
                'state' => 'error',
                'message' => 'Produit inconnu.'
            ];

            return $data;
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            $data = [
                'state' => 'error',
                'message' => 'Vous devez être connecté pour '.($add ? 'ajouté' : 'retiré').' un produit du panier.'
            ];

            return $data;
        }

        $cart = $user->getCart();

        if ($add) {
            $cart->addProduct($productId, $quantity);
        } else {
            $cart->removeProduct($productId, $quantity);
        }

        $cart->checkProducts($this->entityManager);

        $user->setCart($cart);
        $this->entityManager->flush();

        $cartComplete = $cart->getCartComplete($this->entityManager);
        $cartData = [];

        /**
         * @var Product $product
         * @var int $quantity
         */
        foreach ($cartComplete as $element) {
            $product = $element['product'];
            $quantity = $element['quantity'];

            $cartData[] = [
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    'illustration' => $product->getIllustration(),
                    'price' => $product->getPrice(),
                    'category' => $product->getCategory()->__toString(),
                    'subtitle' => $product->getSubtitle()
                ],
                'quantity' => $quantity
            ];
        }

        $data = [
            'state' => 'success',
            'message' => 'Produit '.($add ? 'ajouté' : 'retiré').' au panier.',
            'cart' => [
                'cart_complete' => $cartData,
                'cart_total_price' => $cart->getTotalPrice($this->entityManager),
                'cart_total_quantity' => $cart->getTotalQuantity()
            ]
        ];

        return $data;
    }
}
