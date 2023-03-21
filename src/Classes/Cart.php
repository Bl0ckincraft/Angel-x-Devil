<?php

namespace App\Classes;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use http\Exception\RuntimeException;

class Cart
{
    private ?EntityManagerInterface $entityManager;

    public function __construct(private array $content)
    {

    }

    public function withEntityManager(EntityManagerInterface $entityManager): Cart
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * Checks if products in cart still exist and remove them if not
     * @param EntityManagerInterface $entityManager used to access product repository
     * @return void
     */
    public function checkProducts(EntityManagerInterface $entityManager): void
    {
        foreach ($this->content as $productId => $productQuantity) {
            $product = $entityManager->getRepository(Product::class)->find($productId);

            if (!$product) {
                $this->removeProduct($productId);
            }
        }
    }

    /**
     * Checks if products in cart still exist and remove them if not <br/>
     * Make sure to call withEntityManager() before to use this
     * @return void
     */
    public function _checkProducts(): void
    {
        if (!$this->entityManager) {
            throw new RuntimeException('_checkProducts() called without defined entityManager!');
        }

        foreach ($this->content as $productId => $productQuantity) {
            $product = $this->entityManager->getRepository(Product::class)->find($productId);

            if (!$product) {
                $this->removeProduct($productId);
            }
        }
    }

    /**
     * Add a product in cart
     * @param int $productId product to add
     * @param int $quantity quantity of product to add
     * @return void
     */
    public function addProduct(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        if (array_key_exists($productId, $this->content)) {
            $this->content[$productId] += $quantity;
        } else {
            $this->content[$productId] = $quantity;
        }
    }

    /**
     * Get quantity of a product in cart
     * @param int $productId product to get
     * @return int quantity of product in cart
     */
    public function getProduct(int $productId): int
    {
        if (array_key_exists($productId, $this->content)) {
            return $this->content[$productId];
        } else {
            return 0;
        }
    }

    /**
     * Remove a product from cart
     * @param int $productId product to remove
     * @param int $quantity quantity of product to remove
     * @return void
     */
    public function removeProduct(int $productId, int $quantity = -1): void
    {
        if (!array_key_exists($productId, $this->content)) {
            return;
        }

        if ($quantity < 0) {
            unset($this->content[$productId]);
        } else {
            $this->content[$productId] -= $quantity;

            if ($this->content[$productId] <= 0) {
                unset($this->content[$productId]);
            }
        }
    }

    /**
     * Clear all products of cart
     * @return void
     */
    public function clear(): void
    {
        $this->content = [];
    }

    /**
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent(array $content): void
    {
        $this->content = $content;
    }

    public function getTotalQuantity(): int
    {
        $total = 0;

        foreach ($this->content as $quantity) {
            $total += $quantity;
        }

        return $total;
    }

    public function getTotalPrice(EntityManagerInterface $entityManager): int
    {
        $total = 0;

        foreach ($this->content as $productId => $quantity) {
            /** @var Product $product **/
            $product = $entityManager->getRepository(Product::class)->find($productId);
            $total += $quantity * $product->getPrice();
        }

        return $total;
    }

    /**
     * Make sure to call withEntityManager() before to use this
     * @return int total price of cart
     */
    public function _getTotalPrice(): int
    {
        if (!$this->entityManager) {
            throw new RuntimeException('_getTotalPrice() called without defined entityManager!');
        }

        $total = 0;

        foreach ($this->content as $productId => $quantity) {
            /** @var Product $product **/
            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            $total += $quantity * $product->getPrice();
        }

        return $total;
    }

    public function getCartComplete(EntityManagerInterface $entityManager): array
    {
        $cartComplete = [];

        foreach ($this->content as $productId => $productQuantity) {
            $product = $entityManager->getRepository(Product::class)->find($productId);

            if ($product) {
                $cartComplete[] = [
                    'product' => $product,
                    'quantity' => $productQuantity
                ];
            }
        }

        return $cartComplete;
    }

    /**
     * Make sure to call withEntityManager() before to use this
     * @return array complete cart
     */
    public function _getCartComplete(): array
    {
        if (!$this->entityManager) {
            throw new RuntimeException('_getCartComplete() called without defined entityManager!');
        }

        $cartComplete = [];

        foreach ($this->content as $productId => $productQuantity) {
            $product = $this->entityManager->getRepository(Product::class)->find($productId);

            if ($product) {
                $cartComplete[] = [
                    'product' => $product,
                    'quantity' => $productQuantity
                ];
            }
        }

        return $cartComplete;
    }
}