<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\CategoriesFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ProductsFixtures extends Fixture implements DependentFixtureInterface
{
    private $root_directory;
    public function __construct(string $root_directory)
    {
        $this->root_directory = $root_directory;
    }

    public function load(ObjectManager $manager): void
    {
        $productDatas = json_decode(file_get_contents($this->root_directory . '/src/DataFixtures/jsons/product.json'));

        foreach ($productDatas as $productData) {

            $product = new Product();
            $product->setName($productData->name);
            $product->setDescription($productData->description);
            $product->setReference($productData->reference);
            $product->setStock($productData->stock);
            $product->setCategory($manager->getRepository(Category::class)->find($productData->category));
            
            $manager->persist($product);            
        }
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CategoriesFixtures::class
        ];
    }
}